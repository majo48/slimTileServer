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
                'status_code' => $searchTerm->code,
                'status_text' => $searchTerm->message
            );
        }

        echo json_encode($response);
    }

}