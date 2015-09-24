<?php

use Illuminate\Database\Seeder;

class PhysicianTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('physicians')->truncate();

        $physicians = $this->getCsv('physicians.csv');
        $this->seedTable($physicians);
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
            $specialty = App\Physician::create([
                'aoa_mem_id'                 => $row[0],
                'full_name'                  => $row[1],
                'prefix'                     => $row[2],
                'first_name'                 => $row[3],
                'middle_name'                => $row[4],
                'last_name'                  => $row[5],
                'suffix'                     => $row[6],
                'designation'                => $row[7],
                'SortColumn'                 => $row[8],
                'MemberStatus'               => $row[9],
                'City'                       => $row[10],
                'State_Province'             => $row[11],
                'Zip'                        => $row[12],
                'Country'                    => $row[13],
                'COLLEGE_CODE'               => $row[14],
                'YearOfGraduation'           => $row[15],
                'fellows'                    => $row[16],
                'PrimaryPracticeFocusCode'   => $row[17],
                'PrimaryPracticeFocusArea'   => $row[18],
                'SecondaryPracticeFocusCode' => $row[19],
                'SecondaryPracticeFocusArea' => $row[20],
                'website'                    => $row[21],
                'AOABoardCertified'          => ($row[22] == 'YES' ? 1 : 0),
                'address_1'                  => $row[23],
                'address_2'                  => $row[24],
                'Phone'                      => $row[25],
                'Email'                      => $row[26],
                'ABMS'                       => ($row[27] == 'YES' ? 1 : 0),
                'lat'                        => $row[28],
                'lon'                        => $row[29],
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
