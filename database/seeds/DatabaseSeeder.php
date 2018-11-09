<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        $this->call('LocationTableSeeder');
        $this->call('SpecialtyTableSeeder');
        $this->call('SpecialtySubspecialtyTableSeeder');
        $this->call('AliasTableSeeder');
        $this->call('CreateSpecialtyAliasTableSeeder');
        $this->call('PhysicianTableSeeder');

        Model::reguard();
    }
}
