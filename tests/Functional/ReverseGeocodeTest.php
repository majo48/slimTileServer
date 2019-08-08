<?php

namespace Tests\Functional;

use Tests\Functional\GuzzleTrait;
use GuzzleHttp\Exception\ClientException;

class ReverseGeocodeTest extends BaseTestCase
{
    use GuzzleTrait{
        setUp as protected;
        tearDown as protected;
    }
    /**
     * Test that the reverse geocode query returns the defined json response for
     * the Städtli Metzg @ Zugerstrasse 43, 6330 Cham, Schweiz
     */
    public function testReverseGeocodeAddressStructureAndContent()
    {
        $lat = '47.1821327';
        $lng = '8.4659836';
        $key = 'da98c7a446274dbe82b8f13667848952';
        $response = $this->http->request(
            'GET',
            "/api/v1/reversegeocode?lat=$lat&lng=$lng&key=$key"
        );
        $this->assertEquals(200, $response->getStatusCode());

        // assert top level
        $data = json_decode($response->getBody(true), true); // array
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('status_text', $data);
        $this->assertArrayHasKey('msecs', $data);
        $this->assertArrayHasKey('addresses', $data);
        $this->assertGreaterThanOrEqual(count($data), 10); // 10 results

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
}