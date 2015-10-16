<?php

use Illuminate\Database\Seeder;

class SpecialtyTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        DB::table('specialties')->truncate();

        $specialties = $this->getCsv('specialties-eunice.csv');

        $this->seedTable($specialties);
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    }

    /**
     * Seed the specialties table.
     *
     * @return void
     * @author PJ
     */
    private function seedTable($data)
    {
        foreach ($data as $lineIndex => $row) {
            $specialty = App\Specialty::create([
                'code' => $row[0],
                'full' => $row[1],
                'is_parent' => ($row[2] == 'yes' ? 1: 0),
                'is_dupe' => ($row[3] == 'yes' ? 1: 0),
            ]);
        }
    }

    /**
     * Get CSV file and turn it into a multidimensional array.
     * File needs to be in /database/seeds/data directory.
     *
     * @return array
     * @author PJ
     */
    private function getCsv($filename)
    {
        $csv = \League\Csv\Reader::createFromPath(
            database_path() . '/seeds/data/' . $filename
        );

        return $csv->query();
    }

}

