<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-user {name} {email} {password} {role=client}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new user with password';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $email = $this->argument('email');
        $password = $this->argument('password');
        $role = $this->argument('role');

        // Check if user already exists
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            $this->error("User with email {$email} already exists!");
            $this->info("Existing user: {$existingUser->name} (ID: {$existingUser->id_user})");
            return 1;
        }

        // Create new user
        $user = new User();
        $user->name = $name;
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->role = $role;
        $user->save();

        $this->info("User created successfully!");
        $this->info("Name: {$user->name}");
        $this->info("Email: {$user->email}");
        $this->info("Role: {$user->role}");
        $this->info("User ID: {$user->id_user}");
        $this->info("Password: {$password}");

        return 0;
    }
}
