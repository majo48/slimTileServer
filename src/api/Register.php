<?php
/**
 * Created by PhpStorm.
 * User: mart
 * Date: 2018-12-21
 * Time: 07:09
 */

namespace App\api;

use PDO;
use Slim\Container;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Class Register
 * @package App\api
 *
 * Copyright (C) 2019  Martin Jonasse, see LICENCE.md.
 */
class Register
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
        $this->container->logger->info("/register request");

        // check for email
        $email = $request->getQueryParam('email');
        if (!empty($email)){

            // create guid (key)
            $guid = $this->getGUID(false);

            // send email confirmation to user
            $this->container->mymail->sendMail($email, $guid);

            // persist user info
            $this->container->mypostgres->registerUser($email, $guid);

            // register log message
            $this->container->logger->info("registered ".$email.' with key '.$guid);

            // thank user page
            return $this->container->renderer->render($response, 'thanks.phtml', $args);
        }

        // Render register view
        return $this->container->renderer->render($response, 'register.phtml', $args);
    }

    /**
     * Create GUID (globally unique identifier)
     * Credit: Kristof_Polleunis at yahoo dot com
     * @param $email string
     * @return string
     */
    private function getGUID($opt = true)
    {
        if( function_exists('com_create_guid') ){
            // if extension com_dotnet is installed (best)
            if( $opt ){ return com_create_guid(); }
            else { return trim( com_create_guid(), '{}' ); }
        }
        else {
            // else alternative (good enough)
            mt_srand( (double)microtime() * 10000 );
            $charid = strtoupper( md5(uniqid(rand(), true)) );
            $hyphen = chr( 45 );    // "-"
            $left_curly = $opt ? chr(123) : "";     //  "{"
            $right_curly = $opt ? chr(125) : "";    //  "}"
            $uuid = $left_curly
                . substr( $charid, 0, 8 ) . $hyphen
                . substr( $charid, 8, 4 ) . $hyphen
                . substr( $charid, 12, 4 ) . $hyphen
                . substr( $charid, 16, 4 ) . $hyphen
                . substr( $charid, 20, 12 )
                . $right_curly;
            return $uuid;
        }
    }
}