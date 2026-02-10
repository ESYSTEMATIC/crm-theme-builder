<?php

namespace Database\Seeders;

use App\Models\Platform\Site;
use App\Models\Platform\SiteDomain;
use App\Models\Platform\SiteVersion;
use App\Models\Platform\SiteVersionPayload;
use App\Models\Platform\Theme;
use App\Models\Tenant\Property;
use App\Services\TenantConnectionManager;
use App\Services\ThemeRegistry;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding platform database...');

        // 1. Sync themes from disk
        $registry = app(ThemeRegistry::class);
        try {
            $synced = $registry->syncAll();
            $this->command->info('Synced themes: ' . implode(', ', $synced));
        } catch (\Throwable $e) {
            $this->command->warn('Could not sync themes from disk: ' . $e->getMessage());
            $this->command->info('Creating theme-a-v1 manually...');

            Theme::updateOrCreate(
                ['key' => 'theme-a-v1'],
                ['name' => 'Theme A', 'version' => '1.0.0', 'is_active' => true]
            );
        }

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
            $defaultPayload = [];
            try {
                $defaultPayload = $registry->getDefaultPayload('theme-a-v1');
            } catch (\Throwable $e) {
                $this->command->warn('Could not load default payload: ' . $e->getMessage());
                $defaultPayload = $this->fallbackPayload();
            }

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

    private function fallbackPayload(): array
    {
        return [
            'settings' => [
                'seo' => [
                    'titleSuffix' => ' | Demo Real Estate',
                ],
                'branding' => [
                    'primaryColor' => '#2563eb',
                    'logo' => null,
                ],
            ],
            'routes' => [
                'home' => [
                    'seo' => [
                        'title' => 'Home',
                    ],
                    'sections' => [],
                ],
                'listings' => [
                    'seo' => [
                        'title' => 'Listings',
                    ],
                    'sections' => [],
                ],
                'listing-detail' => [
                    'seo' => [
                        'title' => 'Property Details',
                    ],
                    'sections' => [],
                ],
                'about' => [
                    'seo' => [
                        'title' => 'About Us',
                    ],
                    'sections' => [],
                ],
                'contact' => [
                    'seo' => [
                        'title' => 'Contact',
                    ],
                    'sections' => [],
                ],
            ],
        ];
    }
}
