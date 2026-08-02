<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OurTalentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sample data untuk New Models (Our Talents)
        $ourTalents = [
            [
                'nama_model' => 'Luna Anderson',
                'deskripsi' => 'New face in fashion modeling with fresh look and versatile portfolio',
                'foto' => 'newmodel1.jpg',
                'instagram' => '@lunaanderson',
                'twitter' => '@luna_a_model',
                'status' => 'available',
                'order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_model' => 'Zara Kim',
                'deskripsi' => 'Emerging model specializing in streetwear and urban fashion',
                'foto' => 'newmodel2.jpg',
                'instagram' => '@zarakim_official',
                'twitter' => '@zara_kim',
                'status' => 'available',
                'order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_model' => 'Maya Patel',
                'deskripsi' => 'Professional model with background in both commercial and editorial work',
                'foto' => 'newmodel3.jpg',
                'instagram' => '@mayapatel_model',
                'twitter' => '@maya_patel',
                'status' => 'available',
                'order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_model' => 'Chloe Rodriguez',
                'deskripsi' => 'Fashion model with experience in runway and photoshoot campaigns',
                'foto' => 'newmodel4.jpg',
                'instagram' => '@chloe_rodriguez',
                'twitter' => '@chloe_r',
                'status' => 'available',
                'order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_model' => 'Ava Thompson',
                'deskripsi' => 'Versatile model known for creative poses and professional attitude',
                'foto' => 'newmodel5.jpg',
                'instagram' => '@ava_thompson',
                'twitter' => '@ava_t_model',
                'status' => 'available',
                'order' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_model' => 'Sophie Lee',
                'deskripsi' => 'Asian model with international experience and multicultural appeal',
                'foto' => 'newmodel6.jpg',
                'instagram' => '@sophie_lee_model',
                'twitter' => '@sophie_lee',
                'status' => 'available',
                'order' => 6,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_model' => 'Emma Wilson',
                'deskripsi' => 'Professional model with strong portfolio in fashion and commercial work',
                'foto' => 'newmodel7.jpg',
                'instagram' => '@emma_wilson',
                'twitter' => '@emma_w_model',
                'status' => 'available',
                'order' => 7,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_model' => 'Olivia Brown',
                'deskripsi' => 'Emerging talent with fresh perspective and unique modeling style',
                'foto' => 'newmodel8.jpg',
                'instagram' => '@olivia_brown',
                'twitter' => '@olivia_b_model',
                'status' => 'available',
                'order' => 8,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('our_talents')->insert($ourTalents);
    }
}
