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
use App\api\v1\MyPostgres;

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

    /** @var array $countries */
    protected  $countries;

    /** @var array $timestamps */
    protected  $timestamps;

    /** @var Container $container */
    protected   $container;

    public function __construct($container)
    {
        $this->container = $container;
        $this->countries = array(
            'CH' => array(
                'country' => 'Schweiz',
                'source' => 'https://map.geo.admin.ch/?layers=ch.bfs.gebaeude_wohnungs_register',
                'licence' => 'https://www.admin.ch/gov/de/start/dokumentation/medienmitteilungen.msg-id-66999.html'
            ),
            'LI' => array(
                'country' => 'Liechetenstein',
                'source' => 'http://geodaten.llv.li/geoportal/gebaeudeidentifikator.html',
                'licence' => 'https://github.com/openaddresses/openaddresses/blob/master/LICENSE'
            )
        );
    }

    /**
     * Standard slim entry point for request /api/v1/geocode
     * @param Slim\Http\Request $request
     * @param Slim\Http\Response $response
     * @param array $args
     */
    public function index($request, $response, $args)
    {
        $time_begin = microtime(true);
        $ipAddress = $request->getAttribute('ip_address');
        $queryAdr = $request->getQueryParam('adr');
        $queryKey = $request->getQueryParam('key');
        // check terms of use
        $response = $this->checkTermsOfUse($queryKey);
        // check the requestors approximate location
        $geoLocation = new GeoLocation($ipAddress);
        // parse the users search term
        $searchTerm = new SearchTerm($queryAdr);
        if ($searchTerm->code!==200){
            $response = array(
                'status' => $searchTerm->code,
                'status_text' => $searchTerm->message
            );
        }
        // find the term in the database
        $results = $this->findAddress($searchTerm, $geoLocation);
        if ($searchTerm->code===200){
            $time_end = microtime(true);
            $response['msecs'] = round($time_end - $time_begin, 3)*1000;
            $response['addresses'] = $results;
        }
        else{
            $response = array(
                'status' => $searchTerm->code,
                'status_text' => $searchTerm->message
            );
        }
        // build the response
        $output = json_encode($response);
        return $output;
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
        $postgres = $this->container->mypostgres;
        $this->timestamps = $postgres->getTimestamps();

        if ((!empty($searchTerm->street))&&
            (empty($searchTerm->streetnumber))&&
            (empty($searchTerm->postcode))&&
            (empty($searchTerm->city))&&
            (empty($searchTerm->countrycode))) {
            $results = $postgres->findStreet($searchTerm, $geolocation);
        }
        else{
            $results = $postgres->findAddress($searchTerm, $geolocation);
        }
        $output = array();
        foreach ($results as $input){
            $countrycode = $input['countrycode'];
            $output[] = array(
                "address_id" => (string) $input['id'],
                "address_type" => "building",
                "streetnumber" => $input['number'],
                "street" => $input['street'],
                "postcode" => $input['postcode'],
                "city" => $input['city'],
                "country" => $this->countries[$countrycode]['country'],
                "countrycode" => $countrycode,
                "latitude" =>  $input['lat'],
                "longitude" => $input['lon'],
                "location_type" =>  "rooftop",
                "display" =>  $input['street']." ".$input['number'].", ".
                    $input['postcode']." ".$input['city'].", ".
                    $this->countries[$countrycode]['country'],
                "source" =>  $this->countries[$countrycode]['source'],
                "licence" =>  $this->countries[$countrycode]['licence'],
                "version" =>  "Data packaged ".$this->getVersion($countrycode)
            );
        }
        return $output;
    }

    /**
     * Get the persisted download version date from the array
     * @param string $countrycode
     * @return string with timestamp
     */
    private function getVersion($countrycode)
    {
        foreach ($this->timestamps as $timestamp){
            if ($timestamp['countrycode']===$countrycode){
                return $timestamp['timestamp'];
            }
        }
        return 'n/a';
    }

}