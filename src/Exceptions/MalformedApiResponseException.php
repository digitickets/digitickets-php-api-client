<?php

namespace DigiTicketsApiClient\Exceptions;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class MalformedApiResponseException extends InvalidArgumentException
{
    /**
     * @var ResponseInterface
     */
    private $response;

    public function __construct(
        ResponseInterface $response,
        $message = "",
        $code = 0,
        Throwable $previous = null
    ) {
        parent::__construct(
            $message,
            $code,
            $previous
        );

        $this->response = $response;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
