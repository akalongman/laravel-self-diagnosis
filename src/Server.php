<?php

namespace BeyondCode\SelfDiagnosis;

use function parse_url;

/**
 * DTO class for a server used by the ping check.
 *
 * @package BeyondCode\SelfDiagnosis
 */
class Server
{
    protected string $host;
    protected ?int $port;
    protected int $timeout;

    public function __construct(string $host, ?int $port, int $timeout)
    {
        $parsed = parse_url($host);

        $this->host = $parsed['host'] ?? $parsed['path'];
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
