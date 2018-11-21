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

namespace Integral\SysPass;

use GuzzleHttp\Client;

/**
 * Class SysPassClient
 * @package Integral\SysPass
 */
class SysPassClient
{
    /**
     * @var string
     */
    protected $token = '';

    /**
     * @var string
     */
    protected $password = '';

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $host = '';

    /**
     * @var int
     */
    private $requestCount = 1;

    public function __construct(string $token, string $password, string $sysPassHost, Client $client)
    {
        $this->token = $token;
        $this->password = $password;
        $this->host = $sysPassHost;

        $this->client = $client;
    }

    /**
     * @param string $search
     * @return \stdClass
     * @throws \RuntimeException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function search(string $search) : \stdClass
    {
        $request = $this->createJsonRpcRequest('getAccountSearch', false);
        $request['params']['text'] = $search;
        $request['params']['count'] = 30;

        $res = $this->client->request('POST', $this->host, [
            'verify' => false,
            'json' => $request
        ]);

        if ($res->getStatusCode() !== 200) {
            throw new \RuntimeException('Error while searching for accounts: '.$res->getBody());
        }

        $data = json_decode($res->getBody()->getContents());

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException(json_last_error_msg());
        }

        if (isset($data->error->message)) {
            throw new \RuntimeException($data->error->message);
        }

        return $data;
    }
    
    /**
     * @param string $accountId
     * @return array
     * @throws \RuntimeException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getPassword(string $accountId) : array
    {
        $request = $this->createJsonRpcRequest('getAccountPassword', false);
        $request['params']['tokenPass'] = $this->password;
        $request['params']['id'] = $accountId;
        $request['params']['details'] = true;

        $res = $this->client->request('POST', $this->host, [
            'verify' => false,
            'json' => $request
        ]);

        if ($res->getStatusCode() !== 200) {
            throw new \RuntimeException('Error while fetching password: '.$res->getBody());
        }

        $data = json_decode($res->getBody()->getContents());

        if (isset($data->error->message)) {
            throw new \RuntimeException($data->error->message);
        }

        $username = (string) $data->result->details->account_login;
        $url = trim((string) $data->result->details->account_url);
        $tags = (array) $data->result->details->tags;
        $tagsStr = trim(implode(', ', $tags));

        if (\in_array('ssh', $tags, true) || strpos($url, 'ssh://') !== false) {
            $url = 'ssh ' .escapeshellarg($username.'@'.preg_replace('/^ssh:\/\//', '', $url));
        }

        return [
            'name' => $data->result->details->account_name,
            'username' => $username,
            'password' => $data->result->pass,
            'url' => $url,
            'tags' => $tagsStr
        ];
    }

    /**
     * @param string $method
     * @param bool $usePassword
     * @return array
     */
    private function createJsonRpcRequest($method, $usePassword = false) : array
    {
        $rpc = [
            'jsonrpc' => '2.0',
            'id' => $this->requestCount,
            'method' => $method,
            'params' => [
                'authToken' => $this->token
            ]
        ];

        ++$this->requestCount;

        if ($usePassword === true) {
            $rpc['params']['tokenPass'] = $this->password;
        }

        return $rpc;
    }
}
