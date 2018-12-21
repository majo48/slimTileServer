<?php
/**
 * Created by PhpStorm.
 * User: mart
 * Date: 27/11/18
 * Time: 13:42
 */

namespace App\api\v1;


class Geocode
{
    /** @var Container $container */
    protected   $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function index($request, $response, $args)
    {
        $address = $args['text'];
        $responseArray = [
            'status_code' => 500,
            'status_text' => 'not implemented yet',
            'nounce' => 0
        ];
        echo json_encode($responseArray);
    }
}