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
        $msecs = strval(round(microtime(true), 3));
        $usage = $this->getUsage($key, $msecs);
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

    /**
     * Get the current usage info from the database
     * @param $key
     * @return array
     */
    private function getUsage($key, $msecs)
    {
        try{
            $pdo = $this->container->get('pdo');
            $quote = '"';
            $stmt = $pdo->prepare(
                "SELECT * FROM `usage` WHERE userkey = ".$quote.$key.$quote
            );
            $stmt->execute();
            $usage = $stmt->fetch(); // get first row
            if (false===$usage){
                $stmt->closeCursor();
                // usage $key not found
                $stmt2 = $pdo->prepare(
                    "SELECT * FROM `register` WHERE userkey = ".$quote.$key.$quote
                );
                $stmt2->execute();
                $usage = $stmt2->fetch(); // get first row
                if (false===$usage){
                    // $key not registered
                    return array();
                }
                $stmt2->closeCursor();
                $stmt = $pdo->prepare(
                    "INSERT INTO `usage` (userkey, microtime, countday) ".
                    "VALUES(".$quote.$key.$quote.",".$quote.$msecs.$quote.",1);"
                );
                $stmt->execute();
                $usage = array(
                    'userkey' => $key,
                    'microtime' => $msecs,
                    'countday' => 1
                );
            }
            else {
                // check for sameday
                $newDate = date("Y-m-d", $msecs);
                $oldDate = date("Y-m-d", $usage['microtime']);
                $counter = ($newDate===$oldDate)? $usage['countday']+1 : 1;
                // persist timestamp and counter
                $stmt = $pdo->prepare( "UPDATE `usage` SET".
                    " microtime = ".$quote.$msecs.$quote.','.
                    " countday = ".$counter.
                    " WHERE userkey = ".$quote.$key.$quote
                );
                $stmt->execute();
                $usage['countday'] = $counter;
            }
            return $usage;
        }
        catch (\Exception $e){
            return array();
        }
    }

}