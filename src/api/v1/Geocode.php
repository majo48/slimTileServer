<?php
/**
 * Created by PhpStorm.
 * User: mart
 * Date: 27/11/18
 * Time: 13:42
 */

namespace App\api\v1;

use App\api\v1\TermsOfUse;
use App\api\v1\GeoLocation;
use App\api\v1\SearchTerm;

/**
 * Class Geocode
 * @package App\api\v1
 *
 * Route pattern: /api/v1/geocode?adr=AAA&key=BBB
 * where AAA is coded with plus signs or %20 for spaces,
 * format: application/x-www-form-urlencoded.
 */
class Geocode
{
    use TermsOfUse{
        checkTermsOfUse as protected;
    }

    /** @var Container $container */
    protected   $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function index($request, $response, $args)
    {
        $ipAddress = $request->getAttribute('ip_address');
        $queryAdr = $request->getQueryParam('adr');
        $queryKey = $request->getQueryParam('key');

        $response = $this->checkTermsOfUse($queryKey);

        $geoLocation = new GeoLocation($ipAddress);

        $searchTerm = new SearchTerm($queryAdr);
        if ($searchTerm->code!==200){
            $response = array(
                'status' => $searchTerm->code,
                'status_text' => $searchTerm->message
            );
        }

        $results = $this->findAddress($searchTerm, $geoLocation);
        if ($searchTerm->code===200){
            $response['addresses'] = $results;
        }
        else{
            $response = array(
                'status' => $searchTerm->code,
                'status_text' => $searchTerm->message
            );
        }

        $output = json_encode($response);
        echo $output;
    }

    /**
     * Search for the address in database 'gis' table 'gwr'
     *
     * @param SearchTerm $searchTerm
     * @param GeoLocation $geolocation
     * @return array
     */
    private function findAddress($searchTerm, $geolocation)
    {
        return $this->getTestAddress();
    }

    private function getTestAddress()
    {
        return array(
            array(
                "address_id" =>  "123456789",
                "address_type" =>  "building",
                "streetnumber" =>  "38",
                "street" =>  "Seemattstrasse",
                "postcode" =>  "6333",
                "city" =>  "Hünenberg See",
                "country" =>  "Schweiz",
                "countrycode" =>  "CH",
                "latitude" =>  47.173224,
                "longitude" =>  8.453082,
                "location_type" =>  "rooftop",
                "display" =>  "Seemattstrasse 38, 6333 Hünenberg See, Schweiz",
                "source" =>  "[https => //map.geo.admin.ch/..._wohnungs_register)",
                "licence" =>  "[https => //www.admin.ch/...msg-id-66999.html",
                "version" =>  "Data packaged around 2018-11-03 by OpenAddresses ..."
            )
        );
    }
}