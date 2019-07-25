<?php


namespace App\api\v1;

use PDO;
use DOMDocument;
use ZipArchive;

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
            'CH' => array(
                'link' => 'https://results.openaddresses.io/sources/ch/countrywide',
                'zipfile' => 'data/countrywide/countrywide.ch.csv',
                'pathfile' => 'data/countrywide/ch/countrywide.csv'
            ),
            'LI' => array(
                'link' => 'https://results.openaddresses.io/sources/li/countrywide',
                'zipfile' => 'data/countrywide/countrywide.li.csv',
                'pathfile' => 'data/countrywide/li/countrywide.csv'
            ),
        );

        $custom = $container->get('settings')['custom'];
        $dbname = $custom['postgresDbName'];
        $username = $custom['postgresDbUserName'];
        $password = $custom['postgresDbUserPassword'];
        $dsn = "pgsql:host=localhost;dbname=$dbname";
        $this->pdoPostgres = new PDO($dsn, $username, $password);
    }

    /** -----
     * Register the user in the Postgres database.
     * @param string $username
     * @param string $userkey
     * @return boolean true=success, false=failed
     */
    public function registerUser($username, $userkey)
    {
        try{
            $quote = "'";
            $timestamp = date('Y-m-d H:i:s');
            // check if persisted
            $stmt = $this->pdoPostgres->prepare(
                "SELECT * FROM register WHERE username = ".
                $quote.$username.$quote.";"
            );
            $stmt->execute();
            if ($stmt->rowCount()===0){
                // insert row
                $stmt = $this->pdoPostgres->prepare(
                    'INSERT INTO '.
                    'register(username,userkey,registerdatetime) '.
                    'VALUES(:username,:userkey,:registerdatetime);'
                );
                $stmt->bindValue(':username', $username);
                $stmt->bindValue(':userkey', $userkey);
                $stmt->bindValue(':registerdatetime', $timestamp);
                $stmt->execute();
            }
            else{
                // update row
                $stmt = $this->pdoPostgres->prepare(
                    'UPDATE register SET '.
                    'userkey = :userkey, registerdatetime = :registerdatetime '.
                    'WHERE username ='.$quote.$username.$quote.';'
                );
                $stmt->bindValue(':userkey', $userkey);
                $stmt->bindValue(':registerdatetime', $timestamp);
                $stmt->execute();
            }
            return true;
        }
        catch (\Exception $e){
            $this->container->logger->error(
                "Register user ".$username." with error: ".$e->getMessage()
            );
            return false;
        }
    }

    /** -----
     * @param string $country
     * @return null|string null=OK, string=error message
     */
    public function downloadCountries($countrycode)
    {
        try{
            foreach ($this->countries as $country => $countryArray){
                if (($countrycode==='*')||
                    ($countrycode===$country)){
                    $link = $this->getDownloadLink($country);
                    if (null===$link){
                        return 'Download link not found for country '.$country;
                    }
                    if ($this->hasDownloadLinkFor($country, $link)){
                        continue; // skip, download already in database
                    }
                    $destination = $this->getProjectDir().'/'.
                        $this->countries[$country]['zipfile'];
                    unlink($destination);
                    file_put_contents($destination, file_get_contents($link));
                    // unzip file
                    $zip = new ZipArchive();
                    $res = $zip->open($destination);
                    if ($res===true){
                        $path = pathinfo($destination, PATHINFO_DIRNAME);
                        $zip->extractTo($path);
                        $zip->close();
                        $this->setDownloadLinkFor($country, $link);
                        $this->updateGisGwr($country);
                        continue; // next country
                    }
                    return 'Error opening Zip file '.$destination;
                }
            }
            return null;
        }
        catch (\Exception $e){
            return 'Download error: '.$e->getMessage();
        }
    }

    /**
     * Compares with the last download link for a countrycode, e.g. CH
     * The link contains a unique number for each new run version.
     *
     * @param string $countrycode
     * @return boolean true when links are the same, false when not
     */
    private function hasDownloadLinkFor($countrycode, $hyperlink)
    {
        try{
            $quote = "'";
            $stmt = $this->pdoPostgres->prepare(
                "SELECT * FROM downloads WHERE countrycode = ".
                $quote.$countrycode.$quote.";"
            );
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return ($hyperlink===$row['hyperlink']);
        }
        catch (Exception $e){
            $this->container->logger->error(
                "Downloads read error: ".$e->getMessage()
            );
            return false;
        }
    }

    /**
     * Set the last download link for a countrycode, e.g. CH
     * The link contains a unique number for each new run version.
     *
     * @param string $countrycode
     * @return integer|null 1 for success, 0 for error
     */
    private function setDownloadLinkFor($countrycode, $hyperlink)
    {
        try{
            $quote = "'";
            $sql =
                "UPDATE downloads SET hyperlink = ".$quote.$hyperlink.$quote.
                " WHERE countrycode = ".$quote.$countrycode.$quote.';';
            $stmt = $this->pdoPostgres->prepare( $sql );
            $stmt->execute();
            return $stmt->rowCount();
        }
        catch (Exception $e){
            $this->container->logger->error(
                "Downloads update error: ".$e->getMessage()
            );
            return 0;
        }
    }


    /**
     * Get latest download link from addresses.io resource page
     * @param $countrycode
     * @return string|null
     */
    private function getDownloadLink($countrycode)
    {
        try{
            $link = $this->countries[$countrycode]['link'];
            $html = file_get_contents($link);
            $doc = new DOMDocument();
            $doc->loadHTML($html); // openaddresses resource page
            $table = $doc->getElementById('source');
            $trs = $table->getElementsByTagName('tr');
            $tr = $trs->item(1); // second row
            $tds = $tr->getElementsByTagName('td'); // get columns in this row
            $td = $tds->item(3); // third td
            $anchors = $td->getElementsByTagName('a'); //
            $anchor = $anchors->item(0); // first anchor
            return $anchor->getAttribute('href');
        }
        catch (\Exception $e){
            $this->container->logger->error(
                "Get download link error: ".$e->getMessage()
            );
            return null;
        }
    }

    /**
     * Download from resource server and unzip the download
     * @param string $country asterik(*) is for all countries
     * @return null|string
     */
    private function updateGisGwr($countrycode)
    {
        $cntr = 0;
        foreach ($this->countries as $country => $countryArray){
            if (($countrycode==='*')||
                ($countrycode===$country)){
                // open csv file
                $csv = $this->getProjectDir().'/'.$countryArray['pathfile'];
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
                $this->updateGwrGeom($country);
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
            $this->container->logger->error(
                "Header check error: ".$e->getMessage()
            );
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
            $quote = "'";
            $stmt = $this->pdoPostgres->prepare(
                'DELETE FROM gwr WHERE countrycode ='.$quote.$countrycode.$quote.';'
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
                'VALUES(:street,:number,:city,:region,:postcode,:countrycode,:gwrId,:hash,:lat,:lon);';
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
    private function updateGwrGeom($countrycode)
    {
        try{
            // prepare statement for update
            $quote = "'";
            $sql =
                "UPDATE gwr SET geom = ST_SetSRID(".
                "ST_MakePoint(cast(LON AS float), cast(LAT AS float)), 4326) ".
                "WHERE countrycode = ".$quote.$countrycode.$quote.';';
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

    /** -----
     * Check if the key provided is valid.
     * @param string $key
     * @return bool true: valid, false: invalid
     */
    public function checkValidKey($key)
    {
        try{
            $quote = "'";
            $stmt = $this->pdoPostgres->prepare(
                "SELECT * FROM register WHERE userkey = ".$quote.$key.$quote.";"
            );
            $stmt->execute();
            return ($stmt->rowCount()!==0);
        }
        catch (\Exception $e){
            $this->container->logger->error(
                "Database read key error: ".$e->getMessage()
            );
            return false;
        }
    }

    /** -----
     * Find the specified address (searchterm) in database 'gis' table 'gwr'
     *
     * @param SearchTerm $searchTerm
     * @param GeoLocation $geolocation
     * @return array
     */
    public function findAddress($searchTerm, $geolocation)
    {
        try{
            if ((!empty($searchTerm->street))&&
                (empty($searchTerm->streetnumber))&&
                (empty($searchTerm->postcode))&&
                (empty($searchTerm->city))&&
                (empty($searchTerm->countrycode))){
                // street only
                $sql = "SELECT DISTINCT ".
                    "street, NULL AS number, postcode, city, countrycode, ".
                    "MIN(lat) as lat, MIN(lon) as lon, MIN(ST_Distance( geom, ".
                    "ST_SetSRID(ST_MakePoint(:lon, :lat),4326))) AS dist ".
                    "FROM gwr WHERE (street LIKE '%:street%') ".
                    "GROUP BY street, postcode, city, countrycode ".
                    "ORDER BY dist ASC LIMIT 10;";
                $sql = str_replace(':street', $searchTerm->street, $sql);
                $sql = str_replace(":lat", strval($geolocation->latitude), $sql);
                $sql = str_replace(':lon', strval($geolocation->longitude), $sql);
                $stmt = $this->pdoPostgres->prepare($sql);
            }
            else{
                // conditional
                $sql = "SELECT DISTINCT ".
                    "street, number, postcode, city, countrycode, ".
                    "lat, lon, ST_Distance( geom, ".
                    "ST_SetSRID(ST_MakePoint(".$geolocation->longitude.
                    ", ".$geolocation->latitude."),4326)) AS dist ".
                    "FROM gwr WHERE (street LIKE '%".$searchTerm->street."%') ";
                if (!empty($searchTerm->streetnumber)){
                    $sql .= "AND (number LIKE '%".$searchTerm->streetnumber."%') ";
                }
                if (!empty($searchTerm->postcode)){
                    $sql .= "AND (postcode LIKE '%".$searchTerm->postcode."%') ";
                }
                if (!empty($searchTerm->city)){
                    $sql .= "AND (city LIKE '%".$searchTerm->city."%') ";
                }
                if (!empty($searchTerm->countrycode)){
                    $sql .= "AND (countrycode LIKE '%".$searchTerm->countrycode."%') ";
                }
                $sql .= "ORDER BY dist ASC LIMIT 10;";
                $stmt = $this->pdoPostgres->prepare($sql);
            }
            // execute the insert statement
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $results;
        }
        catch (\Exception $e){
            $searchTerm->code = 500;
            $searchTerm->message = 'Database error: '.$e->getMessage();
        }
    }

}