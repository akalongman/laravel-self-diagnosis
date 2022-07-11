<?php

namespace BeyondCode\SelfDiagnosis;

use function str_replace;

/**
 * DTO class for a server used by the ping check.
 *
 * @package BeyondCode\SelfDiagnosis
 */
class Server
{
    /** @var string */
    protected $host;

    /** @var int|null */
    protected $port;

    /** @var int */
    protected $timeout;

    public function __construct(string $host, ?int $port, int $timeout)
    {
        $this->host = str_replace(['http://', 'https://'], '', $host);
        $this->port = $port;
        $this->timeout = $timeout;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }
}
