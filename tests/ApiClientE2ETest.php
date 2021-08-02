<?php

namespace DigiTicketsApiClientTests;

use DigiTicketsApiClient\ApiClient;
use DigiTicketsApiClient\Consts\ApiVersion;
use DigiTicketsApiClient\Exceptions\MalformedApiResponseException;
use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\ResponseInterface;

class ApiClientE2ETest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    private function makeApiClient(string $apiVersion = ApiVersion::V2): ApiClient
    {
        return new ApiClient(
            $apiVersion,
            $_ENV['API_URL']
        );
    }

    private function makeAuthenticatedApiClient(string $apiVersion = ApiVersion::V2): ApiClient
    {
        return $this->makeApiClient($apiVersion)->setApiKey($_ENV['API_KEY']);
    }

    public function testSetGetApiKey()
    {
        $apiClient = $this->makeApiClient();

        $this->assertNull($apiClient->getApiKey());
        $apiClient->setApiKey('abcdefg');
        $this->assertSame('abcdefg', $apiClient->getApiKey());
        $apiClient->setApiKey();
        $this->assertNull($apiClient->getApiKey());
    }

    public function testSetApiRootUrl()
    {
        $apiClient = $this->makeApiClient(ApiVersion::NONE);
        $apiClient->setApiRootUrl('https://downloads.dtapps.co.uk');

        $response = $apiClient->get('test.json');
        $result = $apiClient->parseResponse($response);
        $this->assertSame(['hello' => 'world'], $result);
    }

    /**
     * Make a request to the paymentmethods endpoint, which does not require authentication, to check we are
     * getting a sensible result.
     */
    public function testGetUnauthenticatedV2Endpoint()
    {
        $apiClient = $this->makeApiClient();
        $response = $apiClient->get('paymentmethods');
        $result = $apiClient->parseResponse($response);

        $this->assertGreaterThan(1, count($result));

        $cashPaymentMethods = array_values(
            array_filter(
                $result,
                function ($paymentMethod) {
                    return $paymentMethod['ref'] === 'cash';
                }
            )
        );

        $this->assertCount(1, $cashPaymentMethods);
        $this->assertSame('Cash', $cashPaymentMethods[0]['name']);
    }

    public function testGetUnauthenticatedV1Endpoint()
    {
        $apiClient = $this->makeApiClient(ApiVersion::V1);
        $response = $apiClient->get('paymentmethods');
        $result = $apiClient->parseResponse($response);

        $this->assertGreaterThan(1, count($result));

        $cashPaymentMethods = array_values(
            array_filter(
                $result,
                function ($paymentMethod) {
                    return $paymentMethod['ref'] === 'cash';
                }
            )
        );

        $this->assertCount(1, $cashPaymentMethods);
        $this->assertSame('Cash', $cashPaymentMethods[0]['name']);
    }

    public function testGetNonexistentEndpointThrowsException()
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage("resulted in a `405 Method Not Allowed` response");

        $apiClient = $this->makeApiClient();
        $apiClient->get('dogbirthdays');
    }

    public function testNonJsonEndpointThrowsException()
    {
        $this->expectException(MalformedApiResponseException::class);
        $this->expectExceptionMessage("json_decode error: Syntax error");

        // Create an API client to access the unversioned API root.
        $apiClient = new ApiClient(ApiVersion::NONE);
        // This is a response that just contains some text.
        $response = $apiClient->get('');
        $apiClient->parseResponse($response);
    }

    public function testNonJsonEndpointReturnsResponseInException()
    {
        $response = null;

        try {
            // Create an API client to access the unversioned API root.
            $apiClient = new ApiClient(ApiVersion::NONE);
            // This is a response that just contains some text.
            $response = $apiClient->get('');
            $apiClient->parseResponse($response);
        } catch (MalformedApiResponseException $e) {
            $response = $e->getResponse();
        }

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame("DigiTickets API - You've arrived!<br />\n", (string) $response->getBody());
    }

    public function testAuthenticatedEndpointThrowsExceptionWithoutApiKey()
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('You must supply a valid apiKey key with each request.');

        $apiClient = $this->makeApiClient();
        $apiClient->get('branches');
    }

    public function testAuthenticatedEndpointReturnsResponse()
    {
        $apiClient = $this->makeAuthenticatedApiClient();

        $response = $apiClient->get('branches');
        $branches = $apiClient->parseResponse($response);

        $this->assertNotEmpty($branches);
        $this->assertNotEmpty($branches[0]['name']);
    }

    public function testPost()
    {
        $apiClient = $this->makeAuthenticatedApiClient();

        $response = $apiClient->post(
            'stashedcarts',
            [
                'guid' => 'abcdefg',
                'thirdPartyRef' => 'abcdefg',
                'stashedCartData' => '{"hello":"world"}',
                'deviceGuid' => 'abcdefg',
            ]
        );
        $this->assertSame(200, $response->getStatusCode());
        $result = $apiClient->parseResponse($response);
        $this->assertSame('abcdefg', $result['thirdPartyRef']);
    }

    public function testDelete()
    {
        $apiClient = $this->makeAuthenticatedApiClient();

        $response = $apiClient->delete(
            'stashedcarts/abcdefg'
        );
        $this->assertSame(200, $response->getStatusCode());
        $result = $apiClient->parseResponse($response);
        $this->assertTrue($result['success']);
    }

    public function testPut()
    {
        $apiClient = $this->makeAuthenticatedApiClient();

        $device = $this->getTestDevice($apiClient);
        $deviceID = $device['deviceID'];

        $response = $apiClient->put(
            'devices/'.$deviceID.'/printconfig',
            [
                'printConfig' => '{"hello":"world"}',
            ]
        );
        $this->assertSame(200, $response->getStatusCode());
        $result = $apiClient->parseResponse($response);
        $this->assertTrue($result['success']);
    }

    public function testPatch()
    {
        $apiClient = $this->makeAuthenticatedApiClient();

        $device = $this->getTestDevice($apiClient);
        $deviceID = $device['deviceID'];

        $response = $apiClient->patch(
            'devices/'.$deviceID.'/info',
            [
                'machineIdentifier' => 'abcdefg',
            ]
        );
        $this->assertSame(200, $response->getStatusCode());
        $result = $apiClient->parseResponse($response);
        $this->assertSame('abcdefg', $result['machineIdentifier']);
    }

    private function getTestDevice(ApiClient $apiClient): array
    {
        // Get available devices.
        $response = $apiClient->get('devices');
        $devices = $apiClient->parseResponse($response);
        if (empty($devices)) {
            $this->markTestSkipped("Can't test devices endpoint because this company has no devices.");
        }

        return $devices[0];
    }
}
