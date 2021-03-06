<?php

use Illuminate\Database\Seeder;
use Elit\AggregateReporter;
use Elit\Hasher;

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

        $physicians = $this->getCsv('physicians-2018-11-07.csv');
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

            $aliases = AggregateReporter::getAliases([$row[17], $row[19]]);

            DB::table('physicians')->insert([
                'id'                         => Hasher::createId($row[0], $row[3], $row[4], $row[5]),
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
                'Gender'                     => $row[28],
                'CERT1'                      => $row[29],
                'CERT2'                      => $row[30],
                'CERT3'                      => $row[31],
                'CERT4'                      => $row[32],
                'CERT5'                      => $row[33],
                'lat'                        => $row[34],
                'lon'                        => $row[35],
                'geo_confidence'             => $row[36],
                'geo_city'                   => $row[37],
                'geo_state'                  => $row[38],
                'geo_matches'                => ($row[39] == 'True' ? 1 : 0),
                'alias_1'                    => (empty($aliases[0]) ? null : $aliases[0]->id),
                'alias_2'                    => (empty($aliases[1]) ? null : $aliases[1]->id),
                'alias_3'                    => (empty($aliases[2]) ? null : $aliases[2]->id),
                'alias_4'                    => (empty($aliases[3]) ? null : $aliases[3]->id),
                'alias_5'                    => (empty($aliases[4]) ? null : $aliases[4]->id),
                'alias_6'                    => (empty($aliases[5]) ? null : $aliases[5]->id),
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

