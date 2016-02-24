<?php

namespace App\Transformers;

use App\Physician;
use League\Fractal;
use League\Fractal\TransformerAbstract;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use Carbon\Carbon;

class PhysicianTransformer extends TransformerAbstract
{
    /**
     * List of resources possible to include
     *
     * @var  array
     */
    protected $availableIncludes = [];

    /**
     * List of resources to automatically include
     *
     * @var  array
     */
    protected $defaultIncludes = [];

    /**
     * Transform Physician instance into a generic array
     *
     * @param  Physician
     * @return array
     */
    public function transform(Physician $phys)
    {
        return [
            'id' => $phys['id'],
            'full_name' => $phys['full_name'],
            'prefix' => $phys['prefix'],
            'first_name' => $phys['first_name'],
            'middle_name' => $phys['middle_name'],
            'last_name' => $phys['last_name'],
            'suffix' => $phys['suffix'],
            //'designation' => $phys['designation'],
            'designation' => 'DO',
            'gender' => ($phys['Gender'] == 'F' ? 'Female' : 'Male'),
            'addr_1' => $phys['address_1'],
            'addr_2' => $phys['address_2'],
            'city' => $phys['City'],
            'state' => $phys['State_Province'],
            'zip' => $phys['Zip'],
            'phone' => $phys['Phone'],
            //'email' => $phys['Email'],
            'website' => 
              strpos($phys['website'], 'http') === false ? 'http://' : $phys['website'],
            'school' => $phys['COLLEGE_CODE'],
            'grad_year' => $phys['YearOfGraduation'],
            'experience' => $this->convertToExperience($phys['YearOfGraduation']),
            'fellow' => $phys['fellows'],
            'specialty' => $phys['PrimaryPracticeFocusArea'],
            'specialty_code' => $phys['PrimaryPracticeFocusCode'],
            'secondary' => $phys['SecondaryPracticeFocusArea'],
            'secondary_code' => $phys['SecondaryPracticeFocusCode'],
            'aoa_cert' => $phys['AOABoardCertified'],
            'abms_cert' => $phys['ABMS'],
            'aoa_certs' => $this->convertCertificationsToString([
                $phys['CERT1'], $phys['CERT2'], $phys['CERT3'], 
                $phys['CERT4'], $phys['CERT5'],
            ]),
            'lat' => $phys['lat'],
            'lon' => $phys['lon'],
            'distance' => $phys['distance'],
        ];
    }

    /**
     * Convert a graduation date into an experience category.
     *
     * Graduated 3  years ago: 2+ years experience
     * Graduated 6  years ago: 5+
     * Graduated 11 years ago: 10+
     * Graduated 16 years ago: 15+
     * Graduated 21 years ago: 20+
     * Graduated 26 years ago: 25+
     *
     * @param string 
     * @return string 
     */
    private function convertToExperience($gradYear)
    {
        // Guard against 'N/A'
        if (is_numeric($gradYear)) {

            $difference = Carbon::now()->year - 
                Carbon::create($gradYear)->year;

            if ($difference > 25) {
                return '25+ years';
            } elseif ($difference > 20) {
                return '20+ years';
            } elseif ($difference > 15) {
                return '15+ years';
            } elseif ($difference > 10) {
                return '10+ years';
            } elseif ($difference > 5) {
                return '5+ years';
            } 

            return '2+ years';

        }
    }

    /**
     * Convert an array of certifications into a human-readable string.
     *
     * @param array
     * @return string
     */
    private function convertCertificationsToString($certs)
    {
        $certs = array_filter($certs, function($cert) {
            return trim($cert) != '';
        });

        $count = count($certs);
        
        if ($count == 0) {
            return;
        } elseif ($count == 1) {
            return $certs[0];
        } 

        $partToReplace = ', ';
        $certsAsString = implode($partToReplace, $certs);
        
        $lastCommaPosition = strrpos($certsAsString, $partToReplace);

        if ($count == 2) {
            $replacement = ' and ';
        } else {
            $replacement = ', and ';
        }

        return substr_replace(
            $certsAsString, 
            $replacement,
            $lastCommaPosition, 
            strlen($partToReplace)
        );
    }
}
