<?php

namespace Abicart\V2;

use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    public function testClient()
    {
        $client = new Client();
        $this->assertInstanceOf('Abicart\V2\Client', $client);
    }
}
