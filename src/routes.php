<?php

use Slim\Http\Request;
use Slim\Http\Response;
use App\api\v1\Geocode;
use App\api\Mailer;

// Routes

$app->get('/api/v1/geocode/{text}', '\App\api\v1\Geocode:index');

$app->get('/api/v1/reversegeocode/{text}', '\App\api\v1\ReverseGeocode:index');

$app->get('/{_:|about}', '\App\api\About:index');

$app->get('/register', '\App\api\Register:index');
