<?php

use Illuminate\Database\Seeder;

class SpecialtySubspecialtyTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('specialty_subspecialty')->truncate();
        $specialties = $this->getCsv('specialties-mapping.csv');
        $this->seedTable($specialties);
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
            try {
                $specialty = App\Specialty::find($row[0]);
                $subspecialty = App\Specialty::find($row[1]);
                DB::table('specialty_subspecialty')->insert (
                    [
                        'specialty_id' => $specialty->code,
                        'subspecialty_id' => $subspecialty->code,
                    ]
                );
            } catch (ErrorException $ee) {
                
            }
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
