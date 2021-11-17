<?php

namespace DigiTicketsApiClient;

use DigiTicketsApiClient\Consts\ApiVersion;
use GenericApiClient\Exceptions\MalformedApiResponseException;
use Psr\Http\Message\ResponseInterface;

class ApiClient extends \GenericApiClient\ApiClient
{
    const DEFAULT_API_URL = 'https://api.digitickets.co.uk/';

    public function __construct(
        string $apiVersion = ApiVersion::V2,
        string $apiUrl = self::DEFAULT_API_URL
    ) {
        parent::__construct($apiUrl.($apiVersion ? $apiVersion.'/' : ''));
    }

    /**
     * @return string|null
     */
    public function getApiKey()
    {
        return $this->defaultQueryParameters['apiKey'] ?? null;
    }

    public function setApiKey(string $apiKey = null): self
    {
        if ($apiKey) {
            $this->addDefaultQueryParameter('apiKey', $apiKey);
        } else {
            $this->removeDefaultQueryParameter('apiKey');
        }

        return $this;
    }

    public function parseResponse(ResponseInterface $response): array
    {
        try {
            return parent::parseResponse($response);
        } catch (MalformedApiResponseException $exception) {
            throw new \DigiTicketsApiClient\Exceptions\MalformedApiResponseException(
                $exception->getResponse(),
                $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }
    }
}
