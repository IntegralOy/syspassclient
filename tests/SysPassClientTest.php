<?php

declare(strict_types=1);

/**
 * This file is part of the sysPassClient package.
 *
 * (c) Integral Oy <integral@integral.fi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Integral\SysPass\Tests;

use GuzzleHttp\Client;
use Integral\SysPass\SysPassClient;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class SysPassClientTest extends TestCase
{
    public function testSearch()
    {
        $contents = <<<JSON
{
  "result": [
    {
        "account_login": "login",
        "account_url": "url",
        "account_id": "id",
        "account_name": "name"
    }
  ]
}
JSON;
        $client = $this->createMockClient($contents);
        $sysPassClient = new SysPassClient('token', 'password', 'host', $client);

        $data = $sysPassClient->search('x');

        $this->assertInternalType('array', $data->result);
        $this->assertCount(1, $data->result);
        $this->assertInstanceOf(\stdClass::class, $data->result[0]);
        $this->assertEquals('login', $data->result[0]->account_login);
        $this->assertEquals('url', $data->result[0]->account_url);
        $this->assertEquals('id', $data->result[0]->account_id);
        $this->assertEquals('name', $data->result[0]->account_name);
    }

    public function testGetPassword()
    {
        $contents = <<<JSON
{
  "result":
    {
        "details": {
            "account_login": "login",
            "account_url": "url",
            "account_name": "name",
            "tags": ["a", "b"]
        
        },
        "pass": "password"
    }
}
JSON;

        $client = $this->createMockClient($contents);
        $sysPassClient = new SysPassClient('token', 'password', 'host', $client);

        $result = $sysPassClient->getPassword('x');

        $this->assertEquals('name', $result['name']);
        $this->assertEquals('login', $result['username']);
        $this->assertEquals('password', $result['password']);
        $this->assertEquals('url', $result['url']);
    }

    private function createMockClient(string $contents): Client
    {
        $body = $this->createMock(StreamInterface::class);
        $body->method('getContents')->willReturn($contents);
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($body);
        $client = $this->createMock(Client::class);
        $client->method('request')->willReturn($response);

        return $client;
    }
}
