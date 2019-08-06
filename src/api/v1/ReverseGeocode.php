<?php
/**
 * Created by PhpStorm.
 * User: mart
 * Date: 27/11/18
 * Time: 14:01
 */

namespace App\api\v1;

use App\api\v1\MyPostgres;
use App\api\v1\TermsOfUse;
use App\api\v1\Geocode;

/**
 * Class ReverseGeocode
 * @package App\api\v1
 *
 * Route pattern: /api/v1/reversegeocode?lat=AAA&lng=BBB&key=CCC
 */
class ReverseGeocode
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
     * Standard slim entry point for request /api/v1/reversegeocode
     * @param Slim\Http\Request $request
     * @param Slim\Http\Response $response
     * @param array $args
     */
    public function index($request, $response, $args)
    {
        $time_begin = microtime(true);
        $ipAddress = $request->getAttribute('ip_address');
        $queryLat = $request->getQueryParam('lat');
        $queryLng = $request->getQueryParam('lng');
        $queryKey = $request->getQueryParam('key');
        // check terms of use
        $response = $this->checkTermsOfUse($queryKey);
        if ($response['status']===200){
            $results = $this->findLocations($queryLat, $queryLng);
            $time_end = microtime(true);
            $response['msecs'] = round($time_end - $time_begin, 3)*1000;
            $response['addresses'] = $results;
        }
        $output = json_encode($response);
        return $output;
    }

    /**
     * Search for buildings located near by latitude, longitude
     * @param string $latitude
     * @param string $longitude
     */
    private function findLocations($latitude, $longitude)
    {
        $postgres = $this->container->mypostgres;
        $this->timestamps = $postgres->getTimestamps();

        $results = $postgres->findLocations((float)$latitude, (float)$longitude);
        $output = array();
        foreach ($results as $result){
            $countrycode = $result['countrycode'];
            $output[] = array(
                "address_id" => (string) $result['id'],
                "address_type" => "building",
                "streetnumber" => $result['number'],
                "street" => $result['street'],
                "postcode" => $result['postcode'],
                "city" => $result['city'],
                "country" => $this->countries[$countrycode]['country'],
                "countrycode" => $countrycode,
                "latitude" =>  $result['lat'],
                "longitude" => $result['lon'],
                "location_type" =>  GeoCode::RESULTTYPE_ROOFTOP,
                "display" =>  $result['street']." ".$result['number'].", ".
                    $result['postcode']." ".$result['city'].", ".
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