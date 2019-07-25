<?php
/**
 * Created by PhpStorm.
 * User: mart
 * Date: 27/11/18
 * Time: 14:01
 */

namespace App\api\v1;

use App\api\v1\TermsOfUse;

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

    /** @var Container $container */
    protected   $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * Standard slim entry point for request /api/v1/reversegeocode
     * @param Slim\Http\Request $request
     * @param Slim\Http\Response $response
     * @param array $args
     */
    public function index($request, $response, $args)
    {
        $ipAddress = $request->getAttribute('ip_address');
        $queryLat = $request->getQueryParam('lat');
        $queryLng = $request->getQueryParam('lng');
        $queryKey = $request->getQueryParam('key');

        $response = $this->checkTermsOfUse($queryKey);
        if ($response['status']===200){
            $response = array(
                'status' => 501,
                'status_text' => "Not implemented yet."
            );
        }
        $output = json_encode($response);
        return $output;
    }
}