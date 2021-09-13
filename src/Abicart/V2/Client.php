<?php

namespace Abicart\V2;

use GuzzleHttp\{
    Client as Guzzle,
    ClientInterface,
    HandlerStack,
    Middleware,
    MessageFormatter
};
use Psr\Log\{
    LoggerInterface,
    NullLogger
};
use Psr\Http\Message\{
    RequestInterface,
    ResponseInterface
};

/**
 * Abicart API v2 client
 */
class Client
{
    private static $defaults = [
        'logger'          => null,
        'base_uri'        => 'https://api.abicart.com/v2/',
        'allow_redirects' => false,
        'parse_result'    => false,
    ];

    private $config;
    private $client;
    private $authorization;

    /**
     * Create client.
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge(
            self::$defaults,
            ['logger' => new NullLogger()],
            $config,
        );
    }

    public function get(string $resource, array $query = [])
    {
        $client = $this->getClient();
        $response = $client->get("{$resource}{$params}", [
            'query' => $query,
        ]);
        return $this->result($response);
    }

    public function post(string $resource, $body)
    {
        $client = $this->getClient();
        $response = $client->post("{$resource}", [
            'body' => json_encode($body),
        ]);
        return $this->result($response);
    }

    public function put(string $resource, $body)
    {
        $client = $this->getClient();
        $response = $client->put("{$resource}", [
            'body' => json_encode($body),
        ]);
        return $this->result($response);
    }

    public function delete(string $resource)
    {
        $client = $this->getClient();
        $response = $client->delete("{$resource}");
        return $this->result($response);
    }

    public function parse(ResponseInterface $response)
    {
        switch ($response->getStatusCode()) {
            case 200:
                return json_decode($response->getBody()->__toString());
            case 201:
            case 204:
                return true;
            case 404:
                return null;
        }
    }

    private function result(ResponseInterface $response)
    {
        return $this->config['parse_result'] ? $this->parse($response) : $response;
    }

    // Set up Guzzle client with middleware
    private function getClient(): ClientInterface
    {
        if ($this->client) {
            return $this->client;
        }

        $stack = HandlerStack::create();

        // Logging middleware
        $stack->unshift(
            Middleware::log(
                $this->config['logger'],
                new MessageFormatter('{method} {uri} {req_body}')
            )
        );
        $stack->unshift(
            Middleware::log(
                $this->config['logger'],
                new MessageFormatter('{code} {res_body}')
            )
        );

        // Authorization middleware
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            return $request->withHeader('Authorization', (string)$this->authorization);
        }));
        $stack->push(Middleware::mapResponse(function (ResponseInterface $response) {
            $this->authorization = $response->getHeaderLine('Authorization');
            return $response;
        }));

        $config = $this->config;
        $config['handler'] = $stack;
        $this->client = new Guzzle($config);
        return $this->client;
    }
}
