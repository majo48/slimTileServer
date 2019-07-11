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
    const SEMAPHOREID = 100;
    const SHAREDMEMORYID = 200;

    /** @var Container $container */
    protected $container;

    protected $sem;
    protected $shm;

    /** -----
     * MyPostgres constructor.
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
            $sem = sem_get(self::SEMAPHOREID, 1, 0600);
            sem_acquire($sem) or die('Cannot acquire semaphore');
            // ----- wait here if already used by another thread

            // get persisted value for $key from shared memory
            $shm = shm_attach(self::SHAREDMEMORYID,16384, 0600);
            $jsonOld = shm_get_var($shm, 0);
            if (empty($jsonOld)){
                // todo continue here... check $key = valid
                $result = array(
                    'userkey' => $key,
                    'countday' => 0,
                    'microtime' => $msecs-1000
                );
            }
            else{
                $result = json_decode($jsonOld);
            }
            $persist = array(
                'userkey' => $key,
                'countday' => $result['countday']+1,
                'microtime' => $msecs
            );
            $jsonNew = json_encode($persist);
            $put = shm_put_var($shm, 0, $jsonNew);
            $det = shm_detach($shm);

            // ----- release other threads next
            sem_release($sem);
            return $result;
        }
        catch (\Exception $e){
            sem_release(self::SEMAPHOREID);
            return array();
        }
    }
}