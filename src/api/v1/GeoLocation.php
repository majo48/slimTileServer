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
    use MyProjectDir{
        getProjectDir as protected;
    }
                            // Example
    public $country;        // Switzerland
    public $countryCode;    // CH
    public $state;          // Zug
    public $stateCode;      // ZG
    public $city;           // Cham
    public $postalCode;     // 6330
    public $latitude;       // 47.1821
    public $longitude;      // 8.4636
    public $errormsg;       // null

    /**
     * GeoLocation constructor for any IP address. If the IP address is a
     * private IP address, then a location in Cham/ZG, Switzerland is returned.
     * @param string $ipadr valis IP4 address
     */
    public function __construct($ipadr)
    {
        $privateIP = '/(^127\.)|(^10\.)|(^172\.1[6-9]\.)|(^172\.2[0-9]\.)|(^172\.3[0-1]\.)|(^192\.168\.)/';
        $ip = (preg_match($privateIP, $ipadr))? '151.248.223.119': $ipadr;
        try{
            $filePath = $this->getProjectDir().'/data/city/GeoLite2-City.mmdb';
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