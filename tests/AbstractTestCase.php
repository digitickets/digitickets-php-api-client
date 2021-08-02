<?php

namespace DigiTicketsApiClientTests;

use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;

class AbstractTestCase extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $dotenv = Dotenv::createImmutable(__DIR__.'/../');
        $dotenv->load();
    }
}
