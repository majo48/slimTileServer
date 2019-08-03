-- script for upgrading a postgres database as 'postgres' user
--
-- USER 'mart'
-- DATABASE 'gis'
-- TABLE 'cities' and 'postalcodes', aggregated from data in table 'gwr'
--
-- UPGRADE 'cities' and 'postcodes',
-- ------------------------------------------
-- PREREQUISITE: TABLE 'GWR' HAS CURRENT DATA
-- ------------------------------------------
-- to run this script:
-- 1.SSH to remote server
-- 2.$ sudo -u postgres psql
-- 3.# \i /srv/slim/build/upgradepostgres.sql
--
-- remove old items
DROP TABLE IF EXISTS cities;
CREATE TABLE cities (
    id SERIAL PRIMARY KEY,
    city VARCHAR(50) NOT NULL,
    countrycode VARCHAR(8) NOT NULL,
    lat DOUBLE PRECISION,
    lon DOUBLE PRECISION,
    geom geometry(POINT)
);
--
-- add cities from table 'gwr'
INSERT INTO cities(city, countrycode, lat, lon)
(
    SELECT
        city,
        countrycode,
        TO_CHAR(avg(CAST (lat AS DOUBLE PRECISION)),'99.9999999') AS lat,
        TO_CHAR(avg(CAST (lon AS DOUBLE PRECISION)),'99.9999999') AS lon
    FROM gwr
    WHERE (city <> '') IS TRUE
    GROUP BY city, countrycode
    ORDER BY city ASC
);
-- update field 'geom' in table 'cities'
UPDATE cities
SET geom = ST_SetSRID( ST_MakePoint( LON, LAT ), 4326 );
--
-- remove old items
DROP TABLE IF EXISTS postcodes;
CREATE TABLE postcodes (
    id SERIAL PRIMARY KEY,
    postcode VARCHAR(16) NOT NULL,
    city VARCHAR(50) NOT NULL,
    countrycode VARCHAR(8) NOT NULL,
    lat DOUBLE PRECISION,
    lon DOUBLE PRECISION,
    geom geometry(POINT)
);
--
-- add postalcodes from table 'gwr'
INSERT INTO postcodes(postcode, city, countrycode, lat, lon)
(
    SELECT
        postcode,
        city,
        countrycode,
        TO_CHAR(avg(CAST (lat AS DOUBLE PRECISION)),'99.9999999') AS lat,
        TO_CHAR(avg(CAST (lon AS DOUBLE PRECISION)),'99.9999999') AS lon
    FROM gwr
    WHERE (city <> '') IS TRUE
    AND (postcode <> '') IS TRUE
    GROUP BY postcode, city, countrycode
    ORDER BY postcode ASC, city ASC
);
-- update field 'geom' in table 'postcodes'
UPDATE postcodes
SET geom = ST_SetSRID( ST_MakePoint( LON, LAT ), 4326 );
