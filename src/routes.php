<?php

use Slim\Http\Request;
use Slim\Http\Response;
use App\api\v1\Geocode;
use App\api\Register;

// Routes

$app->get('/api/v1/geocode/{text}', '\App\api\v1\Geocode:index');

$app->get('/api/v1/reversegeocode/{text}', '\App\api\v1\ReverseGeocode:index');

$app->get('/{_:|about}',
    function (Request $request, Response $response, array $args)
    {
        // Sample log message
        $this->logger->info("Slim-Skeleton (or about)");

        // Render index view
        return $this->renderer->render($response, 'index.phtml', [
            'url' => $request->getUri()->getScheme().'://'.
                     $request->getUri()->getHost().'/register'
        ]);
    });

$app->get('/register',
    function (Request $request, Response $response, array $args)
    {
        // Sample log message
        $this->logger->info("/register");
        // check for email
        $email = $request->getQueryParam('email');
        if (!empty($email)){
            // register email in the app
            $register = new Register($this);
            $register->registerEmail($email);
            // thank user
            return $this->renderer->render($response, 'thanks.phtml', $args);
        }
        // Render register view
        return $this->renderer->render($response, 'register.phtml', $args);
    });
