<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Create admin user separately
        User::create([
            'username' => 'admin23',
            'email' => 'admin3@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'admin',
            'locale' => 'en',
            'remember_token' => Str::random(10),
        ]);

        $this->command->info('Created admin user');

        // Prepare batch users
        $batchSize = 100; // Insert 100 users at a time
        $usedEmails = ['admin@example.com'];
        $usedUsernames = ['admin2'];
        $users = [];

        $this->command->info('Generating user data...');

        // Generate 999 random users
        for ($i = 0; $i < 999; $i++) {
            // Generate unique username
            do {
                $username = $faker->userName();
            } while (in_array($username, $usedUsernames));
            $usedUsernames[] = $username;

            // Generate unique email
            do {
                $email = $faker->unique()->safeEmail();
            } while (in_array($email, $usedEmails));
            $usedEmails[] = $email;

            // Randomly decide if user is verified (80% are verified)
            $isVerified = $faker->boolean(80);

            // Generate created_at timestamp
            $createdAt = $faker->dateTimeBetween('-2 years', 'now');

            // Determine updated_at timestamp
            $updatedAt = $faker->boolean(70) ? $createdAt : $faker->dateTimeBetween($createdAt, 'now');

            // Add to users batch
            $users[] = [
                'username' => $username,
                'email' => $email,
                'email_verified_at' => $isVerified ? $faker->dateTimeBetween('-1 year', 'now') : null,
                'password' => Hash::make('password'), // Same password for all
                'role' => $faker->boolean(2) ? 'admin' : 'user',
                'locale' => $faker->randomElement(['en', 'lt']),
                'remember_token' => $isVerified ? Str::random(10) : null,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ];

            // Insert batch when it reaches the batch size
            if (count($users) === $batchSize) {
                DB::table('users')->insert($users);
                $this->command->info("Inserted batch of {$batchSize} users. Progress: " . ($i + 1) . "/999");
                $users = []; // Reset batch
            }
        }

        // Insert any remaining users
        if (count($users) > 0) {
            DB::table('users')->insert($users);
            $this->command->info("Inserted final batch of " . count($users) . " users");
        }

        $this->command->info('User seeding completed successfully!');
    }
}
