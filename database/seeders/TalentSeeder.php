<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TalentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sample data untuk Hire Models (Talents)
        $hireModels = [
            [
                'nama_model' => 'Alexandra Chen',
                'deskripsi' => 'Professional model available for commercial and fashion projects',
                'foto' => 'hire1.jpg',
                'instagram' => '@alexandra_chen',
                'twitter' => '@alex_c_model',
                'status' => 'available',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_model' => 'Brianna Williams',
                'deskripsi' => 'Versatile model with experience in various fashion campaigns',
                'foto' => 'hire2.jpg',
                'instagram' => '@brianna_w',
                'twitter' => '@brianna_model',
                'status' => 'available',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_model' => 'Catherine Martinez',
                'deskripsi' => 'Runway model available for fashion shows and editorial work',
                'foto' => 'hire3.jpg',
                'instagram' => '@catherine_m',
                'twitter' => '@catherine_model',
                'status' => 'available',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_model' => 'Diana Thompson',
                'deskripsi' => 'Commercial model with strong portfolio in brand campaigns',
                'foto' => 'hire4.jpg',
                'instagram' => '@diana_t',
                'twitter' => '@diana_thompson',
                'status' => 'available',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_model' => 'Elena Rodriguez',
                'deskripsi' => 'Fashion model specializing in luxury and high-end fashion',
                'foto' => 'hire5.jpg',
                'instagram' => '@elena_r',
                'twitter' => '@elena_model',
                'status' => 'available',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_model' => 'Fiona Anderson',
                'deskripsi' => 'Professional model available for photoshoot and video projects',
                'foto' => 'hire6.jpg',
                'instagram' => '@fiona_a',
                'twitter' => '@fiona_anderson',
                'status' => 'available',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_model' => 'Grace Kim',
                'deskripsi' => 'Asian model with international experience and multicultural appeal',
                'foto' => 'hire7.jpg',
                'instagram' => '@grace_kim',
                'twitter' => '@grace_k_model',
                'status' => 'available',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_model' => 'Hannah Lee',
                'deskripsi' => 'Emerging model with fresh look and professional attitude',
                'foto' => 'hire8.jpg',
                'instagram' => '@hannah_lee',
                'twitter' => '@hannah_l_model',
                'status' => 'available',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_model' => 'Isabella Brown',
                'deskripsi' => 'Versatile model experienced in both runway and commercial work',
                'foto' => 'hire9.jpg',
                'instagram' => '@isabella_b',
                'twitter' => '@isabella_brown',
                'status' => 'available',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_model' => 'Julia Wilson',
                'deskripsi' => 'Professional model with strong portfolio and international experience',
                'foto' => 'hire10.jpg',
                'instagram' => '@julia_w',
                'twitter' => '@julia_wilson',
                'status' => 'available',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('talents')->insert($hireModels);
    }
}
