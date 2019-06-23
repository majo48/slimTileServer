-- script for creating a virgin database as 'postgres' user
-- credit: https://dba.stackexchange.com/questions/221209
-- USER mart
-- DATABASE gis
-- TABLE gwr
--
DROP ROLE IF EXISTS gisgroup;
CREATE ROLE gisgroup NOLOGIN;

DROP DATABASE IF EXISTS gis;
CREATE DATABASE gis;

-- connect to database
\c gis

-- set privileges for objects created in the future
ALTER DEFAULT PRIVILEGES FOR ROLE postgres GRANT ALL ON TABLES TO gisgroup;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres GRANT ALL  ON SEQUENCES TO gisgroup;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres GRANT ALL  ON FUNCTIONS TO gisgroup;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres GRANT ALL  ON TYPES TO gisgroup;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres GRANT ALL  ON SCHEMAS TO gisgroup;

-- create postgis extensions
CREATE EXTENSION postgis;
CREATE EXTENSION hstore;

-- create objects
DROP TABLE IF EXISTS gwr;
CREATE TABLE gwr (
    id SERIAL PRIMARY KEY,
    street VARCHAR(50) NOT NULL,
    number VARCHAR(50) NOT NULL,
    unit VARCHAR(16),
    city VARCHAR(50) NOT NULL,
    district VARCHAR(50),
    region VARCHAR(16) NOT NULL,
    postcode VARCHAR(16) NOT NULL,
    gwrId VARCHAR(16),
    hash VARCHAR(32) NOT NULL,
    lat VARCHAR(16),
    lon VARCHAR(16),
    geom geometry(POINT)
);

-- Create more objects....

-- add user 
DROP ROLE IF EXISTS mart;
CREATE ROLE mart WITH INHERIT ENCRYPTED PASSWORD 'abc123' IN ROLE gisgroup;
ALTER ROLE mart WITH LOGIN;

ALTER TABLE gwr OWNER TO mart;
ALTER TABLE geometry_columns OWNER TO mart;
ALTER TABLE spatial_ref_sys OWNER TO mart;

