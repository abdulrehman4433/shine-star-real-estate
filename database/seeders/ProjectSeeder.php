<?php

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        if (Project::query()->exists()) {
            return;
        }

        $projects = [
            [
                'title' => 'Bahria Town Phase 8',
                'society_name' => 'Bahria Town',
                'developer_name' => 'Bahria Town Pvt. Ltd.',
                'type' => 'residential',
                'city' => 'Rawalpindi',
                'description' => 'A master-planned residential community with parks, schools, and commercial hubs within walking distance of every block.',
                'lat' => 33.5651, 'lng' => 73.1348,
            ],
            [
                'title' => 'DHA Phase 9 Town',
                'society_name' => 'Defence Housing Authority',
                'developer_name' => 'DHA Lahore',
                'type' => 'residential',
                'city' => 'Lahore',
                'description' => 'An established, fully-developed residential town with paved roads, underground utilities, and a dedicated commercial zone.',
                'lat' => 31.4020, 'lng' => 74.4520,
            ],
            [
                'title' => 'Gulberg Corporate Centre',
                'society_name' => 'Gulberg Greens',
                'developer_name' => null,
                'type' => 'commercial',
                'city' => 'Islamabad',
                'description' => 'A commercial high-rise offering office suites and retail units in the heart of Islamabad\'s business district.',
                'lat' => 33.6844, 'lng' => 73.0479,
            ],
            [
                'title' => 'Al-Kabir Town Phase 3',
                'society_name' => 'Al-Kabir Town',
                'developer_name' => 'Al-Kabir Group',
                'type' => 'residential',
                'city' => 'Lahore',
                'description' => 'Affordable residential plots with flexible installment plans, close to the Lahore Ring Road interchange.',
                'lat' => 31.3705, 'lng' => 74.2201,
            ],
            [
                'title' => 'Emporium Mall Extension',
                'society_name' => 'Emporium Mall',
                'developer_name' => 'Al-Rehman Builders',
                'type' => 'commercial',
                'city' => 'Lahore',
                'description' => 'A retail and entertainment extension adding new shop floors and a rooftop food court to the existing mall complex.',
                'lat' => 31.4787, 'lng' => 74.4008,
            ],
            [
                'title' => 'Faisal Town Phase 2',
                'society_name' => 'Faisal Town',
                'developer_name' => null,
                'type' => 'mixed_use',
                'city' => 'Islamabad',
                'description' => 'A mixed-use development combining residential blocks with a central commercial boulevard and community park.',
                'lat' => 33.6255, 'lng' => 73.0654,
            ],
            [
                'title' => 'Blue World City',
                'society_name' => 'Blue World City',
                'developer_name' => 'Blue Group of Companies',
                'type' => 'residential',
                'city' => 'Rawalpindi',
                'description' => 'A themed residential community featuring a dedicated overseas block and a range of plot sizes for every budget.',
                'lat' => 33.6939, 'lng' => 73.1725,
            ],
            [
                'title' => 'Park View City',
                'society_name' => 'Park View City',
                'developer_name' => 'Vision Group',
                'type' => 'residential',
                'city' => 'Islamabad',
                'description' => 'Hillside residential plots with landscaped parks, a golf course view, and dedicated schooling zones.',
                'lat' => 33.5590, 'lng' => 72.9760,
            ],
            [
                'title' => 'Lake City',
                'society_name' => 'Lake City',
                'developer_name' => null,
                'type' => 'residential',
                'city' => 'Lahore',
                'description' => 'A fully-developed lakeside residential society with boutique commercial strips and waterfront plots.',
                'lat' => 31.3467, 'lng' => 74.1957,
            ],
            [
                'title' => 'Capital Smart City',
                'society_name' => 'Capital Smart City',
                'developer_name' => 'Future Developments Holdings',
                'type' => 'mixed_use',
                'city' => 'Rawalpindi',
                'description' => 'Pakistan\'s first smart city project, integrating residential, commercial, and technology-enabled infrastructure.',
                'lat' => 33.4372, 'lng' => 73.1201,
            ],
        ];

        $blockCatalog = [
            ['name' => 'Block A', 'description' => 'General residential block, closest to the main entrance.'],
            ['name' => 'Block B', 'description' => 'Corner plots and boulevard-facing residential block.'],
            ['name' => 'Commercial Block', 'description' => 'Retail and office plots along the main commercial avenue.'],
        ];

        $plotOptions = [
            ['size_value' => 5, 'unit' => 'marla', 'category' => 'Residential Plot', 'total_price' => 8500000, 'booking_amount' => 1000000, 'confirmation_amount' => 500000, 'installment_amount' => 175000, 'installment_count' => 36, 'installment_frequency' => 'monthly', 'possession_amount' => 1000000],
            ['size_value' => 8, 'unit' => 'marla', 'category' => 'Residential Plot', 'total_price' => 13500000, 'booking_amount' => 1500000, 'confirmation_amount' => 750000, 'installment_amount' => 275000, 'installment_count' => 36, 'installment_frequency' => 'monthly', 'possession_amount' => 1500000],
            ['size_value' => 1, 'unit' => 'kanal', 'category' => 'Residential Plot', 'total_price' => 24500000, 'booking_amount' => 2500000, 'confirmation_amount' => 1250000, 'installment_amount' => 475000, 'installment_count' => 36, 'installment_frequency' => 'monthly', 'possession_amount' => 2500000],
        ];

        foreach ($projects as $index => $data) {
            $project = Project::create($data + [
                'is_featured' => true,
                'is_active' => true,
                'order' => $index,
            ]);

            $blockIds = [];
            foreach (array_slice($blockCatalog, 0, 2) as $blockIndex => $blockData) {
                $block = $project->blocks()->create($blockData + ['order' => $blockIndex]);
                $blockIds[] = $block->id;
            }

            foreach ($plotOptions as $plotIndex => $plotData) {
                $project->plotSizes()->create($plotData + [
                    'project_block_id' => $blockIds[$plotIndex % count($blockIds)],
                    'order' => $plotIndex,
                ]);
            }
        }
    }
}
