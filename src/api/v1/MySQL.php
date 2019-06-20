<?php
/**
 * Created by PhpStorm.
 * User: mart
 * Date: 2018-12-21
 * Time: 14:32
 */

namespace App\api\v1;

use PDO;

class MySQL
{
    /** @var Container $container */
    protected   $container;

    /** @var PDO $pdoMysql */
    protected $pdoMysql;

    public function __construct($container)
    {
        $this->container = $container;

        $custom = $container->get('settings')['custom'];
        $dbname = $custom['mysqlDbName'];
        $username = $custom['mysqlDbUserName'];
        $password = $custom['mysqlDbUserPassword'];
        $dsn = "mysql:host=localhost;dbname=$dbname;charset=utf8";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        ];
        $this->pdoMysql = new PDO($dsn, $username, $password, $options);
    }

    /**
     * Register the user in the MySQL database.
     * @param string $username
     * @param string $userkey
     */
    public function registerUser($username, $userkey)
    {
        try{
            $datestring = date('Y-m-d H:i:s');
            $quote = '"';
            $stmt = $this->pdoMysql->query(
                "SELECT * FROM `register` ".
                "WHERE username = ".$quote.$username.$quote.';'
            );
            $rows = $stmt->fetch();

            if ($rows===false){
                // the user is not yet registered in the database
                $sql = "INSERT INTO `register` ".
                    "(username, userkey, registerdate) ".
                    "VALUES(".$quote.$username.$quote.",".
                    $quote.$userkey.$quote.",".$quote.$datestring.$quote.");"
                ;
                $rows = $this->pdoMysql->exec($sql);
            }
            else {
                // the user is already registered in the database
                $sql = "UPDATE `register` SET".
                    " userkey = ".$quote.$userkey.$quote.','.
                    " registerdate = ".$quote.$datestring.$quote.
                    " WHERE username = ".$quote.$username.$quote
                ;
                $rows = $this->pdoMysql->exec($sql);
            }
            $this->container->logger->info(
                "persisted ".$username.' with key: '.$userkey
            );
        }
        catch (\Exception $e){
            $this->container->logger->error(
                "persisted: ".$username.' with error: '.$e
            );
        }
    }

    /**
     * Get/set the current usage info in the mysql database
     * @param $key
     * @return array
     */
    public function getUsage($key, $msecs)
    {
        try{
            $quote = '"';
            $stmt = $this->pdoMysql->prepare(
                "SELECT * FROM `usage` WHERE userkey = ".$quote.$key.$quote
            );
            $stmt->execute();
            $usage = $stmt->fetch(); // get first row
            if (false===$usage){
                $stmt->closeCursor();
                // usage $key not found
                $stmt2 = $this->pdoMysql->prepare(
                    "SELECT * FROM `register` WHERE userkey = ".$quote.$key.$quote
                );
                $stmt2->execute();
                $usage = $stmt2->fetch(); // get first row
                if (false===$usage){
                    // $key not registered
                    return array();
                }
                $stmt2->closeCursor();
                $stmt = $this->pdoMysql->prepare(
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
                $stmt = $this->pdoMysql->prepare(
                    "UPDATE `usage` SET".
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
            $this->container->logger->error(
                'MySQL database record for '.$key.', message: '.$e->getMessage()
            );
            return array();
        }
    }
}