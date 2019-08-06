<?php
/**
 * Created by PhpStorm.
 * User: mart
 * Date: 27/11/18
 * Time: 14:01
 */

namespace App\api\v1;

use App\api\v1\MyPostgres;
use App\api\v1\GeoTraits;
use App\api\v1\Geocode;

/**
 * Class ReverseGeocode
 * @package App\api\v1
 *
 * Route pattern: /api/v1/reversegeocode?lat=AAA&lng=BBB&key=CCC
 */
class ReverseGeocode
{
    use GeoTraits{
        checkTermsOfUse as protected;
        getCountries as protected;
        convertPersitedInfos as protected;
        getVersion as protected;
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
        $this->countries = $this->getCountries();
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
        return $this->convertPersitedInfos(
            $results,
            GeoCode::ADDRESSTYPE_BUILDING,
            GeoCode::RESULTTYPE_ROOFTOP
        );
    }
}