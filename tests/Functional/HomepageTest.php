<?php

namespace Tests\Functional;

use Tests\Functional\GuzzleTrait;
use GuzzleHttp\Exception\ClientException;

class HomepageTest extends BaseTestCase
{
    use GuzzleTrait{
        setUp as protected;
        tearDown as protected;
    }

    /**
     * Test that the index route returns a rendered response containing the text
     * 'SlimFramework' but not a greeting
     */
    public function testGetHomepageWithoutName()
    {
        $response = $this->http->request('GET', '/');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('SlimFramework', (string)$response->getBody());
        $this->assertNotContains('Hello', (string)$response->getBody());
    }

    /**
     * Test that the about route returns a rendered response containing the text
     * 'SlimFramework' but not a greeting
     */
    public function testGetAboutWithoutName()
    {
        $response = $this->http->request('GET', '/about');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('SlimFramework', (string)$response->getBody());
        $this->assertNotContains('Hello', (string)$response->getBody());
    }

    /**
     * Test that the index route with optional name argument does not return a
     * rendered greeting
     */
    public function testGetHomepageWithGreeting()
    {
        try{
            $response = $this->http->request('GET', '/name'); // crash
            $this->assertNotEquals(404, $response->getStatusCode());
        }
        catch (ClientException $e){
            $response = $e->getResponse();
            $this->assertEquals(404, $response->getStatusCode());
        }
    }

    /**
     * Test that the index route won't accept a post request
     */
    public function testPostHomepageNotAllowed()
    {
        try{
            $response = $this->http->request('POST', '/', ['test']); // crash
            $this->assertNotEquals(405, $response->getStatusCode());
        }
        catch (ClientException $e){
            $response = $e->getResponse();
            $this->assertEquals(405, $response->getStatusCode());
        }
    }
}