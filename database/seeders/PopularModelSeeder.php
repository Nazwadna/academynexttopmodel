<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PopularModelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sample data untuk Popular Models
        $popularModels = [
            [
                'nama_model' => 'Natalie Portman',
                'deskripsi' => 'Top model with 10+ years experience in international fashion shows',
                'foto' => 'popular1.jpg',
                'instagram' => '@natalieportman',
                'twitter' => '@natalie_p_model',
                'status' => 'available',
                'order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_model' => 'Scarlett Johansson',
                'deskripsi' => 'Celebrity model known for luxury brand campaigns and magazine covers',
                'foto' => 'popular2.jpg',
                'instagram' => '@scarlett_j',
                'twitter' => '@scarlett_model',
                'status' => 'available',
                'order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_model' => 'Angelina Jolie',
                'deskripsi' => 'International model with humanitarian work and fashion influence',
                'foto' => 'popular3.jpg',
                'instagram' => '@angelina_jolie',
                'twitter' => '@angelina_j',
                'status' => 'available',
                'order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_model' => 'Jennifer Lawrence',
                'deskripsi' => 'Fashion model with experience in both commercial and editorial work',
                'foto' => 'popular4.jpg',
                'instagram' => '@jennifer_lawrence',
                'twitter' => '@jennifer_l',
                'status' => 'available',
                'order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_model' => 'Emma Stone',
                'deskripsi' => 'Versatile model known for creative fashion choices and brand partnerships',
                'foto' => 'popular5.jpg',
                'instagram' => '@emma_stone',
                'twitter' => '@emma_s_model',
                'status' => 'available',
                'order' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_model' => 'Margot Robbie',
                'deskripsi' => 'Professional model with international runway and campaign experience',
                'foto' => 'popular6.jpg',
                'instagram' => '@margot_robbie',
                'twitter' => '@margot_r',
                'status' => 'available',
                'order' => 6,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_model' => 'Gal Gadot',
                'deskripsi' => 'Fashion model with strong presence in luxury and commercial campaigns',
                'foto' => 'popular7.jpg',
                'instagram' => '@gal_gadot',
                'twitter' => '@gal_g_model',
                'status' => 'available',
                'order' => 7,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_model' => 'Zendaya Coleman',
                'deskripsi' => 'Trend-setting model known for bold fashion choices and brand collaborations',
                'foto' => 'popular8.jpg',
                'instagram' => '@zendaya',
                'twitter' => '@zendaya_model',
                'status' => 'available',
                'order' => 8,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('popular_models')->insert($popularModels);
    }
}
