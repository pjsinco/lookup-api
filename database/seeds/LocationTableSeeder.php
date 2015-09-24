<?php

use Illuminate\Database\Seeder;

class LocationTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('locations')->truncate();

        $locations = $this->getCsv('locations.csv');
        $this->seedTable($locations);
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
            $specialty = App\Location::create([
                'zip' => $row[0],
                'city' => $row[1],
                'state' => $row[2],
                'lat' => $row[3],
                'lon' => $row[4],
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

