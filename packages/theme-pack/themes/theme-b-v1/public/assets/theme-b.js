/**
 * Theme B - Bold Modern
 * Dark, vibrant, geometric microsite runtime.
 */
(function () {
  'use strict';

  // ---------------------------------------------------------------------------
  // Helpers
  // ---------------------------------------------------------------------------

  function get(obj, path, fallback) {
    if (!obj) return fallback;
    var keys = path.split('.');
    var val = obj;
    for (var i = 0; i < keys.length; i++) {
      val = val[keys[i]];
      if (val === undefined || val === null) return fallback;
    }
    return val;
  }

  function el(tag, className, textContent) {
    var node = document.createElement(tag);
    if (className) node.className = className;
    if (textContent) node.textContent = textContent;
    return node;
  }

  function fetchJSON(url) {
    return fetch(url, { headers: { Accept: 'application/json' } })
      .then(function (r) {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
      });
  }

  function formatPrice(n) {
    if (!n) return '$0';
    return '$' + Number(n).toLocaleString('en-US');
  }

  var ICONS = {
    search: '\u{1F50D}',
    shield: '\u{1F6E1}\uFE0F',
    zap: '\u26A1',
    bed: '\u{1F6CF}\uFE0F',
    bath: '\u{1F6BF}',
    area: '\u{1F4D0}'
  };

  // ---------------------------------------------------------------------------
  // Config
  // ---------------------------------------------------------------------------

  var config = window.__MICROSITE__ || {};
  var settings = get(config, 'settings', {});
  var route = get(config, 'route', {});
  var sections = get(route, 'sections', []);
  var routeId = get(config, 'routeId', '');
  var apiBaseUrl = get(config, 'apiBaseUrl', '');
  var siteId = get(config, 'siteId', '');

  // ---------------------------------------------------------------------------
  // Init
  // ---------------------------------------------------------------------------

  document.addEventListener('DOMContentLoaded', function () {
    renderAll();
  });

  /**
   * Full render pass: apply brand, render sections.
   * Can be called multiple times for live preview updates.
   */
  function renderAll() {
    applyBrand();
    var root = document.getElementById('microsite-root');
    if (!root) return;

    root.innerHTML = '';
    for (var i = 0; i < sections.length; i++) {
      var sec = sections[i];
      if (!sec.visible) continue;
      var node = renderSection(sec, root);
      if (node) root.appendChild(node);
    }

    if (routeId === 'listing-detail') {
      initDetailPage(root);
    }
  }

  // ---------------------------------------------------------------------------
  // Live Preview (postMessage)
  // ---------------------------------------------------------------------------

  window.addEventListener('message', function (event) {
    if (!event.data || event.data.type !== 'MICROSITE_PREVIEW_UPDATE') return;

    var msg = event.data;
    if (msg.settings) {
      settings = msg.settings;
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

  function applyBrand() {
    var brand = get(settings, 'brand', {});
    var r = document.documentElement;
    if (brand.primaryColor) {
      r.style.setProperty('--tb-primary', brand.primaryColor);
    }
    if (brand.secondaryColor) {
      r.style.setProperty('--tb-primary-dark', brand.secondaryColor);
    }
    if (brand.font) {
      r.style.setProperty('--tb-font', brand.font);
    }
  }

  // ---------------------------------------------------------------------------
  // Section Router
  // ---------------------------------------------------------------------------

  function renderSection(sec, root) {
    switch (sec.type) {
      case 'navbar': return renderNavbar(sec.props);
      case 'banner': return renderBanner(sec.props);
      case 'features': return renderFeatures(sec.props);
      case 'cta': return renderCTA(sec.props);
      case 'property-grid': return renderPropertyGrid(sec.props);
      case 'site-footer': return renderFooter(sec.props);
      default: return null;
    }
  }

  // ---------------------------------------------------------------------------
  // Navbar
  // ---------------------------------------------------------------------------

  function renderNavbar(props) {
    var nav = el('nav', 'tb-navbar');
    var inner = el('div', 'tb-navbar__inner');
    var brand = el('a', 'tb-navbar__brand', get(props, 'brandName', 'Site'));
    brand.href = '/';
    inner.appendChild(brand);

    var links = get(props, 'links', []);
    var ul = el('ul', 'tb-navbar__links');
    for (var i = 0; i < links.length; i++) {
      var li = el('li');
      var a = el('a', '', links[i].label);
      a.href = links[i].href || '#';
      li.appendChild(a);
      ul.appendChild(li);
    }
    inner.appendChild(ul);
    nav.appendChild(inner);
    return nav;
  }

  // ---------------------------------------------------------------------------
  // Banner
  // ---------------------------------------------------------------------------

  function renderBanner(props) {
    var section = el('section', 'tb-banner');
    var content = el('div', 'tb-banner__content');
    var heading = get(props, 'heading', '');
    var sub = get(props, 'subheading', '');
    var ctaText = get(props, 'ctaText', '');
    var ctaHref = get(props, 'ctaHref', '');

    if (heading) content.appendChild(el('h1', 'tb-banner__heading', heading));
    if (sub) content.appendChild(el('p', 'tb-banner__sub', sub));
    if (ctaText) {
      var btn = el('a', 'tb-btn', ctaText);
      btn.href = ctaHref || '#';
      content.appendChild(btn);
    }
    section.appendChild(content);
    return section;
  }

  // ---------------------------------------------------------------------------
  // Features
  // ---------------------------------------------------------------------------

  function renderFeatures(props) {
    var section = el('section', 'tb-features');
    var inner = el('div', 'tb-features__inner');
    var title = get(props, 'title', '');
    if (title) inner.appendChild(el('h2', 'tb-features__title', title));

    var items = get(props, 'items', []);
    var grid = el('div', 'tb-features__grid');
    for (var i = 0; i < items.length; i++) {
      var item = items[i];
      var card = el('div', 'tb-feature-card');
      var icon = el('div', 'tb-feature-card__icon', ICONS[item.icon] || '\u2728');
      card.appendChild(icon);
      card.appendChild(el('h3', 'tb-feature-card__title', item.title || ''));
      card.appendChild(el('p', 'tb-feature-card__desc', item.desc || ''));
      grid.appendChild(card);
    }
    inner.appendChild(grid);
    section.appendChild(inner);
    return section;
  }

  // ---------------------------------------------------------------------------
  // CTA / Lead Form
  // ---------------------------------------------------------------------------

  function renderCTA(props) {
    var section = el('section', 'tb-cta');
    var inner = el('div', 'tb-cta__inner');

    var heading = get(props, 'heading', '');
    if (heading) inner.appendChild(el('h2', 'tb-cta__heading', heading));

    var form = el('form', 'tb-cta__form');
    form.innerHTML =
      '<input type="text" class="tb-input" name="name" placeholder="Your Name" required>' +
      '<input type="email" class="tb-input" name="email" placeholder="Email Address" required>' +
      '<input type="tel" class="tb-input" name="phone" placeholder="Phone (optional)">' +
      '<textarea class="tb-input tb-input--full" name="message" placeholder="Message" rows="3"></textarea>' +
      '<div class="tb-cta__success">Thank you! We\'ll be in touch soon.</div>' +
      '<button type="submit" class="tb-btn tb-cta__submit">' +
        (get(props, 'buttonLabel', 'Submit')) +
      '</button>';

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var fd = new FormData(form);
      var btn = form.querySelector('.tb-cta__submit');
      btn.disabled = true;
      btn.textContent = 'Sending...';

      var payload = {
        name: fd.get('name'),
        email: fd.get('email'),
        phone: fd.get('phone') || '',
        message: fd.get('message') || '',
        site_id: siteId,
        route_id: routeId,
        source_url: window.location.href
      };

      fetch(apiBaseUrl + '/api/public/leads', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify(payload)
      })
        .then(function () {
          form.querySelector('.tb-cta__success').classList.add('visible');
          btn.textContent = 'Sent!';
        })
        .catch(function () {
          btn.disabled = false;
          btn.textContent = get(props, 'buttonLabel', 'Submit');
          alert('Something went wrong. Please try again.');
        });
    });

    inner.appendChild(form);
    section.appendChild(inner);
    return section;
  }

  // ---------------------------------------------------------------------------
  // Property Grid (Listings)
  // ---------------------------------------------------------------------------

  function renderPropertyGrid(props) {
    var section = el('section', 'tb-pgrid');
    var inner = el('div', 'tb-pgrid__inner');

    var heading = get(props, 'heading', '');
    if (heading) inner.appendChild(el('h2', 'tb-pgrid__heading', heading));

    var grid = el('div', 'tb-pgrid__grid');
    grid.appendChild(el('div', 'tb-pgrid__loading', 'Loading properties...'));
    inner.appendChild(grid);
    section.appendChild(inner);

    var url = apiBaseUrl + '/api/public/properties?site_id=' + encodeURIComponent(siteId);
    fetchJSON(url)
      .then(function (response) {
        var properties = response.data || response || [];
        grid.innerHTML = '';
        if (!Array.isArray(properties) || properties.length === 0) {
          grid.appendChild(el('div', 'tb-pgrid__empty', 'No properties available.'));
          return;
        }
        for (var i = 0; i < properties.length; i++) {
          grid.appendChild(createPropertyCard(properties[i]));
        }
      })
      .catch(function (err) {
        console.error('[Theme B] Failed to load properties:', err);
        grid.innerHTML = '';
        grid.appendChild(el('div', 'tb-pgrid__empty', 'Unable to load properties.'));
      });

    return section;
  }

  function createPropertyCard(prop) {
    var card = el('div', 'tb-pcard');
    card.addEventListener('click', function () {
      window.location.href = '/listings/' + prop.id;
    });

    if (prop.image_url) {
      var img = el('img', 'tb-pcard__img');
      img.src = prop.image_url;
      img.alt = prop.title || '';
      img.loading = 'lazy';
      card.appendChild(img);
    } else {
      card.appendChild(el('div', 'tb-pcard__img-placeholder', 'No Image'));
    }

    var body = el('div', 'tb-pcard__body');
    body.appendChild(el('div', 'tb-pcard__price', formatPrice(prop.price)));
    body.appendChild(el('div', 'tb-pcard__title', prop.title || 'Untitled'));
    body.appendChild(el('div', 'tb-pcard__address', [prop.address, prop.city, prop.state].filter(Boolean).join(', ')));

    var meta = el('div', 'tb-pcard__meta');
    if (prop.bedrooms != null) {
      meta.innerHTML +=
        '<span>' + ICONS.bed + ' ' + prop.bedrooms + ' bd</span>';
    }
    if (prop.bathrooms != null) {
      meta.innerHTML +=
        '<span>' + ICONS.bath + ' ' + prop.bathrooms + ' ba</span>';
    }
    if (prop.sqft != null) {
      meta.innerHTML +=
        '<span>' + ICONS.area + ' ' + Number(prop.sqft).toLocaleString() + ' sqft</span>';
    }
    body.appendChild(meta);
    card.appendChild(body);
    return card;
  }

  // ---------------------------------------------------------------------------
  // Detail Page
  // ---------------------------------------------------------------------------

  function initDetailPage(root) {
    var pathParts = window.location.pathname.replace(/\/+$/, '').split('/');
    var propertyId = pathParts[pathParts.length - 1];
    if (!propertyId || propertyId === 'listings') return;

    var detailWrapper = el('div', 'tb-detail');
    detailWrapper.appendChild(el('div', 'tb-pgrid__loading', 'Loading property...'));

    // Insert after navbar
    var firstBanner = root.querySelector('.tb-banner');
    if (firstBanner) {
      firstBanner.parentNode.insertBefore(detailWrapper, firstBanner.nextSibling);
      firstBanner.style.display = 'none'; // hide banner on detail
    } else {
      root.appendChild(detailWrapper);
    }

    var url = apiBaseUrl + '/api/public/properties/' + encodeURIComponent(propertyId) + '?site_id=' + encodeURIComponent(siteId);
    fetchJSON(url)
      .then(function (response) {
        var prop = response.data || response;
        detailWrapper.innerHTML = '';
        renderDetail(detailWrapper, prop);
      })
      .catch(function (err) {
        console.error('[Theme B] Failed to load property:', err);
        detailWrapper.innerHTML = '<p style="text-align:center;padding:48px;color:var(--tb-text-muted)">Property not found.</p>';
      });
  }

  function renderDetail(container, prop) {
    if (prop.image_url) {
      var img = el('img', 'tb-detail__img');
      img.src = prop.image_url;
      img.alt = prop.title || '';
      container.appendChild(img);
    }

    var header = el('div', 'tb-detail__header');
    var left = el('div');
    left.appendChild(el('h1', 'tb-detail__title', prop.title || ''));
    left.appendChild(el('p', 'tb-detail__address', [prop.address, prop.city, prop.state, prop.zip].filter(Boolean).join(', ')));
    header.appendChild(left);
    header.appendChild(el('div', 'tb-detail__price', formatPrice(prop.price)));
    container.appendChild(header);

    var stats = el('div', 'tb-detail__stats');
    if (prop.bedrooms != null) {
      var s1 = el('div', 'tb-detail__stat');
      s1.appendChild(el('div', 'tb-detail__stat-value', String(prop.bedrooms)));
      s1.appendChild(el('div', 'tb-detail__stat-label', 'Bedrooms'));
      stats.appendChild(s1);
    }
    if (prop.bathrooms != null) {
      var s2 = el('div', 'tb-detail__stat');
      s2.appendChild(el('div', 'tb-detail__stat-value', String(prop.bathrooms)));
      s2.appendChild(el('div', 'tb-detail__stat-label', 'Bathrooms'));
      stats.appendChild(s2);
    }
    if (prop.sqft != null) {
      var s3 = el('div', 'tb-detail__stat');
      s3.appendChild(el('div', 'tb-detail__stat-value', Number(prop.sqft).toLocaleString()));
      s3.appendChild(el('div', 'tb-detail__stat-label', 'Sq Ft'));
      stats.appendChild(s3);
    }
    container.appendChild(stats);

    if (prop.description) {
      container.appendChild(el('p', 'tb-detail__desc', prop.description));
    }
  }

  // ---------------------------------------------------------------------------
  // Footer
  // ---------------------------------------------------------------------------

  function renderFooter(props) {
    return el('footer', 'tb-footer', get(props, 'copyright', ''));
  }

})();
