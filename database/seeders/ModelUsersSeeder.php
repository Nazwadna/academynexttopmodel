<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Talent;

class ModelUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil semua model dari tabel models
        $talents = Talent::all();
        $usedPins = [];

        foreach ($talents as $talent) {
            // Cek apakah user model ini sudah ada
            $user = User::where('name', $talent->nama_model)->where('role', 'model')->first();
            if ($user) {
                continue;
            }

            // Generate PIN unik 6 digit
            do {
                $pin = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            } while (in_array($pin, $usedPins, true));
            $usedPins[] = $pin;

            User::create([
                'name' => $talent->nama_model,
                'email' => null, // akan diisi saat registrasi
                'password' => Hash::make(Str::random(12)), // placeholder
                'role' => 'model',
                'number_phone' => null,
                'pin' => $pin,
                'is_verified' => 0,
            ]);
        }
    }
}


