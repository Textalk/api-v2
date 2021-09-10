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

    public function get(string $resource, array $query = []): ?object
    {
        $client = $this->getClient();
        $response = $client->get("{$resource}{$params}", [
            'query' => $query,
        ]);
        $content = $response->getBody()->__toString();
        return json_decode($content);
    }

    public function post(string $resource, object $body): string
    {
        $client = $this->getClient();
        $response = $client->post("{$resource}", [
            'body' => json_encode($body),
        ]);
        return $response->getHeaderLine('Location');
    }

    public function put(string $resource, object $body): string
    {
        $client = $this->getClient();
        $response = $client->put("{$resource}", [
            'body' => json_encode($body),
        ]);
        return $response->getHeaderLine('Location');
    }

    public function delete(string $resource): bool
    {
        $client = $this->getClient();
        $response = $client->delete("{$resource}");
        return true;
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
