<?php

use Illuminate\Database\Seeder;

class CreateSpecialtyAliasTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        DB::table('specialty_alias')->truncate();
        $mappings = $this->getCsv('specialty-alias-mapping.csv');
        $this->seedTable($mappings);

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
            try {
                $specialty_id = App\Specialty::find($row[0]);
                $alias_id = App\Alias::find($row[1]);
                DB::table('specialty_alias')->insert (
                    [
                        'specialty_id' => $specialty_id->code,
                        'alias_id' => $alias_id->id,
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
