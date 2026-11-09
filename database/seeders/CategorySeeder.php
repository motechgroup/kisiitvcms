<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Kenya',
                'description' => 'National headlines, breaking events, and in-depth reporting from across the republic.',
                'display_order' => 1,
                'subcategories' => ['National News', 'Coast', 'Rift Valley', 'Central', 'Western'],
            ],
            [
                'name' => 'Politics',
                'description' => 'Parliamentary proceedings, elections, policy shifts, and political discourse.',
                'display_order' => 2,
                'subcategories' => ['Parliament', 'State House', 'Elections', 'Policy & Law'],
            ],
            [
                'name' => 'Counties',
                'description' => 'Devolution reporting, county assemblies, local governance, and grassroots news.',
                'display_order' => 3,
                'subcategories' => ['Nairobi', 'Kisii', 'Mombasa', 'Nakuru', 'Kisumu'],
            ],
            [
                'name' => 'Business',
                'description' => 'Financial markets, corporate updates, entrepreneurship, and economic indicators.',
                'display_order' => 4,
                'subcategories' => ['Markets', 'Economy', 'Banking', 'Real Estate', 'Agribusiness'],
            ],
            [
                'name' => 'World',
                'description' => 'International diplomacy, global crises, African affairs, and geopolitical developments.',
                'display_order' => 5,
                'subcategories' => ['East Africa', 'Africa', 'Americas', 'Europe', 'Middle East', 'Asia'],
            ],
            [
                'name' => 'Crime',
                'description' => 'Investigative exposes, court trials, law enforcement, and justice reporting.',
                'display_order' => 6,
                'subcategories' => ['Courts', 'Investigations', 'Security'],
            ],
            [
                'name' => 'Technology',
                'description' => 'Tech innovations, mobile payments, AI developments, telecom, and startups.',
                'display_order' => 7,
                'subcategories' => ['Fintech', 'Startups', 'Cybersecurity', 'Gadgets'],
            ],
            [
                'name' => 'Education',
                'description' => 'Curriculum reforms, university research, national examinations, and campus affairs.',
                'display_order' => 8,
                'subcategories' => ['Schools & CBC', 'Higher Education', 'Examinations'],
            ],
            [
                'name' => 'Sports',
                'description' => 'Athletics, football, rugby, premier league, and sporting achievements.',
                'display_order' => 9,
                'subcategories' => ['Athletics', 'Football', 'Rugby', 'Motorsport'],
            ],
            [
                'name' => 'Entertainment',
                'description' => 'Music, cinema, celebrity news, culture, and arts.',
                'display_order' => 10,
                'subcategories' => ['Music', 'Film & TV', 'Celebrity', 'Culture'],
            ],
            [
                'name' => 'Lifestyle',
                'description' => 'Wellness, travel, dining, fashion, personal growth, and human interest.',
                'display_order' => 11,
                'subcategories' => ['Health & Wellness', 'Travel', 'Food & Dining'],
            ],
            [
                'name' => 'Opinion',
                'description' => 'Columns, expert analyses, editorial viewpoints, and guest commentary.',
                'display_order' => 12,
                'subcategories' => ['Editorials', 'Guest Columns', 'Letters to the Editor'],
            ],
            [
                'name' => 'Video',
                'description' => 'Broadcast bulletins, live streams, video investigations, and digital shorts.',
                'display_order' => 13,
                'subcategories' => ['Bulletins', 'Special Reports', 'Interviews'],
            ],
        ];

        foreach ($categories as $catData) {
            $parent = Category::updateOrCreate(
                ['slug' => Str::slug($catData['name'])],
                [
                    'name' => $catData['name'],
                    'description' => $catData['description'],
                    'display_order' => $catData['display_order'],
                    'is_active' => true,
                    'show_in_menu' => true,
                ]
            );

            if (! empty($catData['subcategories'])) {
                foreach ($catData['subcategories'] as $index => $subName) {
                    Category::updateOrCreate(
                        ['slug' => Str::slug($catData['name'].'-'.$subName)],
                        [
                            'name' => $subName,
                            'parent_id' => $parent->id,
                            'display_order' => $index + 1,
                            'is_active' => true,
                            'show_in_menu' => true,
                        ]
                    );
                }
            }
        }
    }
}
