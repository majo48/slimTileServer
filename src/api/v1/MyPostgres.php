<?php


namespace App\api\v1;

use PDO;

class MyPostgres
{
    use MyProjectDir{
        getProjectDir as protected;
    }

    /** @var Container $container */
    protected $container;

    /** @var array $countries */
    protected $countries;

    /** @var PDO $pdoPostgres */
    protected $pdoPostgres;

    /** -----
     * MyPostgres constructor.
     * @param Container $container
     */
    public function __construct($container)
    {
        $this->container = $container;

        $this->countries = array(
            'CH' => 'data/countrywide/ch/countrywide.csv',
            'LI' => 'data/countrywide/li/countrywide.csv'
        );

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
        //todo finish this
        return null;
    }

    /** -----
     * @param string $country
     * @return null|string
     */
    public function updateDb($countrycode)
    {
        $cntr = 0;
        foreach ($this->countries as $country => $pathfile){
            if (($countrycode==='*')||
                ($countrycode===$country)){
                // open csv file
                $csv = $this->getProjectDir().'/'.$pathfile;
                $fn = fopen($csv, 'r');
                if ($fn===false){
                    return 'Cannot open file '.$csv;
                }
                // read csv header
                $header = explode(',', rtrim(fgets($fn),"\r\n"));
                if (($fn===false)||($this->checkHeader($header)===false)){
                    return 'No/wrong header in file '.$csv;
                }
                $this->clearDb($country);
                // read csv data
                while(!feof($fn)) {
                    $data = explode(',', rtrim(fgets($fn),"\r\n"));
                    $this->addRow($header, $data, $country);
                    $cntr++;
                }
                fclose($fn);
                $this->updateGwr($country);
            }
        }
        if ($cntr===0){
            return 'No data in file '.$csv;
        }
        return null;
    }

    /**
     * Check the file header for the correct (expected) column index & names
     * @param array $header
     * @return boolean OK=true, error=false
     */
    private function checkHeader($header)
    {
        try{
            if ($header[0]!=='LON') return false;
            if ($header[1]!=='LAT') return false;
            if ($header[2]!=='NUMBER') return false;
            if ($header[3]!=='STREET') return false;
            if ($header[5]!=='CITY') return false;
            if ($header[7]!=='REGION') return false;
            if ($header[8]!=='POSTCODE') return false;
            if ($header[9]!=='ID') return false;
            if ($header[10]!=='HASH') return false;
            return true;
        }
        catch (\Exception $e){
            return false;
        }
    }

    /**
     * Clear all rows in postgres database gis, table gwr
     * @return integer number of rows deleted
     */
    private function clearDb($countrycode)
    {
        try{
            $quote = '"';
            $stmt = $this->pdoPostgres->prepare(
                'DELETE FROM gwr WHERE countrycode ='.$quote.$countrycode.$quote
            );
            $stmt->execute();
            return $stmt->rowCount();
        }
        catch (Exception $e){
            $this->container->logger->error(
                "Database clear error: ".$e->getMessage()
            );
            return 0;
        }
    }

    /**
     * Add one row to postgres database gis, table gwr
     * NOTE: the id field ($data[9]) for Liechtenstein is empty
     *
     * @param array $header
     * @param array $data
     * @return integer the id of the new record
     */
    private function addRow($header, $data, $countrycode)
    {
        try{
            // prepare statement for insert
            $sql = 'INSERT INTO '.
                'gwr(street,number,city,region,postcode,countrycode,gwrId,hash,lat,lon) '.
                'VALUES(:street,:number,:city,:region,:postcode,:countrycode,:gwrId,:hash,:lat,:lon)';
            $stmt = $this->pdoPostgres->prepare($sql);

            // pass values to the statement
            $stmt->bindValue(':street', $data[3]);
            $stmt->bindValue(':number', $data[2]);
            $stmt->bindValue(':city', $data[5]);
            $stmt->bindValue(':region', $data[7]);
            $stmt->bindValue(':postcode', $data[8]);
            $stmt->bindValue(':countrycode', $countrycode);
            $stmt->bindValue(':gwrId', $data[9]);
            $stmt->bindValue(':hash', $data[10]);
            $stmt->bindValue(':lat', $data[1]);
            $stmt->bindValue(':lon', $data[0]);

            // execute the insert statement
            $stmt->execute();

            // return generated id
            return $this->pdoPostgres->lastInsertId('id');
        }
        catch (Exception $e){
            $this->container->logger->error(
                "Database insert error: ".$e->getMessage()
            );
            return 0;
        }
    }

    /**
     * Update all geometry fields in the postgres database
     * @return integer number of rows updated
     */
    private function updateGwr($countrycode)
    {
        try{
            // prepare statement for update
            $quote = '"';
            $sql =
                "UPDATE gwr SET geom = ST_SetSRID(".
                "ST_MakePoint(cast(LON AS float), cast(LAT AS float)), 4326) ".
                "WHERE countrycode = ".$quote.$countrycode.$quote;
            $stmt = $this->pdoPostgres->prepare($sql);
            // execute the update statement
            $stmt->execute();
            return $stmt->rowCount();
        }
        catch (Exception $e){
            $this->container->logger->error(
                "Database update error: ".$e->getMessage()
            );
            return 0;
        }
    }

}