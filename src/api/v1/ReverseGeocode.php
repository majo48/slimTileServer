<?php
/**
 * Created by PhpStorm.
 * User: mart
 * Date: 27/11/18
 * Time: 14:01
 */

namespace App\api\v1;


class ReverseGeocode
{
    public function __construct($app)
    {
        $this->app = $app;
    }

    public function index($request, $response, $args)
    {
        $coordinates = $args['text'];
        $responseArray = [
            'status_code' => 500,
            'status_text' => 'not implemented yet',
            'nounce' => 0
        ];
        echo json_encode($responseArray);
    }
}