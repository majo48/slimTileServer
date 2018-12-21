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
     * 3. max. 10'000 requests per day (todo)
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
        if ($this->checkRequestsPerSecond()){
            $response = array(
                'status_code' => 429,
                'status_text' => "too many requests"
            );
        }
        if ($this->checkInvalidKey($key)){
            $response = array(
                'status_code' => 401,
                'status_text' => "unauthorized"
            );
        }
        return $response;
    }

    /**
     * Check the number of requests per second.
     * @return bool error: true
     */
    private function checkRequestsPerSecond()
    {
        $error = false;
        $timf = $_SERVER['REQUEST_TIME_FLOAT'];
        $url5 = md5($_SESSION['REQUEST_URI']);
        $hash = $url5.'|'.$timf;
        if (isset($_SESSION['tos'])){
            // compare with previous request
            list($_uri, $_exp) = explode('|', $_SESSION['tos']);
            if ($_uri === $url5){
                $secs = $timf - $_exp;
                if ($secs < 1.000){
                    $error = true; // too fast!
                }
            }
        }
        $_SESSION['tos'] = $hash;
        return $error;
    }

    /**
     * Check if the key is valid (currently only one is considered valid)
     * @param $key
     * @return bool error: true
     */
    private function checkInvalidKey($key)
    {
        return $key !== 'da98c7a446274dbe82b8f13667848952'; // default key
    }

}