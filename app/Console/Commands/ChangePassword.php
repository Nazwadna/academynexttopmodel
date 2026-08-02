<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ChangePassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:change-password {email} {password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change user password by email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $newPassword = $this->argument('password');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email {$email} not found!");
            return 1;
        }

        $user->password = Hash::make($newPassword);
        $user->save();

        $this->info("Password for {$email} has been changed successfully!");
        $this->info("User: {$user->name} (ID: {$user->id_user})");
        
        return 0;
    }
}
