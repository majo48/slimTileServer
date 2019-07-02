<?php


namespace App\api\v1;

use PDO;

class MyPostgres
{
    use MyProjectDir{
        getProjectDir as protected;
    }
    // both files have an identical structure
    const CSVFILECH= 'data/countrywide/ch/countrywide.csv';

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
        $csv = $this->getProjectDir().'/'.self::CSVFILECH;
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
        $csv = $this->getProjectDir().'/'.self::CSVFILECH;
        $fn = fopen($csv, 'r');
        if ($fn===false){
            return 'Cannot open file '.$csv;
        }

        // read csv header
        $header = explode(',', rtrim(fgets($fn),"\r\n"));
        if (($fn===false)||($this->checkHeader($header)===false)){
            return 'No/wrong header in file '.$csv;
        }
        $this->clearDb();

        // read csv data
        $cntr = 0;
        while(!feof($fn)) {
            $data = explode(',', rtrim(fgets($fn),"\r\n"));
            $this->addRow($header, $data);
            $cntr++;
        }
        fclose($fn);
        $this->updateGwr();

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
    private function clearDb()
    {
        try{
            $stmt = $this->pdoPostgres->prepare('DELETE FROM gwr');
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
     * @param array $header
     * @param array $data
     * @return integer the id of the new record
     */
    private function addRow($header, $data)
    {
        try{
            // prepare statement for insert
            $sql = 'INSERT INTO '.
                'gwr(street,number,city,region,postcode,gwrId,hash,lat,lon) '.
                'VALUES(:street,:number,:city,:region,:postcode,:gwrId,:hash,:lat,:lon)';
            $stmt = $this->pdoPostgres->prepare($sql);

            // pass values to the statement
            $stmt->bindValue(':street', $data[3]);
            $stmt->bindValue(':number', $data[2]);
            $stmt->bindValue(':city', $data[5]);
            $stmt->bindValue(':region', $data[7]);
            $stmt->bindValue(':postcode', $data[8]);
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
    private function updateGwr()
    {
        try{
            // prepare statement for update
            $sql = "UPDATE gwr SET geom = ST_SetSRID(".
                "ST_MakePoint(cast(LON AS float), cast(LAT AS float)), 4326);";
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