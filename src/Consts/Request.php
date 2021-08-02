<?php

namespace DigiTicketsApiClient\Consts;

/**
 * This contains some constants to aid with making HTTP requests.
 * These are copied from Symfony\Component\HttpFoundation which is not a dependency because it would be overkill
 * to require it just for these constants.
 */
class Request
{
    const METHOD_HEAD = 'HEAD';
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_PATCH = 'PATCH';
    const METHOD_DELETE = 'DELETE';
    const METHOD_PURGE = 'PURGE';
    const METHOD_OPTIONS = 'OPTIONS';
    const METHOD_TRACE = 'TRACE';
    const METHOD_CONNECT = 'CONNECT';
}
