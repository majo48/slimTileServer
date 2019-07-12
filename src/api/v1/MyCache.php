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
     * @param $key
     * @param $msecs
     * @return array (associative keys: userkey, countday, microtime) or empty
     */
    public function getUsage($key, $msecs)
    {
        try{
            //todo continue here
            $result = array(
                'userkey' => $key,
                'countday' => 0,
                'microtime' => $msecs-1000
            );
            return result;
        }
        catch (\Exception $e){
            return array();
        }
    }
}