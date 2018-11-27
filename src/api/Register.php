<?php
/**
 * Created by PhpStorm.
 * User: mart
 * Date: 27/11/18
 * Time: 14:22
 */

namespace App\api;

use Slim\Container;

class Register
{
    protected $email;

    protected $container;

    /**
     * Register constructor.
     * @param $c Container
     */
    public function __construct($c)
    {
        $this->container = $c;
    }

    /**
     * Register constructor.
     * @param $email string
     */
    public function registerEmail($email)
    {
        $this->email = $email;
    }
}