<?php

namespace DigiTicketsApiClient;

use DigiTicketsApiClient\Consts\ApiVersion;
use DigiTicketsApiClient\Consts\Request;
use DigiTicketsApiClient\Exceptions\MalformedApiResponseException;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;

class ApiClient
{
    const DEFAULT_API_URL = 'https://api.digitickets.co.uk/';

    /**
     * @var string|null
     */
    private $apiKey;

    /**
     * @var string
     */
    private $apiRootUrl;

    /**
     * @var Client
     */
    private $guzzleClient;

    public function __construct(
        string $apiVersion = ApiVersion::V2,
        string $apiUrl = self::DEFAULT_API_URL
    ) {
        $this->guzzleClient = new Client();

        // Use the default API root URL. It can be overridden with a call to $this->setApiRootUrl.
        // If an ApiVersion is provided it will be appended to the URl. e.g. (/v2/).
        $this->apiRootUrl = $apiUrl.($apiVersion ? $apiVersion.'/' : '');
    }

    /**
     * @return string|null
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    public function setApiKey(string $apiKey = null): self
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * Makes a request to the API and returns the response. The returned value is a PSR ResponseInterface, so that you
     * can access the status and headers of the response too.
     * To access the actual data pass that response to $this->parseResponse().
     *
     * @param string $method
     * @param string $endpoint
     * @param array $bodyParameters Keys/values to be sent as part of the request body (e.g. for POST requests).
     *     Does nothing for GET requests.
     * @param array $queryParameters Keys/values to be appended to the query string in the URL.
     * @param array $headers
     *
     * @return ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function request(
        string $method,
        string $endpoint,
        array $bodyParameters = [],
        array $queryParameters = [],
        array $headers = []
    ): ResponseInterface {
        // Allow leading or no leading slash in the provided endpoint.
        $url = rtrim($this->apiRootUrl, '/').'/'.ltrim($endpoint, '/');

        // Add the apiKey to the request if one has not been provided already.
        if (!empty($this->getApiKey()) && !array_key_exists('apiKey', $queryParameters)) {
            $queryParameters['apiKey'] = $this->getApiKey();
        }

        if ($method === Request::METHOD_GET) {
            $body = null;
        } else {
            $headers['content-type'] = 'application/x-www-form-urlencoded';
            $body = http_build_query($bodyParameters);
        }

        $url .= (strpos($url, '?') !== false ? '&' : '?').http_build_query($queryParameters);

        $request = new \GuzzleHttp\Psr7\Request(
            $method,
            $url,
            $headers,
            $body
        );

        return $this->guzzleClient->send(
            $request,
            [
                // Don't throw exceptions for "bad" responses. Return them as a response object.
                RequestOptions::HTTP_ERRORS => false,
            ]
        );
    }

    public function get(
        string $endpoint,
        array $queryParameters = [],
        array $headers = []
    ): ResponseInterface {
        return $this->request(Request::METHOD_GET, $endpoint, [], $queryParameters, $headers);
    }

    public function post(
        string $endpoint,
        array $bodyParameters = [],
        array $queryParameters = [],
        array $headers = []
    ): ResponseInterface {
        return $this->request(Request::METHOD_POST, $endpoint, $bodyParameters, $queryParameters, $headers);
    }

    public function put(
        string $endpoint,
        array $bodyParameters = [],
        array $queryParameters = [],
        array $headers = []
    ): ResponseInterface {
        return $this->request(Request::METHOD_PUT, $endpoint, $bodyParameters, $queryParameters, $headers);
    }

    public function patch(
        string $endpoint,
        array $bodyParameters = [],
        array $queryParameters = [],
        array $headers = []
    ): ResponseInterface {
        return $this->request(Request::METHOD_PATCH, $endpoint, $bodyParameters, $queryParameters, $headers);
    }

    public function delete(
        string $endpoint,
        array $bodyParameters = [],
        array $queryParameters = [],
        array $headers = []
    ): ResponseInterface {
        return $this->request(Request::METHOD_DELETE, $endpoint, $bodyParameters, $queryParameters, $headers);
    }

    /**
     * Decode the JSON in a response body and return it as an array.
     *
     * @param ResponseInterface $response
     *
     * @return array
     * @throws MalformedApiResponseException
     */
    public function parseResponse(ResponseInterface $response): array
    {
        try {
            return \GuzzleHttp\json_decode($response->getBody(), true);
        } catch (InvalidArgumentException $e) {
            // Re-throwing the same exception but containing the response so the problem can be debugged.
            throw new MalformedApiResponseException(
                $response,
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}
