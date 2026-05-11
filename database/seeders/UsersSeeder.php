<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            // Executive Board — city_wide
            ['name' => 'Emran Gazi',          'email' => 'president@isgh.org',         'role' => 'executive_board', 'access_level' => 'city_wide', 'zone' => null,        'center' => null],
            ['name' => 'Mohiuddin Syed',       'email' => 'vice.president@isgh.org',    'role' => 'executive_board', 'access_level' => 'city_wide', 'zone' => null,        'center' => null],
            ['name' => 'Muhammad Ali',         'email' => 'general.secretary@isgh.org', 'role' => 'executive_board', 'access_level' => 'city_wide', 'zone' => null,        'center' => null],
            ['name' => 'Imran Nathani',        'email' => 'treasurer@isgh.org',         'role' => 'executive_board', 'access_level' => 'city_wide', 'zone' => null,        'center' => null],

            // Zone Directors — zone level
            ['name' => 'Muhammad Usman Khan',  'email' => 'dir.nz@isgh.org',            'role' => 'zone_director',   'access_level' => 'zone',      'zone' => 'North',     'center' => null],
            ['name' => 'Nusrat Ullah Mir',     'email' => 'dir.nwz@isgh.org',           'role' => 'zone_director',   'access_level' => 'zone',      'zone' => 'Northwest', 'center' => null],
            ['name' => 'Shaizad Chatriwala',   'email' => 'dir.sz@isgh.org',            'role' => 'zone_director',   'access_level' => 'zone',      'zone' => 'South',     'center' => null],
            ['name' => 'Ahmad Shaikh',         'email' => 'dir.swz@isgh.org',           'role' => 'zone_director',   'access_level' => 'zone',      'zone' => 'Southwest', 'center' => null],
            ['name' => 'Sohail Zafar',         'email' => 'dir.sez@isgh.org',           'role' => 'zone_director',   'access_level' => 'zone',      'zone' => 'Southeast', 'center' => null],

            // Associate Directors — center level
            ['name' => 'Badar Alam',           'email' => 'ad.adelroad@isgh.org',       'role' => 'associate_director', 'access_level' => 'center', 'zone' => 'North',     'center' => 'Adel Road'],
            ['name' => 'Hassan Abbasi',        'email' => 'ad.cypress@isgh.org',        'role' => 'associate_director', 'access_level' => 'center', 'zone' => 'North',     'center' => 'Cypress'],
            ['name' => 'Abdurrehman Ansari',   'email' => 'ad.champions@isgh.org',      'role' => 'associate_director', 'access_level' => 'center', 'zone' => 'North',     'center' => 'Champions'],
            ['name' => 'Muhammad Zaman',       'email' => 'ad.woodlands@isgh.org',      'role' => 'associate_director', 'access_level' => 'center', 'zone' => 'North',     'center' => 'Woodlands'],
            ['name' => 'Sameera Thakore',      'email' => 'ad.springbranch@isgh.org',   'role' => 'associate_director', 'access_level' => 'center', 'zone' => 'Northwest', 'center' => 'Spring Branch'],
            ['name' => 'Arif Ali Syed',        'email' => 'ad.bearcreek@isgh.org',      'role' => 'associate_director', 'access_level' => 'center', 'zone' => 'Northwest', 'center' => 'Bear Creek'],
            ['name' => 'Yasin Sahib',          'email' => 'ad.katy@isgh.org',           'role' => 'associate_director', 'access_level' => 'center', 'zone' => 'Northwest', 'center' => 'Katy'],
            ['name' => 'Mubeen Khumawala',     'email' => 'ad.riveroaks@isgh.org',      'role' => 'associate_director', 'access_level' => 'center', 'zone' => 'Southwest', 'center' => 'River Oaks'],
            ['name' => 'Aheed Mohiuddin',      'email' => 'ad.newterritory@isgh.org',   'role' => 'associate_director', 'access_level' => 'center', 'zone' => 'Southwest', 'center' => 'New Territory'],
            ['name' => 'Najeed Ismail',        'email' => 'ad.synott@isgh.org',         'role' => 'associate_director', 'access_level' => 'center', 'zone' => 'Southwest', 'center' => 'Synott'],
            ['name' => 'Chadi Ayoub',          'email' => 'ad.missionbend@isgh.org',    'role' => 'associate_director', 'access_level' => 'center', 'zone' => 'Southwest', 'center' => 'Mission Bend'],
            ['name' => 'Ramadan Younes',       'email' => 'ad.pearland@isgh.org',       'role' => 'associate_director', 'access_level' => 'center', 'zone' => 'Southeast', 'center' => 'Pearland'],
            ['name' => 'Nauman Ali Shah',      'email' => 'ad.hwy3@isgh.org',           'role' => 'associate_director', 'access_level' => 'center', 'zone' => 'Southeast', 'center' => 'HWY3'],
            ['name' => 'Shahdat Hossain',      'email' => 'ad.brandlane@isgh.org',      'role' => 'associate_director', 'access_level' => 'center', 'zone' => 'South',     'center' => 'Brand Lane'],
            ['name' => 'Aftab Chowdhry',       'email' => 'ad.ayesha@isgh.org',         'role' => 'associate_director', 'access_level' => 'center', 'zone' => 'South',     'center' => 'Ayesha'],
        ];

        $logLines   = [];
        $logLines[] = str_pad('Name', 25) . ' | ' . str_pad('Email', 35) . ' | Password';
        $logLines[] = str_repeat('-', 25) . '-+-' . str_repeat('-', 35) . '-+-' . str_repeat('-', 16);

        foreach ($users as $data) {
            $plain    = $this->generatePassword();
            $existing = User::where('email', $data['email'])->first();

            if ($existing) {
                $existing->update(array_merge($data, [
                    'password'             => Hash::make($plain),
                    'must_change_password' => true,
                    'is_active'            => true,
                ]));
            } else {
                User::create(array_merge($data, [
                    'password'             => Hash::make($plain),
                    'must_change_password' => true,
                    'is_active'            => true,
                ]));
            }

            $logLines[] = str_pad($data['name'], 25) . ' | ' . str_pad($data['email'], 35) . ' | ' . $plain;
        }

        file_put_contents(
            storage_path('logs/seeded_passwords.txt'),
            implode(PHP_EOL, $logLines) . PHP_EOL
        );

        $this->command->info('Seeded ' . count($users) . ' users. Plain-text passwords written to storage/logs/seeded_passwords.txt');
    }

    private function generatePassword(int $length = 12): string
    {
        $upper   = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower   = 'abcdefghjkmnpqrstuvwxyz';
        $digits  = '23456789';
        $symbols = '!@#$%^&*';

        // Guarantee at least one of each character class
        $password  = $upper[random_int(0, strlen($upper) - 1)];
        $password .= $lower[random_int(0, strlen($lower) - 1)];
        $password .= $digits[random_int(0, strlen($digits) - 1)];
        $password .= $symbols[random_int(0, strlen($symbols) - 1)];

        $all = $upper . $lower . $digits . $symbols;
        for ($i = 4; $i < $length; $i++) {
            $password .= $all[random_int(0, strlen($all) - 1)];
        }

        // Shuffle so the guaranteed chars aren't always at the front
        return str_shuffle($password);
    }
}
