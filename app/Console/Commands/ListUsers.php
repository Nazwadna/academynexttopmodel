<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class ListUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:list-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all users in the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $users = User::all();

        if ($users->isEmpty()) {
            $this->error("No users found in the database!");
            return 1;
        }

        $this->info("Found {$users->count()} users:");
        $this->info(str_repeat('-', 80));
        $this->info(sprintf("%-5s %-30s %-25s %-10s %-10s", 'ID', 'Name', 'Email', 'Role', 'Has Pass'));
        $this->info(str_repeat('-', 80));

        foreach ($users as $user) {
            $hasPassword = !empty($user->password) ? 'Yes' : 'No';
            $this->info(sprintf(
                "%-5d %-30s %-25s %-10s %-10s",
                $user->id_user,
                substr($user->name, 0, 29),
                substr($user->email, 0, 24),
                $user->role ?? 'N/A',
                $hasPassword
            ));
        }

        return 0;
    }
}
