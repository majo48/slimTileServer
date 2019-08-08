<?php
/**
 * Created by PhpStorm.
 * User: mart
 * Date: 27/11/18
 * Time: 10:08
 */

namespace Tests\Functional;

use Tests\Functional\GuzzleTrait;

class RegisterTest extends BaseTestCase
{
    use GuzzleTrait{
        setUp as protected;
        tearDown as protected;
    }

    /**
     * Test that the register route returns a rendered response containing
     * Email and submit controls plus default key and "terms of use"
     */
    public function testGetRegisterPage()
    {
        $response = $this->http->request('GET', '/register');

        // tests ...
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('Email', (string)$response->getBody());
        $this->assertContains('Submit', (string)$response->getBody());
        $this->assertContains('da98c7a446274dbe82b8f13667848952', (string)$response->getBody());
        $this->assertContains('Terms of Use', (string)$response->getBody());
    }

    /**
     * Test that the register route with email parameter returns a rendered
     * response containing "thank you" and "terms of use"
     */
    public function testThanksRegisterPage()
    {
        $response = $this->http->request('GET', '/register?email=john.doe%40gmail.com');

        // tests ...
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('Thank you', (string)$response->getBody());
        $this->assertContains('Terms of Use', (string)$response->getBody());
    }
}