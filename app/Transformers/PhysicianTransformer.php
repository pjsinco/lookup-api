<?php

namespace App\Transformers;

use App\Physician;
use League\Fractal;
use League\Fractal\TransformerAbstract;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;

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
            'gender' => $phys['Gender'],
            'addr_1' => $phys['address_1'],
            'addr_2' => $phys['address_2'],
            'city' => $phys['City'],
            'state' => $phys['State_Province'],
            'zip' => $phys['Zip'],
            'phone' => $phys['Phone'],
            'email' => $phys['Email'],
            'website' => $phys['website'],
            'school' => $phys['COLLEGE_CODE'],
            'grad_year' => $phys['YearOfGraduation'],
            'fellow' => $phys['fellows'],
            'specialty' => $phys['PrimaryPracticeFocusArea'],
            'specialty_code' => $phys['PrimaryPracticeFocusCode'],
            'secondary' => $phys['SecondaryPracticeFocusArea'],
            'secondary_code' => $phys['SecondaryPracticeFocusCode'],
            'aoa_cert' => $phys['AOABoardCertified'],
            'abms_cert' => $phys['ABMS'],
            'aoa_cert_1' => $phys['CERT1'],
            'aoa_cert_2' => $phys['CERT2'],
            'aoa_cert_3' => $phys['CERT3'],
            'aoa_cert_5' => $phys['CERT4'],
            'aoa_cert_5' => $phys['CERT5'],
            'lat' => $phys['lat'],
            'lon' => $phys['lon'],
            'distance' => $phys['distance'],
        ];
    }

}
