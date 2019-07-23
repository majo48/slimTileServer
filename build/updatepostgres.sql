-- script for updating a osmap database as 'postgres' user
--
-- USER 'mart'
-- DATABASE 'gis'
-- TABLE 'cities' and 'postalcodes', aggregated from data in table 'gwr'
--
-- UPDATE 'cities' and 'postcodes',
-- Do this once a year, the data herein is not very volatile.
-- ------------------------------------------
-- PREREQUISITE: TABLE 'GWR' HAS CURRENT DATA
-- ------------------------------------------
-- to run this script:
-- 1.SSH to remote server
-- 2.$ sudo -u postgres psql
-- 3.# \i /srv/slim/build/updatepostgres.sql
--
-- remove old items
DELETE FROM cities;
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
SET geom = ST_SetSRID(
    ST_MakePoint( cast(LON AS float), cast(LAT AS float) ),
    4326
);
-- remove old items
DELETE FROM postcodes;
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
SET geom = ST_SetSRID(
    ST_MakePoint( cast(LON AS float), cast(LAT AS float) ),
    4326
);
