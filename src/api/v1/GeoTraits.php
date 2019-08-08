<?php
/**
 * Created by PhpStorm.
 * User: mart
 * Date: 2018-12-21
 * Time: 14:32
 */

namespace App\api\v1;

/**
 * Trait GeoTraits
 * @package App\api\v1
 *
 * Copyright (C) 2019  Martin Jonasse, see LICENCE.md.
 */
trait GeoTraits
{
    /** -----
     * Check terms of use:
     * 1. valid key
     * 2. max. one request per second
     * 3. max. 10'000 requests per day
     *
     * @param string $key
     * @return array with status code and text
     */
    public function checkTermsOfUse($key)
    {
        $response = array(
            'status' => 200,
            'status_text' => "OK"
        );
        $msecs = strval(round(microtime(true), 3));
        $usage = $this->container->mycache->getUsage($key, $msecs);
        if ($usage===array()){
            // invalid key
            $response = array(
                'status' => 401,
                'status_text' => "unauthorized"
            );
        }
        $lapsed = $msecs - $usage['microtime'];
        if (($lapsed <= 1.0)||($usage['countday']>10000)){
            $response = array(
                'status' => 429,
                'status_text' => "too many requests"
            );
        }
        return $response;
    }

    /** -----
     * Set an array with country information, indexed by countrycode.
     *
     * @return array
     */
    public function getCountries()
    {
        return array(
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

    /** -----
     * Convert an array with persisted infos to the defined output format
     * @param array $inputs
     * @param string $addressType
     * @param string $locationType
     * @return array
     */
    public function convertPersitedInfos($inputs, $addressType, $locationType)
    {
        $output = array();
        foreach ($inputs as $input){
            $countrycode = $input['countrycode'];
            $output[] = array(
                "address_id" => (string) $input['id'],
                "address_type" => $addressType,
                "streetnumber" => $input['number'],
                "street" => $input['street'],
                "postcode" => $input['postcode'],
                "city" => $input['city'],
                "country" => $this->countries[$countrycode]['country'],
                "countrycode" => $countrycode,
                "latitude" =>  $input['lat'],
                "longitude" => $input['lon'],
                "location_type" =>  $locationType,
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

    /** -----
     * Get the persisted download version date from the array
     * @param string $countrycode
     * @return string with timestamp
     */
    public function getVersion($countrycode)
    {
        foreach ($this->timestamps as $timestamp){
            if ($timestamp['countrycode']===$countrycode){
                return $timestamp['timestamp'];
            }
        }
        return 'n/a';
    }
}