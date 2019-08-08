<?php
/**
 * Created by PhpStorm.
 * User: mart
 * Date: 2018-12-21
 * Time: 06:27
 */

namespace App\api;

use Slim\Container;

/**
 * Class About
 * @package App\api
 *
 * Copyright (C) 2019  Martin Jonasse, see LICENCE.md.
 */
class About
{
    /** @var Container $container */
    protected   $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function index($request, $response, $args)
    {
        // request log message
        $this->container->logger->info("/about request");

        // Render index view
        return $this->container->renderer->render($response, 'index.phtml', [
            'url' => $request->getUri()->getScheme().'://'.
                $request->getUri()->getHost().'/register'
        ]);
    }
}