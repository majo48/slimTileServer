<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes

$app->get('/', function (Request $request, Response $response, array $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    // Render index view
    return $this->renderer->render($response, 'index.phtml', $args);
});

$app->get('/register',
    function (Request $request, Response $response, array $args) {
        // Sample log message
        $this->logger->info("/register");

        $email = $request->getQueryParam('email');
        if (!empty($email)){
            return $this->renderer->render($response, 'thanks.phtml', $args);
        }

        // Render register view
        return $this->renderer->render($response, 'register.phtml', $args);
    });
