<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class ImportUsersFromUrlCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:import-users-from-url';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $url = $this->ask('Enter the URL:');
        if (empty($url)) {
            $this->error('URL is required.');
            return Command::FAILURE;
        }
        $limit = (int) $this->ask('Enter the limit:');

        if ($limit <= 0) {
            $this->error('Enter a valid limit.');
            return Command::FAILURE;
        }

        $response = Http::get($url)->json();


        if (empty($response) || !is_array($response)) {
            $this->error('No users found at the provided URL.');
            return Command::FAILURE;
        }

        // NB : No need to validate the data as they will alwas be in the format shared.
        $usersToImport = array_slice($response, 0, $limit);

        foreach ($usersToImport as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make(rand(100000, 999999))
                ]
            );
        }

        $this->info(count($usersToImport) . ' Users imported successfully.');
        return Command::SUCCESS;
    }
}
