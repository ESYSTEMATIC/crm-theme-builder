<?php

namespace Database\Seeders;

use App\Models\Platform\Site;
use App\Models\Platform\SiteDomain;
use App\Models\Platform\SiteVersion;
use App\Models\Platform\SiteVersionPayload;
use App\Models\Platform\Theme;
use App\Models\Platform\ThemeManifest;
use App\Models\Tenant\Property;
use App\Services\TenantConnectionManager;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding platform database...');

        // 1. Seed themes directly into DB
        $this->seedThemes();

        // 2. Create a sample site
        $theme = Theme::where('key', 'theme-a-v1')->first();

        if (!$theme) {
            $this->command->error('Theme theme-a-v1 not found. Cannot create sample site.');
            return;
        }

        $site = Site::updateOrCreate(
            ['slug' => 'demo-site'],
            [
                'tenant_id' => 'tenant_1',
                'theme_id' => $theme->id,
            ]
        );

        $this->command->info("Created site: {$site->slug} (ID: {$site->id})");

        // 3. Create initial draft version with default payload
        $existingDraft = $site->draftVersion;
        if (!$existingDraft) {
            $defaultPayload = $theme->default_payload_json;

            $version = SiteVersion::create([
                'site_id' => $site->id,
                'version' => 1,
                'status' => 'draft',
                'created_by' => 'seeder',
            ]);

            SiteVersionPayload::create([
                'site_version_id' => $version->id,
                'payload_json' => $defaultPayload,
                'checksum' => md5(json_encode($defaultPayload)),
            ]);

            $this->command->info("Created draft version {$version->version} for site {$site->slug}");
        }

        // 4. Create platform subdomain domain
        SiteDomain::updateOrCreate(
            ['host' => 'demo-site.crmwebsite.com'],
            [
                'site_id' => $site->id,
                'type' => 'platform_subdomain',
                'status' => 'verified',
                'verified_at' => now(),
            ]
        );

        $this->command->info('Created platform subdomain: demo-site.crmwebsite.com');

        // 5. Seed sample properties in tenant DB
        $this->seedTenantProperties();
    }

    private function seedThemes(): void
    {
        $themes = [
            [
                'key' => 'theme-a-v1',
                'name' => 'Theme A',
                'version' => '1.0.0',
                'manifest' => [
                    'key' => 'theme-a-v1',
                    'name' => 'Theme A',
                    'version' => '1.0.0',
                    'routes' => [
                        ['id' => 'home', 'path' => '/', 'type' => 'static', 'label' => 'Home'],
                        ['id' => 'about', 'path' => '/about', 'type' => 'static', 'label' => 'About'],
                        ['id' => 'listings', 'path' => '/listings', 'type' => 'collection', 'source' => 'properties', 'label' => 'Listings'],
                        ['id' => 'listing-detail', 'path' => '/listings/:id', 'type' => 'detail', 'source' => 'properties', 'label' => 'Listing Detail'],
                    ],
                    'sections' => [
                        'home' => ['header', 'hero', 'lead-form', 'footer'],
                        'about' => ['header', 'hero', 'footer'],
                        'listings' => ['header', 'gallery', 'footer'],
                        'listing-detail' => ['header', 'hero', 'lead-form', 'footer'],
                    ],
                    'sectionTypes' => [
                        'header' => ['label' => 'Header', 'props' => ['logoText' => 'string', 'navLinks' => 'array']],
                        'hero' => ['label' => 'Hero', 'props' => ['title' => 'string', 'subtitle' => 'string', 'backgroundImage' => 'string']],
                        'gallery' => ['label' => 'Gallery', 'props' => ['title' => 'string', 'columns' => 'number']],
                        'lead-form' => ['label' => 'Lead Form', 'props' => ['headline' => 'string', 'submitLabel' => 'string']],
                        'footer' => ['label' => 'Footer', 'props' => ['text' => 'string', 'links' => 'array']],
                    ],
                ],
                'default_payload' => [
                    'settings' => [
                        'brand' => ['primaryColor' => '#2563eb', 'secondaryColor' => '#1e40af', 'font' => 'Inter, sans-serif'],
                        'seo' => ['titleSuffix' => ' | My Website'],
                    ],
                    'routes' => [
                        'home' => [
                            'seo' => ['title' => 'Home'],
                            'sections' => [
                                ['type' => 'header', 'visible' => true, 'props' => ['logoText' => 'My Site', 'navLinks' => [['label' => 'Home', 'href' => '/'], ['label' => 'About', 'href' => '/about'], ['label' => 'Listings', 'href' => '/listings']]]],
                                ['type' => 'hero', 'visible' => true, 'props' => ['title' => 'Find Your Dream Home', 'subtitle' => 'Browse our curated selection of premium properties', 'backgroundImage' => '']],
                                ['type' => 'lead-form', 'visible' => true, 'props' => ['headline' => 'Get in Touch', 'submitLabel' => 'Send Message']],
                                ['type' => 'footer', 'visible' => true, 'props' => ['text' => "\xC2\xA9 2024 My Site. All rights reserved.", 'links' => []]],
                            ],
                        ],
                        'about' => [
                            'seo' => ['title' => 'About Us'],
                            'sections' => [
                                ['type' => 'header', 'visible' => true, 'props' => ['logoText' => 'My Site', 'navLinks' => [['label' => 'Home', 'href' => '/'], ['label' => 'About', 'href' => '/about'], ['label' => 'Listings', 'href' => '/listings']]]],
                                ['type' => 'hero', 'visible' => true, 'props' => ['title' => 'About Our Company', 'subtitle' => 'Dedicated to helping you find the perfect property', 'backgroundImage' => '']],
                                ['type' => 'footer', 'visible' => true, 'props' => ['text' => "\xC2\xA9 2024 My Site. All rights reserved.", 'links' => []]],
                            ],
                        ],
                        'listings' => [
                            'seo' => ['title' => 'Listings'],
                            'sections' => [
                                ['type' => 'header', 'visible' => true, 'props' => ['logoText' => 'My Site', 'navLinks' => [['label' => 'Home', 'href' => '/'], ['label' => 'About', 'href' => '/about'], ['label' => 'Listings', 'href' => '/listings']]]],
                                ['type' => 'gallery', 'visible' => true, 'props' => ['title' => 'Featured Properties', 'columns' => 3]],
                                ['type' => 'footer', 'visible' => true, 'props' => ['text' => "\xC2\xA9 2024 My Site. All rights reserved.", 'links' => []]],
                            ],
                        ],
                        'listing-detail' => [
                            'seo' => ['title' => 'Property Details'],
                            'sections' => [
                                ['type' => 'header', 'visible' => true, 'props' => ['logoText' => 'My Site', 'navLinks' => [['label' => 'Home', 'href' => '/'], ['label' => 'About', 'href' => '/about'], ['label' => 'Listings', 'href' => '/listings']]]],
                                ['type' => 'hero', 'visible' => true, 'props' => ['title' => '', 'subtitle' => '', 'backgroundImage' => '']],
                                ['type' => 'lead-form', 'visible' => true, 'props' => ['headline' => 'Interested in this property?', 'submitLabel' => 'Request Info']],
                                ['type' => 'footer', 'visible' => true, 'props' => ['text' => "\xC2\xA9 2024 My Site. All rights reserved.", 'links' => []]],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'key' => 'theme-b-v1',
                'name' => 'Theme B - Bold Modern',
                'version' => '1.0.0',
                'manifest' => [
                    'key' => 'theme-b-v1',
                    'name' => 'Theme B - Bold Modern',
                    'version' => '1.0.0',
                    'routes' => [
                        ['id' => 'home', 'path' => '/', 'label' => 'Home', 'type' => 'static'],
                        ['id' => 'about', 'path' => '/about', 'label' => 'About', 'type' => 'static'],
                        ['id' => 'listings', 'path' => '/listings', 'label' => 'Listings', 'type' => 'collection', 'source' => 'properties'],
                        ['id' => 'listing-detail', 'path' => '/listings/:id', 'label' => 'Listing Detail', 'type' => 'detail', 'source' => 'properties'],
                    ],
                    'sections' => [
                        'home' => ['navbar', 'banner', 'features', 'cta', 'site-footer'],
                        'about' => ['navbar', 'banner', 'site-footer'],
                        'listings' => ['navbar', 'property-grid', 'site-footer'],
                        'listing-detail' => ['navbar', 'banner', 'cta', 'site-footer'],
                    ],
                    'sectionTypes' => [
                        'navbar' => ['label' => 'Navigation Bar', 'props' => ['brandName' => 'string', 'links' => 'array']],
                        'banner' => ['label' => 'Banner', 'props' => ['heading' => 'string', 'subheading' => 'string', 'ctaText' => 'string', 'ctaHref' => 'string']],
                        'features' => ['label' => 'Features', 'props' => ['title' => 'string', 'items' => 'array']],
                        'cta' => ['label' => 'Call to Action / Lead Form', 'props' => ['heading' => 'string', 'buttonLabel' => 'string']],
                        'property-grid' => ['label' => 'Property Grid', 'props' => ['heading' => 'string', 'columns' => 'number']],
                        'site-footer' => ['label' => 'Site Footer', 'props' => ['copyright' => 'string', 'links' => 'array']],
                    ],
                ],
                'default_payload' => [
                    'settings' => [
                        'brand' => ['primaryColor' => '#f97316', 'secondaryColor' => '#ea580c', 'font' => 'Poppins, sans-serif'],
                        'seo' => ['titleSuffix' => ' | Bold Realty'],
                    ],
                    'routes' => [
                        'home' => [
                            'seo' => ['title' => 'Home'],
                            'sections' => [
                                ['type' => 'navbar', 'visible' => true, 'props' => ['brandName' => 'Bold Realty', 'links' => [['label' => 'Home', 'href' => '/'], ['label' => 'About', 'href' => '/about'], ['label' => 'Listings', 'href' => '/listings']]]],
                                ['type' => 'banner', 'visible' => true, 'props' => ['heading' => 'Live Bold. Live Here.', 'subheading' => 'Discover extraordinary properties curated for modern living', 'ctaText' => 'Browse Listings', 'ctaHref' => '/listings']],
                                ['type' => 'features', 'visible' => true, 'props' => ['title' => 'Why Choose Us', 'items' => [['icon' => 'search', 'title' => 'Curated Selection', 'desc' => 'Hand-picked properties that meet the highest standards'], ['icon' => 'shield', 'title' => 'Trusted Agents', 'desc' => 'Work with experienced professionals who know your market'], ['icon' => 'zap', 'title' => 'Fast Closing', 'desc' => 'Streamlined process to get you into your dream home faster']]]],
                                ['type' => 'cta', 'visible' => true, 'props' => ['heading' => 'Ready to find your dream home?', 'buttonLabel' => 'Get Started']],
                                ['type' => 'site-footer', 'visible' => true, 'props' => ['copyright' => "\xC2\xA9 2024 Bold Realty. All rights reserved.", 'links' => []]],
                            ],
                        ],
                        'about' => [
                            'seo' => ['title' => 'About'],
                            'sections' => [
                                ['type' => 'navbar', 'visible' => true, 'props' => ['brandName' => 'Bold Realty', 'links' => [['label' => 'Home', 'href' => '/'], ['label' => 'About', 'href' => '/about'], ['label' => 'Listings', 'href' => '/listings']]]],
                                ['type' => 'banner', 'visible' => true, 'props' => ['heading' => 'Our Story', 'subheading' => 'Building trust through exceptional service since 2020', 'ctaText' => '', 'ctaHref' => '']],
                                ['type' => 'site-footer', 'visible' => true, 'props' => ['copyright' => "\xC2\xA9 2024 Bold Realty. All rights reserved.", 'links' => []]],
                            ],
                        ],
                        'listings' => [
                            'seo' => ['title' => 'Listings'],
                            'sections' => [
                                ['type' => 'navbar', 'visible' => true, 'props' => ['brandName' => 'Bold Realty', 'links' => [['label' => 'Home', 'href' => '/'], ['label' => 'About', 'href' => '/about'], ['label' => 'Listings', 'href' => '/listings']]]],
                                ['type' => 'property-grid', 'visible' => true, 'props' => ['heading' => 'Available Properties', 'columns' => 3]],
                                ['type' => 'site-footer', 'visible' => true, 'props' => ['copyright' => "\xC2\xA9 2024 Bold Realty. All rights reserved.", 'links' => []]],
                            ],
                        ],
                        'listing-detail' => [
                            'seo' => ['title' => 'Property Details'],
                            'sections' => [
                                ['type' => 'navbar', 'visible' => true, 'props' => ['brandName' => 'Bold Realty', 'links' => [['label' => 'Home', 'href' => '/'], ['label' => 'About', 'href' => '/about'], ['label' => 'Listings', 'href' => '/listings']]]],
                                ['type' => 'banner', 'visible' => true, 'props' => ['heading' => '', 'subheading' => '', 'ctaText' => '', 'ctaHref' => '']],
                                ['type' => 'cta', 'visible' => true, 'props' => ['heading' => 'Interested in this property?', 'buttonLabel' => 'Contact Agent']],
                                ['type' => 'site-footer', 'visible' => true, 'props' => ['copyright' => "\xC2\xA9 2024 Bold Realty. All rights reserved.", 'links' => []]],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($themes as $themeData) {
            $theme = Theme::updateOrCreate(
                ['key' => $themeData['key']],
                [
                    'name' => $themeData['name'],
                    'version' => $themeData['version'],
                    'is_active' => true,
                    'default_payload_json' => $themeData['default_payload'],
                ]
            );

            $manifest = $themeData['manifest'];
            ThemeManifest::updateOrCreate(
                ['theme_id' => $theme->id],
                [
                    'manifest_json' => $manifest,
                    'checksum' => md5(json_encode($manifest)),
                ]
            );

            $this->command->info("Seeded theme: {$themeData['key']}");
        }
    }

    private function seedTenantProperties(): void
    {
        $tenantManager = app(TenantConnectionManager::class);
        $tenantManager->connect('tenant_1');

        $properties = [
            [
                'title' => 'Modern Downtown Loft',
                'address' => '123 Main Street, Unit 4B',
                'city' => 'Austin',
                'state' => 'TX',
                'zip' => '78701',
                'price' => 425000.00,
                'bedrooms' => 2,
                'bathrooms' => 2,
                'sqft' => 1200,
                'description' => 'Stunning modern loft in the heart of downtown Austin with floor-to-ceiling windows, exposed brick, and premium finishes throughout. Walking distance to restaurants, shops, and entertainment.',
                'image_url' => 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=800',
                'status' => 'active',
            ],
            [
                'title' => 'Charming Craftsman Bungalow',
                'address' => '456 Oak Avenue',
                'city' => 'Austin',
                'state' => 'TX',
                'zip' => '78704',
                'price' => 575000.00,
                'bedrooms' => 3,
                'bathrooms' => 2,
                'sqft' => 1800,
                'description' => 'Beautifully restored 1920s craftsman bungalow in the desirable SoCo neighborhood. Original hardwood floors, updated kitchen with quartz countertops, and a spacious backyard with mature trees.',
                'image_url' => 'https://images.unsplash.com/photo-1568605114967-8130f3a36994?w=800',
                'status' => 'active',
            ],
            [
                'title' => 'Lakefront Estate',
                'address' => '789 Lakeshore Drive',
                'city' => 'Lakeway',
                'state' => 'TX',
                'zip' => '78734',
                'price' => 1250000.00,
                'bedrooms' => 5,
                'bathrooms' => 4,
                'sqft' => 4200,
                'description' => 'Spectacular lakefront estate with panoramic views of Lake Travis. Features a gourmet kitchen, home theater, infinity pool, private dock, and beautifully landscaped grounds.',
                'image_url' => 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=800',
                'status' => 'active',
            ],
            [
                'title' => 'Cozy Hill Country Cottage',
                'address' => '321 Wildflower Lane',
                'city' => 'Dripping Springs',
                'state' => 'TX',
                'zip' => '78620',
                'price' => 385000.00,
                'bedrooms' => 2,
                'bathrooms' => 1,
                'sqft' => 1100,
                'description' => 'Charming Hill Country cottage on a half-acre lot surrounded by native wildflowers and live oaks. Recently updated with new roof, HVAC, and modern bathroom. Perfect weekend retreat or starter home.',
                'image_url' => 'https://images.unsplash.com/photo-1518780664697-55e3ad937233?w=800',
                'status' => 'active',
            ],
            [
                'title' => 'Luxury High-Rise Penthouse',
                'address' => '100 Congress Avenue, PH1',
                'city' => 'Austin',
                'state' => 'TX',
                'zip' => '78701',
                'price' => 2100000.00,
                'bedrooms' => 3,
                'bathrooms' => 3,
                'sqft' => 3500,
                'description' => 'Breathtaking penthouse on the top floor of the premier high-rise on Congress Avenue. 360-degree views of the Capitol, Lady Bird Lake, and the Austin skyline. Private elevator, chef\'s kitchen, and resort-style amenities.',
                'image_url' => 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=800',
                'status' => 'active',
            ],
            [
                'title' => 'Family-Friendly Ranch Home',
                'address' => '555 Bluebonnet Trail',
                'city' => 'Round Rock',
                'state' => 'TX',
                'zip' => '78681',
                'price' => 465000.00,
                'bedrooms' => 4,
                'bathrooms' => 3,
                'sqft' => 2400,
                'description' => 'Spacious single-story ranch home in top-rated school district. Open floor plan, large game room, covered patio, and a beautifully landscaped yard with an in-ground pool. Move-in ready.',
                'image_url' => 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=800',
                'status' => 'active',
            ],
            [
                'title' => 'Historic East Side Victorian',
                'address' => '202 East 6th Street',
                'city' => 'Austin',
                'state' => 'TX',
                'zip' => '78702',
                'price' => 725000.00,
                'bedrooms' => 4,
                'bathrooms' => 2,
                'sqft' => 2200,
                'description' => 'Meticulously preserved Victorian home in Austin\'s thriving East Side. Features original millwork, stained glass windows, wrap-around porch, and a detached guest suite. Zoned for mixed-use.',
                'image_url' => 'https://images.unsplash.com/photo-1600047509807-ba8f99d2cdde?w=800',
                'status' => 'active',
            ],
            [
                'title' => 'New Construction Smart Home',
                'address' => '888 Innovation Way',
                'city' => 'Cedar Park',
                'state' => 'TX',
                'zip' => '78613',
                'price' => 550000.00,
                'bedrooms' => 4,
                'bathrooms' => 3,
                'sqft' => 2800,
                'description' => 'Brand new construction with cutting-edge smart home technology throughout. Energy-efficient design with solar panels, EV charger, and high-end finishes. Open concept living with a flex space and dedicated home office.',
                'image_url' => 'https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?w=800',
                'status' => 'active',
            ],
        ];

        foreach ($properties as $propertyData) {
            Property::updateOrCreate(
                ['address' => $propertyData['address']],
                $propertyData
            );
        }

        $this->command->info('Seeded ' . count($properties) . ' sample properties in tenant database.');
    }
}
