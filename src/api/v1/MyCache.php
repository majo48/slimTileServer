<?php


namespace App\api\v1;


/**
 * Shared memory containing the usage statistics
 * shared memory key = userkey (defined in the postgres database)
 * shared memory values:
 * { "userkey": "abcd...", "microtime": "12345...", "countday": "1" }
 *
 * Class MyCache
 * @package App\api\v1
 */
class MyCache
{
    /** @var Container $container */
    protected $container;

    /** -----
     * MyCache constructor.
     * @param Container $container
     */
    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * Get/set the current usage info in shared memory.
     * Time to live: 24hrs (regenerates key automatically after that).
     *
     * This code is not reentrant, however it errs on the safe side (no crash).
     *
     * @param $key
     * @param $msecs
     * @return array (associative keys: userkey, countday, microtime) or empty
     */
    public function getUsage($key, $msecs)
    {
        try{
            $success = false;
            $jsonOld = apcu_fetch($key,$success);
            if ($success===false){
                // check for an invalid key
                $valid = $this->container->mypostgres->checkValidKey($key);
                if ($valid===false){
                    return array();
                }
                // valid, set initial value
                $result = array(
                    'userkey' => $key,
                    'countday' => 0,
                    'microtime' => $msecs-2000
                );
            }
            else{
                $result = json_decode($jsonOld, true); // json to array
            }
            $persist = array(
                'userkey' => $key,
                'countday' => $result['countday']+1,
                'microtime' => $msecs
            );
            $jsonNew = json_encode($persist);
            $success = apcu_store($key, $jsonNew, 86400); // persist in memory
            if ($success) {
                return $result;
            }
            return array();
        }
        catch (\Exception $e){
            $this->container->logger->error(
                "APCu update error: ".$e->getMessage()
            );
            return array();
        }
    }
}