<?php
/**
 * Created by PhpStorm.
 * User: mart
 * Date: 2018-12-21
 * Time: 14:32
 */

namespace App\api\v1;

trait TermsOfUse
{
    /**
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
            'status_code' => 200,
            'status_text' => "OK"
        );
        $msecs = strval(round(microtime(true), 3));
        $usage = $this->container->mycache->getUsage($key, $msecs);
        if ($usage===array()){
            // invalid key
            $response = array(
                'status_code' => 401,
                'status_text' => "unauthorized"
            );
        }
        $lapsed = $msecs - $usage['microtime'];
        if (($lapsed <= 1.0)||($usage['countday']>10000)){
            $response = array(
                'status_code' => 429,
                'status_text' => "too many requests"
            );
        }
        return $response;
    }
}