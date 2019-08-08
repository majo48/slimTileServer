<?php
/**
 * Created by PhpStorm.
 * User: mart
 * Date: 27/11/18
 * Time: 13:42
 */

namespace App\api\v1;

use App\api\v1\GeoLocation;
use App\api\v1\MyPostgres;
use App\api\v1\SearchTerm;
use App\api\v1\GeoTraits;

/**
 * Class Geocode
 * @package App\api\v1
 *
 * Route pattern: /api/v1/geocode?adr=AAA&key=BBB
 * where AAA is coded with plus signs or %20 for spaces,
 * format: application/x-www-form-urlencoded.
 *
 * Copyright (C) 2019  Martin Jonasse, see LICENCE.md.
 */
class Geocode
{
    use GeoTraits{
        checkTermsOfUse as protected;
        getCountries as protected;
        convertPersitedInfos as protected;
        getVersion as protected;
    }

    const RESULTTYPE_ROOFTOP = 'rooftop'; // building
    const RESULTTYPE_INTERPOLATED = 'interpolated'; // between buildings (street)
    const RESULTTYPE_APPROXIMATE = 'approximated'; // city

    const ADDRESSTYPE_BUILDING = 'building';
    const ADDRESSTYPE_POSTCODE = 'postcode';
    const ADDRESSTYPE_CITY = 'city';

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

        if ($searchTerm->resultType === SearchTerm::RESULT_TYPE_ROOFTOP){
            $resultType = self::RESULTTYPE_ROOFTOP;
            $addressType = self::ADDRESSTYPE_BUILDING;
            $results = $postgres->findAddress($searchTerm, $geolocation);
            if (count($results)===0){
                // not found ... try to interpolate
                $resultType = self::RESULTTYPE_INTERPOLATED;
                $results = $postgres->findStreet($searchTerm, $geolocation);
                $results = $this->interpolateAddress(
                    $postgres, $searchTerm, $geolocation, $results
                );
            }
        }
        elseif ($searchTerm->resultType === SearchTerm::RESULT_TYPE_INTERPOLED){
            $resultType = self::RESULTTYPE_INTERPOLATED;
            $addressType = self::ADDRESSTYPE_BUILDING;
            $results = $postgres->findStreet($searchTerm, $geolocation);
        }
        elseif ($searchTerm->resultType === SearchTerm::RESULT_TYPE_APPROXIMATE){
            $resultType = self::RESULTTYPE_APPROXIMATE;
            $addressType = (empty($searchTerm->postcode))?
                self::ADDRESSTYPE_CITY : self::ADDRESSTYPE_POSTCODE;
            $results = $postgres->findCity($searchTerm, $geolocation);
        }
        else{
            $resultType = self::RESULTTYPE_APPROXIMATE;
        }
        return $this->convertPersitedInfos(
            $results, $addressType, $resultType
        );
    }

    /**
     * Calculate the interpolated address in $searchTerm and results
     *
     * @param MyPostgres $postgres
     * @param SearchTerm $searchTerm
     * @param GeoLocation $geolocation
     * @param array $results
     * @return array
     */
    private function interpolateAddress($postgres, $searchTerm, $geolocation, $results)
    {
        $output = array();
        foreach ($results as $result){
            // look for the first exact match for street, [postcode,] city
            if ($searchTerm->street === $result['street']){
                if ($searchTerm->city === $result['city']){
                    if ((empty($searchTerm->postcode))||
                        ($searchTerm->postcode === $result['postcode'])){
                        $lookup = $this->getPair($searchTerm->streetnumber);
                        $pairs = $this->getPairs($result['number']);
                        $lower=null; $higher=null;
                        foreach ($pairs as $pair){
                            $compared = $this->compareLookup($lookup, $pair);
                            if ($compared === 'more'){
                                $lower = $pair;
                            }
                            elseif ($compared === 'less'){
                                $higher = $pair;
                                break;
                            }
                        }
                        $output = $this->makeSubstitute(
                            $postgres, $searchTerm, $geolocation, $lower, $higher
                        );
                    }
                }
            }
        }
        return $output;
    }

    /**
     * Substitute the location of the search term:
     * - between two buildings
     * - near one building
     * - middle of the street
     *
     * @param MyPostgres $postgres
     * @param SearchTerm $searchTerm
     * @param GeoLocation $geolocation
     * @param array $lower
     * @param array $higher
     * @return array
     */
    private function makeSubstitute($postgres, $searchTerm, $geolocation, $lower, $higher)
    {
        $originalnumber = $searchTerm->streetnumber;
        $output = array();

        if (($lower!==null)&&($higher!==null)){
            // between two buildings
            $searchTerm->streetnumber = $lower['raw'];
            $low = $postgres->findAddress($searchTerm, $geolocation);
            if (count($low)>0){
                $low[0]['number'] = $originalnumber;
                $searchTerm->streetnumber = $higher['raw'];
                $high = $postgres->findAddress($searchTerm, $geolocation);
                if (count($high)>0){
                    $low[0]['lat'] = number_format(
                        ($high[0]['lat']+$low[0]['lat'])/2, 7
                    );
                    $low[0]['lon'] = number_format(
                        ($high[0]['lon']+$low[0]['lon'])/2, 7
                    );
                }
                $output[] = $low[0];
            }
        }
        elseif (($lower!==null)||($higher!==null)){
            // near one building
            $nearer = ($lower===null)? $higher: $lower;
            $searchTerm->streetnumber = $nearer['raw'];
            $near = $postgres->findAddress($searchTerm, $geolocation);
            if (count($near)>0){
                $near[0]['number'] = $originalnumber;
                $output[] = $near[0];
            }
        }
        else {
            // middle of the street
            $searchTerm->streetnumber = null;
            $results = $postgres->findStreet($searchTerm, $geolocation);
            foreach ($results as $key => $result){
                if ($searchTerm->street === $result['street']){
                    if ($searchTerm->city === $result['city']){
                        if ((empty($searchTerm->postcode))||
                            ($searchTerm->postcode === $result['postcode'])){
                            $result['number'] = $originalnumber;
                            $output[] = $result;
                            break;
                        }
                    }
                }
            }
        }
        return $output;
    }

    /**
     * Compare two house numbers (paired)
     * @param array $lookup
     * @param array $pair
     * @return string
     */
    private function compareLookup($lookup, $pair)
    {
        if ($lookup['number'] < $pair['number']){
            return 'less';
        }
        elseif ($lookup['number'] === $pair['number']){
            if ($lookup['subfix'] < $pair['subfix']){
                return 'less';
            }
            elseif ($lookup['subfix'] === $pair['subfix']){
                return 'equal';
            }
            return 'more';
        }
        elseif ($lookup['number'] > $pair['number']){
            return 'more';
        }
        return 'n/a';
    }

    /**
     * Convert comma seperated values to a sorted address number array
     * @param string $csv
     * @return array
     */
    private function getPairs($csv)
    {
        $pairs = array();
        $numbers = explode(',', $csv);
        foreach ($numbers as $number){
            $pairs[] = $this->getPair($number);
        }
        usort($pairs, function($a, $b){
            if ($a['number'] === $b['number']){
                return ($a['subfix'] < $b['subfix'])? -1 : 1;
            }
            return $a['number'] - $b['number'];
        }); // ordered ascending by number and subfix
        return $pairs;
    }

    /**
     * Convert a house number to a pair: integer and the subfix
     * @param string $rawNumber
     * @return array
     */
    private function getPair($rawNumber)
    {
        $number = preg_replace('/\D/', '', $rawNumber);
        $subfix = str_replace( $number, '', $rawNumber);
        return array(
            'number' => intval($number),
            'subfix' => $subfix,
            'raw' => $rawNumber
        );
    }
}