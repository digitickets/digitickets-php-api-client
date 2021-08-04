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

    /**
     * Test the setApiKey and getApiKey methods.
     */
    public function testSetGetApiKey()
    {
        $apiClient = $this->makeApiClient();

        $this->assertNull($apiClient->getApiKey());
        $apiClient->setApiKey('abcdefg');
        $this->assertSame('abcdefg', $apiClient->getApiKey());
        $apiClient->setApiKey();
        $this->assertNull($apiClient->getApiKey());
    }

    /**
     * Make a request to the V2 paymentmethods endpoint, which does not require authentication, to check we are
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

    /**
     * Test a simple GET request to a V1 endpoint.
     */
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

    /**
     * Test that a request to an endpoint that does not exist throws a suitable exception.
     */
    public function testGetNonexistentEndpointThrowsException()
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage("resulted in a `405 Method Not Allowed` response");

        $apiClient = $this->makeApiClient();
        $apiClient->get('dogbirthdays');
    }

    /**
     * Test that an exception is thrown when a non-JSON response is sent to parseResponse.
     */
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

    /**
     * Test that the raw Response is accessible in case of an exception in parseResponse.
     */
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

    /**
     * Test endpoints that require an apiKey throw a suitable exception if one is not supplied.
     */
    public function testAuthenticatedEndpointThrowsExceptionWithoutApiKey()
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('You must supply a valid');

        $apiClient = $this->makeApiClient();
        $apiClient->get('branches');
    }

    /**
     * Test a GET request to an endpoint that requires an apiKey.
     */
    public function testAuthenticatedEndpointReturnsResponse()
    {
        $apiClient = $this->makeAuthenticatedApiClient();

        $response = $apiClient->get('branches');
        $branches = $apiClient->parseResponse($response);

        $this->assertNotEmpty($branches);
        $this->assertNotEmpty($branches[0]['name']);
    }

    /**
     * Test a POST request.
     */
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

    /**
     * Test a DELETE request.
     */
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

    /**
     * Test a PUT request.
     */
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

    /**
     * Test a PATCH request.
     */
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

    /**
     * Test setting an alternate API url.
     */
    public function testAlternateApiUrl()
    {
        $apiClient = new ApiClient(
            ApiVersion::NONE,
            'https://downloads.dtapps.co.uk'
        );

        $response = $apiClient->get('test.json');
        $result = $apiClient->parseResponse($response);
        $this->assertSame(['hello' => 'world'], $result);
    }

    /**
     * Get a device from the /devices endpoint that can be used in some tests.
     *
     * @param ApiClient $apiClient
     *
     * @return array
     */
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
