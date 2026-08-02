<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class VerifyUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:verify-user {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify user account without OTP';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email {$email} not found!");
            return 1;
        }

        // Update user verification status
        $user->is_verified = true;
        $user->save();

        $this->info("User {$email} has been verified successfully!");
        $this->info("Name: {$user->name}");
        $this->info("Role: {$user->role}");
        $this->info("User ID: {$user->id_user}");
        $this->info("Verified at: " . now());

        return 0;
    }
}
