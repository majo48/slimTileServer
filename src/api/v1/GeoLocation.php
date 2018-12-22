<?php
/**
 * Created by PhpStorm.
 * User: mart
 * Date: 2018-12-22
 * Time: 11:53
 */

namespace App\api\v1;

use GeoIp2\Database\Reader;

class GeoLocation
{
    public $country;
    public $countryCode;
    public $state;
    public $stateCode;
    public $city;
    public $postalCode;
    public $latitude;
    public $longitude;
    public $errormsg;

    public function __construct($ipadr)
    {
        $ip = ($ipadr!=='::1')? $ipadr: '151.248.223.119'; // localhost
        try{
            $filePath = __DIR__.'/../../../data/city/Geolite2-City.mmdb';
            $geoip = new Reader($filePath);
            $record = $geoip->city($ip);

            $this->country = $record->country->name;
            $this->countryCode = $record->country->isoCode;
            $this->state = $record->mostSpecificSubdivision->name;
            $this->stateCode = $record->mostSpecificSubdivision->isoCode;
            $this->city = $record->city->name;
            $this->postalCode = $record->postal->code;
            $this->latitude = $record->location->latitude;
            $this->longitude = $record->location->longitude;
            $this->errormsg = null;
        }
        catch (\Exception $e){
            $this->errormsg = $e->getMessage();
        }
    }
}