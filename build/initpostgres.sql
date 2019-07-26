-- script for creating a virgin database as 'postgres' user
-- credit: https://dba.stackexchange.com/questions/221209
--
-- USER mart
-- DATABASE gis
-- TABLE gwr, register, downloads, cities
--
-- --------------------------------------------
-- RUN THIS SCRIPT FOR EACH NEW SERVER INSTANCE
-- --------------------------------------------
-- to run this script:
-- 1.SSH to remote server
-- 2.$ sudo -u postgres psql
-- 3.# \i /srv/slim/build/initpostgres.sql
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
CREATE EXTENSION fuzzystrmatch;

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
    countrycode VARCHAR(8) NOT NULL,
    gwrId VARCHAR(16),
    hash VARCHAR(32) NOT NULL,
    lat VARCHAR(16),
    lon VARCHAR(16),
    geom geometry(POINT)
);

DROP TABLE IF EXISTS register;
CREATE TABLE register (
    id SERIAL PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    userkey VARCHAR(100) UNIQUE NOT NULL,
    registerdatetime VARCHAR(20),
    memo TEXT
);
INSERT INTO register(username,userkey)
VALUES('default','da98c7a446274dbe82b8f13667848952');

DROP TABLE IF EXISTS downloads;
CREATE TABLE downloads (
    id SERIAL PRIMARY KEY,
    countrycode VARCHAR(8) UNIQUE NOT NULL,
    hyperlink VARCHAR(120) NOT NULL,
    timestamp VARCHAR(20) NOT NULL
);

DROP TABLE IF EXISTS cities;
CREATE TABLE cities (
    id SERIAL PRIMARY KEY,
    city VARCHAR(50) NOT NULL,
    countrycode VARCHAR(8) NOT NULL,
    lat VARCHAR(16),
    lon VARCHAR(16),
    geom geometry(POINT)
);

DROP TABLE IF EXISTS postcodes;
CREATE TABLE postcodes (
    id SERIAL PRIMARY KEY,
    postcode VARCHAR(16) NOT NULL,
    city VARCHAR(50) NOT NULL,
    countrycode VARCHAR(8) NOT NULL,
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
ALTER TABLE register OWNER TO mart;
ALTER TABLE downloads OWNER TO mart;
ALTER TABLE geometry_columns OWNER TO mart;
ALTER TABLE spatial_ref_sys OWNER TO mart;

