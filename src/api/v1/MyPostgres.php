<?php


namespace App\api\v1;

use PDO;

class MyPostgres
{
    use MyProjectDir{
        getProjectDir as protected;
    }
    const CSVFILE = 'data/countrywide/ch/countrywide.csv';

    /** @var Container $container */
    protected   $container;

    /** @var PDO $pdoPostgres */
    protected $pdoPostgres;

    /** -----
     * MyPostgres constructor.
     * @param Container $container
     */
    public function __construct($container)
    {
        $this->container = $container;

        $custom = $container->get('settings')['custom'];
        $dbname = $custom['postgresDbName'];
        $username = $custom['postgresDbUserName'];
        $password = $custom['postgresDbUserPassword'];
        $dsn = "pgsql:host=localhost;dbname=$dbname";
        $this->pdoPostgres = new PDO($dsn, $username, $password);
    }

    /** -----
     * @param string $country
     * @return null|string
     */
    public function downloadCsv($country)
    {
        $csv = $this->getProjectDir().'/'.self::CSVFILE;
        //todo finish this
        return null;
    }

    /** -----
     * @param string $country
     * @return null|string
     */
    public function updateDb($country)
    {
        // open csv file
        $csv = $this->getProjectDir().'/'.self::CSVFILE;
        $fn = fopen($csv, 'r');
        if ($fn===false){
            return 'Cannot open file '.$csv;
        }

        // read csv header
        $header = explode(',', fgets($fn));
        if ($fn===false){
            return 'No header in file '.$csv;
        }
        $this->clearDb();

        // read csv data
        $cntr = 0;
        while(!feof($fn)) {
            $data = explode(',', fgets($fn));
            $this->addRow($header, $data);
            $cntr++;
        }
        fclose($fn);
        if ($cntr===0){
            return 'No data in file '.$csv;
        }
        return null;
    }

    /**
     * Clear rows from the postgres database
     */
    private function clearDb()
    {
        //todo finish this
        $dummy = 'stop';
    }

    /**
     * Add one row to postgres database
     * @param array $header
     * @param array $data
     */
    private function addRow($header, $data)
    {
        //todo finish this
        $dummy = 'stop';
    }

}