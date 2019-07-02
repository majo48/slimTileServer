<?php

use Slim\Http\Request;
use Slim\Http\Response;
use App\api\v1\Geocode;
use App\api\v1\Download;
use App\api\Mailer;

// Routes

$app->get('/api/v1/geocode', '\App\api\v1\Geocode:index');

$app->get('/api/v1/reversegeocode', '\App\api\v1\ReverseGeocode:index');

$app->get('/api/v1/download', '\App\api\v1\Download:index');

$app->get('/{_:|about|home}', '\App\api\About:index');

$app->get('/register', '\App\api\Register:index');
