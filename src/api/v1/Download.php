<?php

namespace App\api\v1;

use PDO;

/**
 * Class Download
 * Download OpenAddresses data for Switzerland (and Liechtenstein)
 *
 * @package App\api\v1
 *
 * Route pattern: /api/v1/download?country=XX
 * Where XX = {CH,LI}
 */
class Download
{
    /** @var Container $container */
    protected   $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function index($request, $response, $args)
    {
        set_time_limit ( 600 ); // > 2 million rows in CH

        $ipAddress = $request->getAttribute('ip_address');
        $country = strtoupper($request->getQueryParam('country'));

        // request log message
        $this->container->logger->info("/download request for ".$country);

        $message = null;
        /** @var MyPostgres $postgres */
        $postgres = $this->container->mypostgres;
        // download file to data/countrywide
        $message = $postgres->downloadCountries($country);

        // send proper message to user
        if (null===$message){
            $message = 'Download was successfull.';
        }
        $body = $response->getBody();
        $body->write($message);
        return $response;
    }
}