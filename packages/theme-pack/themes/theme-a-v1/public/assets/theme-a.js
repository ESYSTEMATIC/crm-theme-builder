/**
 * Theme A v1 - Microsite Runtime Script
 *
 * Reads window.__MICROSITE__ configuration and renders
 * the appropriate sections for the current route.
 */
(function () {
  'use strict';

  // ---------------------------------------------------------------------------
  // Helpers
  // ---------------------------------------------------------------------------

  /**
   * Safely read a nested property from an object.
   */
  function get(obj, path, fallback) {
    var keys = path.split('.');
    var current = obj;
    for (var i = 0; i < keys.length; i++) {
      if (current === null || current === undefined) return fallback;
      current = current[keys[i]];
    }
    return current !== undefined ? current : fallback;
  }

  /**
   * Create a DOM element with optional className and innerHTML.
   */
  function el(tag, className, html) {
    var node = document.createElement(tag);
    if (className) node.className = className;
    if (html !== undefined) node.innerHTML = html;
    return node;
  }

  /**
   * Escape HTML to prevent XSS.
   */
  function esc(str) {
    if (!str) return '';
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(String(str)));
    return div.innerHTML;
  }

  /**
   * Format a number as USD currency.
   */
  function formatPrice(value) {
    if (value === null || value === undefined) return '';
    return '$' + Number(value).toLocaleString('en-US');
  }

  /**
   * Perform a fetch request with JSON parsing and error handling.
   */
  function fetchJSON(url, options) {
    return fetch(url, options)
      .then(function (res) {
        if (!res.ok) throw new Error('HTTP ' + res.status + ': ' + res.statusText);
        return res.json();
      });
  }

  // ---------------------------------------------------------------------------
  // Configuration
  // ---------------------------------------------------------------------------

  var config = window.__MICROSITE__ || {};
  var settings = get(config, 'settings', {});
  var brand = get(settings, 'brand', {});
  var seo = get(settings, 'seo', {});
  var routeId = get(config, 'routeId', '');
  var route = get(config, 'route', {});
  var sections = get(route, 'sections', []);
  var apiBaseUrl = get(config, 'apiBaseUrl', '');

  // ---------------------------------------------------------------------------
  // Initialization
  // ---------------------------------------------------------------------------

  document.addEventListener('DOMContentLoaded', function () {
    renderAll();
  });

  /**
   * Full render pass: apply brand, update title, render sections.
   * Can be called multiple times for live preview updates.
   */
  function renderAll() {
    applyBrandStyles();

    var pageTitle = get(route, 'seo.title', '');
    var titleSuffix = get(seo, 'titleSuffix', '');
    if (pageTitle) {
      document.title = pageTitle + titleSuffix;
    }

    var root = document.getElementById('microsite-root');
    if (!root) return;

    root.innerHTML = '';
    renderSections(root);
  }

  // ---------------------------------------------------------------------------
  // Live Preview (postMessage)
  // ---------------------------------------------------------------------------

  window.addEventListener('message', function (event) {
    if (!event.data || event.data.type !== 'MICROSITE_PREVIEW_UPDATE') return;

    var msg = event.data;
    if (msg.settings) {
      settings = msg.settings;
      brand = get(settings, 'brand', {});
      seo = get(settings, 'seo', {});
    }
    if (msg.routeId !== undefined) {
      routeId = msg.routeId;
    }
    if (msg.route) {
      route = msg.route;
      sections = get(route, 'sections', []);
    }

    renderAll();
  });

  /**
   * Apply brand settings as CSS custom properties on :root.
   */
  function applyBrandStyles() {
    var rootStyle = document.documentElement.style;

    if (brand.primaryColor) {
      rootStyle.setProperty('--primary-color', brand.primaryColor);
    }
    if (brand.secondaryColor) {
      rootStyle.setProperty('--secondary-color', brand.secondaryColor);
    }
    if (brand.font) {
      rootStyle.setProperty('--font-family', brand.font);
    }
  }

  /**
   * Iterate over sections and render each one into the root element.
   */
  function renderSections(root) {
    for (var i = 0; i < sections.length; i++) {
      var section = sections[i];

      // Skip hidden sections
      if (section.visible === false) continue;

      var sectionEl = null;
      var type = section.type;
      var props = section.props || {};

      switch (type) {
        case 'header':
          sectionEl = renderHeader(props);
          break;
        case 'hero':
          sectionEl = renderHero(props);
          break;
        case 'gallery':
          sectionEl = renderGallery(props);
          break;
        case 'lead-form':
          sectionEl = renderLeadForm(props);
          break;
        case 'footer':
          sectionEl = renderFooter(props);
          break;
        default:
          console.warn('[Theme A] Unknown section type:', type);
      }

      if (sectionEl) {
        root.appendChild(sectionEl);
      }
    }

    // For listing-detail route, load property data and render detail section
    if (routeId === 'listing-detail') {
      loadPropertyDetail(root);
    }
  }

  // ---------------------------------------------------------------------------
  // Section Renderers
  // ---------------------------------------------------------------------------

  /**
   * Render the header / navigation bar.
   */
  function renderHeader(props) {
    var header = el('header', 'ms-header');
    var inner = el('div', 'ms-header__inner');

    // Logo
    var logo = el('a', 'ms-header__logo', esc(props.logoText || 'Site'));
    logo.href = '/';
    inner.appendChild(logo);

    // Navigation links
    var nav = el('nav', 'ms-header__nav');
    var links = props.navLinks || [];
    for (var i = 0; i < links.length; i++) {
      var link = el('a', '', esc(links[i].label || ''));
      link.href = links[i].href || '#';
      nav.appendChild(link);
    }
    inner.appendChild(nav);

    header.appendChild(inner);
    return header;
  }

  /**
   * Render the hero section with title, subtitle, and optional background image.
   */
  function renderHero(props) {
    var section = el('section', 'ms-hero');

    // Background image
    if (props.backgroundImage) {
      section.style.backgroundImage = 'url(' + esc(props.backgroundImage) + ')';
    }

    // Gradient overlay
    var overlay = el('div', 'ms-hero__overlay');
    section.appendChild(overlay);

    // Content
    var content = el('div', 'ms-hero__content');

    if (props.title) {
      var title = el('h1', 'ms-hero__title', esc(props.title));
      content.appendChild(title);
    }

    if (props.subtitle) {
      var subtitle = el('p', 'ms-hero__subtitle', esc(props.subtitle));
      content.appendChild(subtitle);
    }

    section.appendChild(content);
    return section;
  }

  /**
   * Render the gallery section.
   * For the listings route, fetches property data from the API.
   */
  function renderGallery(props) {
    var section = el('section', 'ms-gallery');

    // Title
    if (props.title) {
      var title = el('h2', 'ms-gallery__title', esc(props.title));
      section.appendChild(title);
    }

    // Grid container
    var columns = props.columns || 3;
    var grid = el('div', 'ms-gallery__grid');
    grid.style.gridTemplateColumns = 'repeat(' + columns + ', 1fr)';

    // Show loading state
    var loading = el('div', 'ms-gallery__loading', 'Loading properties');
    grid.appendChild(loading);
    section.appendChild(grid);

    // Fetch properties from API
    var url = apiBaseUrl + '/api/public/properties?site_id=' + encodeURIComponent(config.siteId);
    fetchJSON(url)
      .then(function (response) {
        var properties = response.data || response || [];
        grid.innerHTML = '';

        if (!Array.isArray(properties) || properties.length === 0) {
          var empty = el('div', 'ms-gallery__empty', 'No properties available at this time.');
          grid.appendChild(empty);
          return;
        }

        for (var i = 0; i < properties.length; i++) {
          var card = createPropertyCard(properties[i]);
          grid.appendChild(card);
        }
      })
      .catch(function (err) {
        console.error('[Theme A] Failed to load properties:', err);
        grid.innerHTML = '';
        var errorEl = el('div', 'ms-gallery__empty', 'Unable to load properties. Please try again later.');
        grid.appendChild(errorEl);
      });

    return section;
  }

  /**
   * Create a single property card element.
   */
  function createPropertyCard(property) {
    var card = el('a', 'ms-gallery__card');
    card.href = '/listings/' + (property.id || '');

    // Image or placeholder
    if (property.image || property.image_url || property.photo) {
      var img = el('img', 'ms-gallery__card-image');
      img.src = property.image || property.image_url || property.photo;
      img.alt = esc(property.title || property.name || 'Property');
      img.loading = 'lazy';
      card.appendChild(img);
    } else {
      var placeholder = el('div', 'ms-gallery__card-image--placeholder');
      var initial = (property.title || property.name || 'P').charAt(0).toUpperCase();
      placeholder.textContent = initial;
      card.appendChild(placeholder);
    }

    // Card body
    var body = el('div', 'ms-gallery__card-body');

    var title = el('h3', 'ms-gallery__card-title', esc(property.title || property.name || 'Untitled Property'));
    body.appendChild(title);

    if (property.address) {
      var address = el('p', 'ms-gallery__card-address', esc(property.address));
      body.appendChild(address);
    }

    if (property.price !== undefined && property.price !== null) {
      var price = el('div', 'ms-gallery__card-price', formatPrice(property.price));
      body.appendChild(price);
    }

    // Meta row (beds, baths, sqft)
    var hasMeta = property.beds || property.baths || property.sqft;
    if (hasMeta) {
      var meta = el('div', 'ms-gallery__card-meta');
      if (property.beds) meta.appendChild(el('span', '', property.beds + ' Beds'));
      if (property.baths) meta.appendChild(el('span', '', property.baths + ' Baths'));
      if (property.sqft) meta.appendChild(el('span', '', Number(property.sqft).toLocaleString() + ' Sqft'));
      body.appendChild(meta);
    }

    card.appendChild(body);
    return card;
  }

  /**
   * Render the lead / contact form.
   */
  function renderLeadForm(props) {
    var section = el('section', 'ms-lead-form');
    var inner = el('div', 'ms-lead-form__inner');

    // Headline
    if (props.headline) {
      var headline = el('h2', 'ms-lead-form__headline', esc(props.headline));
      inner.appendChild(headline);
    }

    // Form
    var form = el('form', 'ms-lead-form__form');
    form.setAttribute('novalidate', '');

    // Name field
    form.appendChild(createFormField('name', 'Name', 'text', 'Your full name'));

    // Email field
    form.appendChild(createFormField('email', 'Email', 'email', 'your@email.com'));

    // Phone field
    form.appendChild(createFormField('phone', 'Phone', 'tel', '(555) 123-4567'));

    // Message field
    var messageField = el('div', 'ms-lead-form__field');
    var messageLabel = el('label', 'ms-lead-form__label', 'Message');
    messageLabel.setAttribute('for', 'ms-field-message');
    var messageTextarea = el('textarea', 'ms-lead-form__textarea');
    messageTextarea.id = 'ms-field-message';
    messageTextarea.name = 'message';
    messageTextarea.placeholder = 'How can we help you?';
    messageTextarea.rows = 4;
    messageField.appendChild(messageLabel);
    messageField.appendChild(messageTextarea);
    form.appendChild(messageField);

    // Submit button
    var submitBtn = el('button', 'ms-lead-form__submit', esc(props.submitLabel || 'Submit'));
    submitBtn.type = 'submit';
    form.appendChild(submitBtn);

    // Message container for success/error
    var messageContainer = el('div', 'ms-lead-form__message-container');
    form.appendChild(messageContainer);

    // Handle form submission
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      handleLeadFormSubmit(form, submitBtn, messageContainer);
    });

    inner.appendChild(form);
    section.appendChild(inner);
    return section;
  }

  /**
   * Create a form field with label and input.
   */
  function createFormField(name, label, type, placeholder) {
    var field = el('div', 'ms-lead-form__field');

    var labelEl = el('label', 'ms-lead-form__label', label);
    labelEl.setAttribute('for', 'ms-field-' + name);

    var input = el('input', 'ms-lead-form__input');
    input.type = type;
    input.id = 'ms-field-' + name;
    input.name = name;
    input.placeholder = placeholder || '';
    input.required = true;

    field.appendChild(labelEl);
    field.appendChild(input);
    return field;
  }

  /**
   * Handle lead form submission: validate, send to API, show result.
   */
  function handleLeadFormSubmit(form, submitBtn, messageContainer) {
    // Clear previous messages
    messageContainer.innerHTML = '';

    // Gather form data
    var name = form.querySelector('[name="name"]').value.trim();
    var email = form.querySelector('[name="email"]').value.trim();
    var phone = form.querySelector('[name="phone"]').value.trim();
    var message = form.querySelector('[name="message"]').value.trim();

    // Basic validation
    if (!name || !email) {
      showFormMessage(messageContainer, 'Please fill in at least your name and email.', 'error');
      return;
    }

    if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      showFormMessage(messageContainer, 'Please enter a valid email address.', 'error');
      return;
    }

    // Disable button during submission
    var originalLabel = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Sending...';

    // Build payload
    var payload = {
      name: name,
      email: email,
      phone: phone,
      message: message,
      site_id: config.siteId || null,
      route_id: routeId,
      source_url: window.location.href
    };

    // Send to API
    var url = apiBaseUrl + '/api/public/leads';
    fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload)
    })
      .then(function (res) {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
      })
      .then(function () {
        showFormMessage(messageContainer, 'Thank you! Your message has been sent successfully.', 'success');
        form.reset();
      })
      .catch(function (err) {
        console.error('[Theme A] Lead form submission failed:', err);
        showFormMessage(messageContainer, 'Something went wrong. Please try again later.', 'error');
      })
      .finally(function () {
        submitBtn.disabled = false;
        submitBtn.textContent = originalLabel;
      });
  }

  /**
   * Show a success or error message below the form.
   */
  function showFormMessage(container, text, type) {
    container.innerHTML = '';
    var msg = el('div', 'ms-lead-form__message ms-lead-form__message--' + type, esc(text));
    container.appendChild(msg);
  }

  /**
   * Render the footer section.
   */
  function renderFooter(props) {
    var footer = el('footer', 'ms-footer');

    // Footer text
    if (props.text) {
      var text = el('p', 'ms-footer__text', esc(props.text));
      footer.appendChild(text);
    }

    // Footer links
    var links = props.links || [];
    if (links.length > 0) {
      var nav = el('nav', 'ms-footer__links');
      for (var i = 0; i < links.length; i++) {
        var a = el('a', '', esc(links[i].label || ''));
        a.href = links[i].href || '#';
        nav.appendChild(a);
      }
      footer.appendChild(nav);
    }

    return footer;
  }

  // ---------------------------------------------------------------------------
  // Property Detail (listing-detail route)
  // ---------------------------------------------------------------------------

  /**
   * Load property detail data and render it into the page.
   * Parses the property ID from the URL path: /listings/:id
   */
  function loadPropertyDetail(root) {
    // Parse property ID from URL
    var pathParts = window.location.pathname.split('/');
    var propertyId = null;

    for (var i = 0; i < pathParts.length; i++) {
      if (pathParts[i] === 'listings' && i + 1 < pathParts.length) {
        propertyId = pathParts[i + 1];
        break;
      }
    }

    if (!propertyId) {
      console.error('[Theme A] Could not parse property ID from URL');
      return;
    }

    // Create detail container and insert after hero (or at end)
    var detailSection = el('section', 'ms-property-detail');
    detailSection.innerHTML = '<div class="ms-property-detail__loading">Loading property details...</div>';

    // Insert before the lead form or footer
    var leadForm = root.querySelector('.ms-lead-form');
    if (leadForm) {
      root.insertBefore(detailSection, leadForm);
    } else {
      var footer = root.querySelector('.ms-footer');
      if (footer) {
        root.insertBefore(detailSection, footer);
      } else {
        root.appendChild(detailSection);
      }
    }

    // Fetch property data
    var url = apiBaseUrl + '/api/public/properties/' + encodeURIComponent(propertyId) + '?site_id=' + encodeURIComponent(config.siteId);
    fetchJSON(url)
      .then(function (response) {
        var property = response.data || response;
        renderPropertyDetail(detailSection, property);
        updateHeroWithProperty(root, property);
      })
      .catch(function (err) {
        console.error('[Theme A] Failed to load property:', err);
        detailSection.innerHTML =
          '<div class="ms-property-detail__loading">Unable to load property details. Please try again later.</div>';
      });
  }

  /**
   * Render the full property detail content.
   */
  function renderPropertyDetail(container, property) {
    container.innerHTML = '';

    // Header
    var header = el('div', 'ms-property-detail__header');

    var title = el('h1', 'ms-property-detail__title', esc(property.title || property.name || 'Property'));
    header.appendChild(title);

    if (property.address) {
      var address = el('p', 'ms-property-detail__address', esc(property.address));
      header.appendChild(address);
    }

    if (property.price !== undefined && property.price !== null) {
      var price = el('div', 'ms-property-detail__price', formatPrice(property.price));
      header.appendChild(price);
    }

    container.appendChild(header);

    // Stats row
    var hasMeta = property.beds || property.baths || property.sqft;
    if (hasMeta) {
      var stats = el('div', 'ms-property-detail__stats');

      if (property.beds) {
        var bedStat = el('div', 'ms-property-detail__stat');
        bedStat.appendChild(el('span', 'ms-property-detail__stat-value', String(property.beds)));
        bedStat.appendChild(el('span', 'ms-property-detail__stat-label', 'Bedrooms'));
        stats.appendChild(bedStat);
      }

      if (property.baths) {
        var bathStat = el('div', 'ms-property-detail__stat');
        bathStat.appendChild(el('span', 'ms-property-detail__stat-value', String(property.baths)));
        bathStat.appendChild(el('span', 'ms-property-detail__stat-label', 'Bathrooms'));
        stats.appendChild(bathStat);
      }

      if (property.sqft) {
        var sqftStat = el('div', 'ms-property-detail__stat');
        sqftStat.appendChild(el('span', 'ms-property-detail__stat-value', Number(property.sqft).toLocaleString()));
        sqftStat.appendChild(el('span', 'ms-property-detail__stat-label', 'Sq. Ft.'));
        stats.appendChild(sqftStat);
      }

      container.appendChild(stats);
    }

    // Description
    if (property.description) {
      var desc = el('div', 'ms-property-detail__description', esc(property.description));
      container.appendChild(desc);
    }
  }

  /**
   * Update the hero section title/subtitle with property data
   * if they were left blank in the payload (common for listing-detail route).
   */
  function updateHeroWithProperty(root, property) {
    var heroTitle = root.querySelector('.ms-hero__title');
    var heroSubtitle = root.querySelector('.ms-hero__subtitle');

    if (heroTitle && !heroTitle.textContent.trim()) {
      heroTitle.textContent = property.title || property.name || 'Property Details';
    }

    if (heroSubtitle && !heroSubtitle.textContent.trim()) {
      heroSubtitle.textContent = property.address || '';
    }

    // Update hero background image if property has one and hero has no image
    var heroSection = root.querySelector('.ms-hero');
    if (heroSection && !heroSection.style.backgroundImage) {
      var imgUrl = property.image || property.image_url || property.photo || '';
      if (imgUrl) {
        heroSection.style.backgroundImage = 'url(' + imgUrl + ')';
      }
    }
  }

})();
