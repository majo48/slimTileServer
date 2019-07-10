<?php


namespace App\api\v1;

/**
 * Shared memory containing the usage statistics
 * shared memory key = userkey in the postgres database
 * shared memory values:
 * { "userkey": "abcd...", "microtime": "12345...", "countday": "1" }
 *
 * Class MyShmop
 * @package App\api\v1
 */
class MyShmop
{
    /** @var Container $container */
    protected $container;

    /** -----
     * MyPostgres constructor.
     * @param Container $container
     */
    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * Get/set the current usage info in shared memory
     * @param $key
     * @param $msecs
     */
    public function getUsage($key, $msecs)
    {
        try{
            //todo add code for shared memory operations
            return array(
                'userkey' => $key,
                'microtime' => $msecs,
                'countday' => 1
            );
        }
        catch (\Exception $e){
            return array();
        }
    }
}