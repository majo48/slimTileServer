<?php

namespace Tests\Functional;

use Tests\Functional\GuzzleTrait;
use GuzzleHttp\Exception\ClientException;

/**
 * Class GeocodeTest
 * @package Tests\Functional
 *
 *  Copyright (C) 2019  Martin Jonasse, see LICENCE.md.
 */
class GeocodeTest extends BaseTestCase
{
    use GuzzleTrait{
        setUp as protected;
        tearDown as protected;
    }

    /**
     * Test that the geocode query returns the defined json response for the
     * Städtli Metzg @ Zugerstrasse 43, 6330 Cham, Schweiz
     */
    public function testGeocodeAddressStructureAndContent()
    {
        $adr = 'Zugerstrasse+43,+6330+Cham';
        $key = 'da98c7a446274dbe82b8f13667848952';
        $response = $this->http->request(
            'GET',
            "/api/v1/geocode?adr=$adr&key=$key"
        );
        $this->assertEquals(200, $response->getStatusCode());

        // assert top level
        $data = json_decode($response->getBody(true), true); // array
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('status_text', $data);
        $this->assertArrayHasKey('msecs', $data);
        $this->assertArrayHasKey('addresses', $data);

        // assert address level
        $address = $data['addresses'][0];
        $this->assertArrayHasKey('address_id', $address);
        $this->assertArrayHasKey('address_type', $address);
        $this->assertArrayHasKey('streetnumber', $address);
        $this->assertArrayHasKey('street', $address);
        $this->assertArrayHasKey('postcode', $address);
        $this->assertArrayHasKey('city', $address);
        $this->assertArrayHasKey('country', $address);
        $this->assertArrayHasKey('countrycode', $address);
        $this->assertArrayHasKey('latitude', $address);
        $this->assertArrayHasKey('longitude', $address);
        $this->assertArrayHasKey('location_type', $address);
        $this->assertArrayHasKey('source', $address);
        $this->assertArrayHasKey('licence', $address);
        $this->assertArrayHasKey('version', $address);
        $this->assertArrayHasKey('display', $address);

        // assert address content
        $this->assertEquals( $address['street'], 'Zugerstrasse');
        $this->assertEquals( $address['streetnumber'], '43');
        $this->assertEquals( $address['postcode'], '6330');
        $this->assertEquals( $address['city'], 'Cham');
        $this->assertEquals( $address['country'], 'Schweiz');
    }

    /**
     * Test that the geocode query returns the defined json response for the
     * Cities @ ,Oberwil
     */
    public function testGeocodeStreetOnlyAddress()
    {
        $adr = 'Bahnhofstrasse';
        $key = 'da98c7a446274dbe82b8f13667848952';
        $response = $this->http->request(
            'GET',
            "/api/v1/geocode?adr=$adr&key=$key"
        );
        $this->assertEquals(200, $response->getStatusCode());

        // assert top level
        $data = json_decode($response->getBody(true), true); // array
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('status_text', $data);
        $this->assertArrayHasKey('msecs', $data);
        $this->assertArrayHasKey('addresses', $data);

        // assert that at least one address contains street
        $addresses = $data['addresses'];
        $count = 0;
        foreach ($addresses as $address){
            $this->assertArrayHasKey('street', $address);
            $street = $address['street'];
            $count = ($street === $adr)? $count+1 : $count;
        }
        $this->assertNotEquals($count,0);
    }

    /**
     * Test that the geocode query returns the defined json response for the
     * Cities @ ,Oberwil
     */
    public function testGeocodeCityOnlyAddress()
    {
        $adr = 'Oberwil';
        $key = 'da98c7a446274dbe82b8f13667848952';
        $response = $this->http->request(
            'GET',
            "/api/v1/geocode?adr=,$adr&key=$key"
        );
        $this->assertEquals(200, $response->getStatusCode());

        // assert top level
        $data = json_decode($response->getBody(true), true); // array
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('status_text', $data);
        $this->assertArrayHasKey('msecs', $data);
        $this->assertArrayHasKey('addresses', $data);

        // assert that at least one address contains city
        $addresses = $data['addresses'];
        $count = 0;
        foreach ($addresses as $address){
            $this->assertArrayHasKey('city', $address);
            $city = substr($address['city'], 0, strlen($adr));
            $count = ($city === $adr)? $count+1 : $count;
        }
        $this->assertNotEquals($count,0);
    }
}