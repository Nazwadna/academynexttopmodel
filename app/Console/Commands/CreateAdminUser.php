<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-admin-user {email} {password} {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new admin user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $password = $this->argument('password');
        $name = $this->argument('name');

        // Check if user already exists
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            $this->error("User with email {$email} already exists!");
            
            // Update existing user to admin if not already admin
            if ($existingUser->role !== 'admin') {
                $existingUser->role = 'admin';
                $existingUser->save();
                $this->info("Updated existing user {$email} to admin role!");
            }
            return 0;
        }

        // Create new admin user
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'admin',
            'is_verified' => true
        ]);

        $this->info("Admin user created successfully!");
        $this->info("Name: {$user->name}");
        $this->info("Email: {$user->email}");
        $this->info("Role: {$user->role}");
        $this->info("User ID: {$user->id_user}");
        
        return 0;
    }
}
