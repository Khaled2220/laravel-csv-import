<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
      // $this->call([
      //      UserSeeder::class
      // ]);

    //DB::tabel('user')->insert([
    //        'name'=>str::random(15),
    //        'email'=>str::random(25).'@example.com',
    //        'password'=>Hash::make('password')
    //]);

    //User::factory()->count(500)->hasImport(3)->create();

    }
}
