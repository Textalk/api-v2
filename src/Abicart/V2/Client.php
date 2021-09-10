<?php

namespace Abicart\V2;

use Psr\Log\{LoggerAwareInterface, LoggerInterface, NullLogger};

/**
 * Abicart API v2 client
 */
class Client implements LoggerAwareInterface
{
    private static $defaults = [
        'merchant'  => null,
        'token'     => null,
        'logger'    => null,
        'base_uri'  => 'https://api.abicart.com/v2/',
        'redirect'  => false,
    ];

    private $options;

    /**
     * Create client.
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge(
            self::$defaults,
            ['logger' => new NullLogger()],
            $options,
        );
    }

    /**
     * Set logger.
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $options['logger'] = $logger;
    }
}
