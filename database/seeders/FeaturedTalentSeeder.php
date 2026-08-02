<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FeaturedTalentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sample data untuk Face of Models
        $featuredTalents = [
            [
                'nama_model' => 'Oliviarie Summer',
                'deskripsi' => 'Professional model with 5+ years experience in fashion and commercial modeling',
                'foto' => 'model1.jpg',
                'order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_model' => 'Amanda Chen',
                'deskripsi' => 'Runway model specializing in haute couture and ready-to-wear fashion',
                'foto' => 'model2.jpg',
                'order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_model' => 'Sophia Williams',
                'deskripsi' => 'Commercial model with experience in print and digital campaigns',
                'foto' => 'model3.jpg',
                'order' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_model' => 'Isabella Martinez',
                'deskripsi' => 'Fashion model known for editorial work and brand collaborations',
                'foto' => 'model4.jpg',
                'order' => 4,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_model' => 'Emily Johnson',
                'deskripsi' => 'Versatile model experienced in both runway and photoshoot modeling',
                'foto' => 'model5.jpg',
                'order' => 5,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_model' => 'Natasha Rose',
                'deskripsi' => 'Professional model with international experience and portfolio',
                'foto' => 'model6.jpg',
                'order' => 6,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_model' => 'Jessica Taylor',
                'deskripsi' => 'Commercial and fashion model with strong social media presence',
                'foto' => 'model7.jpg',
                'order' => 7,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_model' => 'Megan Anderson',
                'deskripsi' => 'Runway model specializing in contemporary fashion and luxury brands',
                'foto' => 'model8.jpg',
                'order' => 8,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('featured_talents')->insert($featuredTalents);
    }
}
