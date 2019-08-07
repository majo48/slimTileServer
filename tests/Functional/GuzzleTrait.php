<?php


namespace Tests\Functional;

use GuzzleHttp;

/**
 * No TDD. We don't believe in Test Driven Development, but do believe in
 * strongly in System Tests.
 * This module (with Guzzle( allows to test the server defined below (base_uri).
 * The Tests can be implemented in the development environment, in as far this
 * has the PHP package installed. Certainly the Tests can be performed in the
 * target server which hosts the application.
 *
 * Trait GuzzleTrait
 * @package Tests\Functional
 */
trait GuzzleTrait
{
    private $http;

    public function setUp()
    {
        $this->http = new GuzzleHttp\Client(['base_uri' => 'http://tileserver.ch']);
    }

    public function tearDown() {
        $this->http = null;
    }
}