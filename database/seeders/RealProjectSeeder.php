<?php

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Seeder;

/**
 * Real project data pulled from each developer's own website, added/updated per explicit user
 * request on 2026-08-14. Two of these titles already existed as generic placeholder rows from
 * ProjectSeeder ("Blue World City", "Faisal Town Phase 2") — this seeder deliberately uses
 * updateOrCreate() keyed on title so it corrects those existing rows in place (same id/slug) rather
 * than creating duplicates, and creates the other four fresh. Unlike ProjectSeeder, this one is safe
 * to re-run — every write is idempotent.
 *
 * Block names are real where the source site published them (Blue World City, Seventeen Villas,
 * Faisal Town Phase 2, Lakeshore City, ESMR's Mall/Residencia split); Kingdom Valley's block names
 * are invented (nothing was published for the Chakri project specifically). **All plot sizes and
 * prices below this point are illustrative, not sourced from any developer's published price list**
 * (only Seventeen Villas' three series prices are real, from seventeenvillas.com) — added 2026-08-14
 * per explicit request that every block should show distinct sizes/pricing on the frontend even
 * where a real figure was never published. Developer/location/description fields above each blocks
 * array are still the real, sourced data from the original 2026-08-14 fetch.
 *
 * Updated again 2026-08-14 to reflect that these six companies don't all sell the same product:
 * Seventeen Villas sells built villas (bedrooms/bathrooms added), ESMR Heights sells high-rise
 * shops/apartments conventionally priced per sqft rather than marla/kanal (unit + bedrooms updated).
 * The other four remain raw-land plot sellers, unchanged.
 */
class RealProjectSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedBlueWorldCity();
        $this->seedKingdomValley();
        $this->seedLakeshoreCity();
        $this->seedSeventeenVillas();
        $this->seedFaisalTownPhase2();
        $this->seedEsmrHeightsFaisalHills();
    }

    /** Delete-and-recreate blocks + their plot sizes, same pattern Admin\Projects\Form uses on save. */
    private function replaceBlocks(Project $project, array $blocks): void
    {
        $project->blocks()->delete();
        $project->plotSizes()->delete();

        foreach ($blocks as $blockIndex => $blockData) {
            $plots = $blockData['plots'] ?? [];
            unset($blockData['plots']);

            $block = $project->blocks()->create($blockData + ['order' => $blockIndex]);

            foreach ($plots as $plotIndex => $plot) {
                $project->plotSizes()->create($plot + [
                    'project_block_id' => $block->id,
                    'order' => $plotIndex,
                ]);
            }
        }
    }

    private function seedBlueWorldCity(): void
    {
        $project = Project::updateOrCreate(
            ['title' => 'Blue World City'],
            [
                'society_name' => 'Blue World City',
                'developer_name' => 'Blue Group of Companies',
                'type' => 'mixed_use',
                'city' => 'Rawalpindi',
                'address' => '8 KM off Chakri Interchange, Lahore-Islamabad Motorway',
                'description' => 'A large-scale master-planned community 8 km off the Chakri Interchange on the '
                    .'Lahore-Islamabad Motorway, combining residential blocks with commercial, tourism, and '
                    .'entertainment zones — including a water theme park, hot air balloon arena, and cricket '
                    .'stadium.',
                'is_active' => true,
                'is_featured' => true,
            ]
        );

        // General Block, Overseas Block, and Sports Valley are all real named blocks published on
        // blueworldcity.com; the plot sizes/prices under each are illustrative (see class doc comment).
        $this->replaceBlocks($project, [
            [
                'name' => 'General Block',
                'plots' => [
                    ['size_value' => 5, 'unit' => 'marla', 'category' => 'Residential Plot', 'total_price' => 4_500_000, 'booking_amount' => 500_000, 'installment_amount' => 110_000, 'installment_count' => 36, 'installment_frequency' => 'monthly'],
                    ['size_value' => 10, 'unit' => 'marla', 'category' => 'Residential Plot', 'total_price' => 8_500_000, 'booking_amount' => 900_000, 'installment_amount' => 200_000, 'installment_count' => 36, 'installment_frequency' => 'monthly'],
                ],
            ],
            [
                'name' => 'Overseas Block',
                'plots' => [
                    ['size_value' => 8, 'unit' => 'marla', 'category' => 'Residential Plot', 'total_price' => 7_200_000, 'booking_amount' => 800_000, 'installment_amount' => 175_000, 'installment_count' => 36, 'installment_frequency' => 'monthly'],
                    ['size_value' => 1, 'unit' => 'kanal', 'category' => 'Residential Plot', 'total_price' => 15_000_000, 'booking_amount' => 1_600_000, 'installment_amount' => 350_000, 'installment_count' => 36, 'installment_frequency' => 'monthly'],
                ],
            ],
            [
                'name' => 'Sports Valley',
                'description' => 'Residential plots overlooking the cricket stadium and sports facilities.',
                'plots' => [
                    ['size_value' => 5, 'unit' => 'marla', 'category' => 'Residential Plot', 'total_price' => 5_000_000, 'booking_amount' => 550_000, 'installment_amount' => 120_000, 'installment_count' => 36, 'installment_frequency' => 'monthly'],
                    ['size_value' => 10, 'unit' => 'marla', 'category' => 'Residential Plot', 'total_price' => 9_200_000, 'booking_amount' => 1_000_000, 'installment_amount' => 220_000, 'installment_count' => 36, 'installment_frequency' => 'monthly'],
                ],
            ],
        ]);
    }

    private function seedKingdomValley(): void
    {
        $project = Project::updateOrCreate(
            ['title' => 'Kingdom Valley'],
            [
                'society_name' => 'Kingdom Valley',
                'developer_name' => 'Kingdom Valley (Pvt) Ltd',
                'type' => 'mixed_use',
                'city' => 'Islamabad',
                'address' => 'Chakri, near New Islamabad International Airport',
                'contact_phone' => '042-111-555-187',
                'description' => 'A diversified real estate development near the New Islamabad International '
                    .'Airport in Chakri, offering residential plots alongside Kingdom Heights, its flagship '
                    .'commercial tower combining retail and business space. Kingdom Valley (Pvt) Ltd also '
                    .'operates parallel developments in Lahore and Gwadar.',
                'is_active' => true,
                'is_featured' => true,
            ]
        );

        // No block names were published for the Chakri project specifically on
        // kingdomvalleypvtltd.com — these two block names, and all plot sizes/prices, are invented.
        $this->replaceBlocks($project, [
            [
                'name' => 'General Block',
                'plots' => [
                    ['size_value' => 5, 'unit' => 'marla', 'category' => 'Residential Plot', 'total_price' => 3_800_000, 'booking_amount' => 400_000, 'installment_amount' => 95_000, 'installment_count' => 36, 'installment_frequency' => 'monthly'],
                    ['size_value' => 10, 'unit' => 'marla', 'category' => 'Residential Plot', 'total_price' => 7_000_000, 'booking_amount' => 750_000, 'installment_amount' => 165_000, 'installment_count' => 36, 'installment_frequency' => 'monthly'],
                ],
            ],
            [
                'name' => 'Executive Block',
                'plots' => [
                    ['size_value' => 8, 'unit' => 'marla', 'category' => 'Residential Plot', 'total_price' => 6_500_000, 'booking_amount' => 700_000, 'installment_amount' => 150_000, 'installment_count' => 36, 'installment_frequency' => 'monthly'],
                    ['size_value' => 1, 'unit' => 'kanal', 'category' => 'Residential Plot', 'total_price' => 13_000_000, 'booking_amount' => 1_400_000, 'installment_amount' => 300_000, 'installment_count' => 36, 'installment_frequency' => 'monthly'],
                ],
            ],
        ]);
    }

    private function seedLakeshoreCity(): void
    {
        $project = Project::updateOrCreate(
            ['title' => 'Lakeshore City'],
            [
                'society_name' => 'Lakeshore City',
                // Developer name not stated on lakeshorecity.com — left null rather than guessed.
                'developer_name' => null,
                'type' => 'mixed_use',
                'city' => 'Haripur',
                'address' => 'Near Khanpur Dam, G.T. Road, Jabbri, Haripur',
                'description' => "A lakeside gated community near Khanpur Dam combining three components — "
                    ."Lakeshore Residencia (residential plots), Lakeshore Farmhouses, and Lakeshore Club — "
                    .'roughly a 46-minute drive from Islamabad\'s Sector B-17.',
                'is_active' => true,
                'is_featured' => true,
            ]
        );

        // The three block names are real (lakeshorecity.com); plot sizes/prices are illustrative.
        $this->replaceBlocks($project, [
            [
                'name' => 'Lakeshore Residencia',
                'description' => 'Residential plots.',
                'plots' => [
                    ['size_value' => 5, 'unit' => 'marla', 'category' => 'Residential Plot', 'total_price' => 3_200_000, 'booking_amount' => 350_000, 'installment_amount' => 80_000, 'installment_count' => 36, 'installment_frequency' => 'monthly'],
                    ['size_value' => 10, 'unit' => 'marla', 'category' => 'Residential Plot', 'total_price' => 6_000_000, 'booking_amount' => 650_000, 'installment_amount' => 140_000, 'installment_count' => 36, 'installment_frequency' => 'monthly'],
                ],
            ],
            [
                'name' => 'Lakeshore Farmhouses',
                'description' => 'Farmhouse plots.',
                'plots' => [
                    ['size_value' => 2, 'unit' => 'kanal', 'category' => 'Farmhouse Plot', 'total_price' => 12_000_000, 'booking_amount' => 1_300_000, 'installment_amount' => 280_000, 'installment_count' => 36, 'installment_frequency' => 'monthly'],
                    ['size_value' => 4, 'unit' => 'kanal', 'category' => 'Farmhouse Plot', 'total_price' => 22_000_000, 'booking_amount' => 2_400_000, 'installment_amount' => 500_000, 'installment_count' => 36, 'installment_frequency' => 'monthly'],
                ],
            ],
            [
                'name' => 'Lakeshore Club',
                'description' => 'Recreational and commercial facilities.',
                'plots' => [
                    ['size_value' => 4, 'unit' => 'marla', 'category' => 'Commercial Plot', 'total_price' => 9_000_000, 'booking_amount' => 1_000_000, 'installment_amount' => 210_000, 'installment_count' => 36, 'installment_frequency' => 'monthly'],
                ],
            ],
        ]);
    }

    private function seedSeventeenVillas(): void
    {
        $project = Project::updateOrCreate(
            ['title' => 'Seventeen Villas'],
            [
                'society_name' => 'Seventeen Villas',
                'developer_name' => 'Al Sadat Group',
                'type' => 'residential',
                'city' => 'Islamabad',
                'address' => '0 KM Rawalpindi Ring Road, near Girja Road & 17th Avenue, first society after '
                    .'Thalian Interchange',
                'contact_phone' => '+92 312 5236793',
                'contact_email' => 'info@seventeenvillas.com.pk',
                'description' => 'Ready-to-move double-story villas positioned as affordable luxury housing, '
                    .'with flexible financing starting at 5% down payment spread over 4-6 years.',
                'is_active' => true,
                'is_featured' => true,
            ]
        );

        // Sizes and starting prices for the three villa series are real, straight from
        // seventeenvillas.com. These are built double-story villas, not raw plots — bedroom/bathroom
        // counts scale with footprint but weren't published per-series, so they're illustrative.
        $this->replaceBlocks($project, [
            ['name' => 'Executive Series', 'plots' => [
                ['size_value' => 3.5, 'unit' => 'marla', 'category' => 'Villa', 'bedrooms' => 2, 'bathrooms' => 3, 'total_price' => 8_200_000],
            ]],
            ['name' => 'Prime Series', 'plots' => [
                ['size_value' => 4, 'unit' => 'marla', 'category' => 'Villa', 'bedrooms' => 3, 'bathrooms' => 4, 'total_price' => 10_600_000],
            ]],
            ['name' => 'Royal Series', 'plots' => [
                ['size_value' => 5.5, 'unit' => 'marla', 'category' => 'Villa', 'bedrooms' => 4, 'bathrooms' => 5, 'total_price' => 12_900_000],
            ]],
        ]);
    }

    private function seedFaisalTownPhase2(): void
    {
        $project = Project::updateOrCreate(
            ['title' => 'Faisal Town Phase 2'],
            [
                'society_name' => 'Faisal Town Phase 2',
                'developer_name' => 'Zedem International',
                'type' => 'mixed_use',
                'city' => 'Islamabad',
                'address' => 'Near Thalian Interchange, Lahore-Islamabad M-2 Motorway',
                'description' => 'A large-scale mixed-use development spanning roughly 90,000 kanals near the '
                    .'Thalian Interchange on the M-2 Motorway, with 35+ residential blocks (A–Y), an Overseas '
                    .'Enclave, a General Block, a Model Block, and dedicated Education City and Silicon Oasis '
                    .'zones.',
                'is_active' => true,
                'is_featured' => true,
            ]
        );

        // All three block names are real (faisaltown.org); the Overseas Enclave's six plot SIZES are
        // also real and published — only the prices attached to them are illustrative, same as
        // General/Model Block's plot sizes, which are entirely invented.
        $this->replaceBlocks($project, [
            [
                'name' => 'Overseas Enclave',
                'plots' => [
                    ['size_value' => 5.56, 'unit' => 'marla', 'category' => 'Residential Plot', 'total_price' => 6_200_000, 'booking_amount' => 650_000, 'installment_amount' => 145_000, 'installment_count' => 36, 'installment_frequency' => 'monthly'],
                    ['size_value' => 8, 'unit' => 'marla', 'category' => 'Residential Plot', 'total_price' => 8_800_000, 'booking_amount' => 950_000, 'installment_amount' => 205_000, 'installment_count' => 36, 'installment_frequency' => 'monthly'],
                    ['size_value' => 10.89, 'unit' => 'marla', 'category' => 'Residential Plot', 'total_price' => 11_500_000, 'booking_amount' => 1_250_000, 'installment_amount' => 265_000, 'installment_count' => 36, 'installment_frequency' => 'monthly'],
                    ['size_value' => 14.22, 'unit' => 'marla', 'category' => 'Residential Plot', 'total_price' => 14_800_000, 'booking_amount' => 1_600_000, 'installment_amount' => 340_000, 'installment_count' => 36, 'installment_frequency' => 'monthly'],
                    ['size_value' => 1, 'unit' => 'kanal', 'category' => 'Residential Plot', 'total_price' => 18_000_000, 'booking_amount' => 2_000_000, 'installment_amount' => 420_000, 'installment_count' => 36, 'installment_frequency' => 'monthly'],
                    ['size_value' => 2, 'unit' => 'kanal', 'category' => 'Residential Plot', 'total_price' => 32_000_000, 'booking_amount' => 3_500_000, 'installment_amount' => 750_000, 'installment_count' => 36, 'installment_frequency' => 'monthly'],
                ],
            ],
            [
                'name' => 'General Block',
                'plots' => [
                    ['size_value' => 5, 'unit' => 'marla', 'category' => 'Residential Plot', 'total_price' => 5_500_000, 'booking_amount' => 600_000, 'installment_amount' => 130_000, 'installment_count' => 36, 'installment_frequency' => 'monthly'],
                    ['size_value' => 10, 'unit' => 'marla', 'category' => 'Residential Plot', 'total_price' => 10_000_000, 'booking_amount' => 1_100_000, 'installment_amount' => 235_000, 'installment_count' => 36, 'installment_frequency' => 'monthly'],
                ],
            ],
            [
                'name' => 'Model Block',
                'plots' => [
                    ['size_value' => 8, 'unit' => 'marla', 'category' => 'Residential Plot', 'total_price' => 9_500_000, 'booking_amount' => 1_000_000, 'installment_amount' => 220_000, 'installment_count' => 36, 'installment_frequency' => 'monthly'],
                    ['size_value' => 1, 'unit' => 'kanal', 'category' => 'Residential Plot', 'total_price' => 19_000_000, 'booking_amount' => 2_050_000, 'installment_amount' => 440_000, 'installment_count' => 36, 'installment_frequency' => 'monthly'],
                ],
            ],
        ]);
    }

    private function seedEsmrHeightsFaisalHills(): void
    {
        // A distinct commercial/residential high-rise developed by Etimaad International and Skylite
        // Builders — physically located inside the Faisal Hills society, but a different project with
        // a different developer, not the same entity as the main Faisal Hills residential project.
        $project = Project::updateOrCreate(
            ['title' => 'ESMR Heights - Faisal Hills'],
            [
                'society_name' => 'Faisal Hills',
                'developer_name' => 'Etimaad International / Skylite Builders and Developers',
                'type' => 'mixed_use',
                'city' => 'Islamabad',
                'address' => 'Near Skylite 63, Faisal Hills, G.T. Road near Taxila, Islamabad',
                'description' => 'Etimaad Skylite Mall and Residencia (ESMR) is a mixed-use high-rise inside '
                    .'Faisal Hills near Skylite 63, combining ground-floor commercial shops with residential '
                    .'apartments above.',
                'is_active' => true,
                'is_featured' => true,
            ]
        );

        // The Mall/Residencia split is real (only "10% booking, easy installments" was published
        // generically); the specific sizes and prices below are illustrative. Unlike the raw-land
        // projects above, this is a high-rise — shops and apartments are conventionally sold by sqft,
        // not marla/kanal, and apartment units carry bedroom/bathroom counts.
        $this->replaceBlocks($project, [
            [
                'name' => 'Mall (Commercial)',
                'description' => 'Ground-floor commercial shops.',
                'plots' => [
                    ['size_value' => 300, 'unit' => 'sqft', 'category' => 'Commercial Shop', 'total_price' => 4_500_000, 'booking_amount' => 450_000, 'installment_amount' => 100_000, 'installment_count' => 36, 'installment_frequency' => 'monthly'],
                    ['size_value' => 550, 'unit' => 'sqft', 'category' => 'Commercial Shop', 'total_price' => 8_200_000, 'booking_amount' => 820_000, 'installment_amount' => 180_000, 'installment_count' => 36, 'installment_frequency' => 'monthly'],
                ],
            ],
            [
                'name' => 'Residencia (Apartments)',
                'description' => 'Residential apartments.',
                'plots' => [
                    ['size_value' => 750, 'unit' => 'sqft', 'category' => 'Apartment', 'bedrooms' => 1, 'bathrooms' => 1, 'total_price' => 8_000_000, 'booking_amount' => 800_000, 'installment_amount' => 175_000, 'installment_count' => 36, 'installment_frequency' => 'monthly'],
                    ['size_value' => 1100, 'unit' => 'sqft', 'category' => 'Apartment', 'bedrooms' => 2, 'bathrooms' => 2, 'total_price' => 11_000_000, 'booking_amount' => 1_100_000, 'installment_amount' => 240_000, 'installment_count' => 36, 'installment_frequency' => 'monthly'],
                    ['size_value' => 1450, 'unit' => 'sqft', 'category' => 'Apartment', 'bedrooms' => 3, 'bathrooms' => 3, 'total_price' => 15_500_000, 'booking_amount' => 1_550_000, 'installment_amount' => 340_000, 'installment_count' => 36, 'installment_frequency' => 'monthly'],
                ],
            ],
        ]);
    }
}
