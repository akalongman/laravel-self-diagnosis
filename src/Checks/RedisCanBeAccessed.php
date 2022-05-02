<?php

declare(strict_types=1);

namespace BeyondCode\SelfDiagnosis\Checks;

use BeyondCode\SelfDiagnosis\Checks\Check;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Redis\Connections\PhpRedisClusterConnection;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

use function is_array;
use function is_string;
use function parse_url;

class RedisCanBeAccessed implements Check
{
    private ?string $message;

    /**
     * The name of the check.
     *
     * @param array $config
     * @return string
     */
    public function name(array $config): string
    {
        return trans('self-diagnosis::checks.redis_can_be_accessed.name');
    }

    /**
     * Perform the actual verification of this check.
     *
     * @param array $config
     * @return bool
     * @throws \BeyondCode\SelfDiagnosis\Exceptions\InvalidConfigurationException
     */
    public function check(array $config): bool
    {
        $this->notReachableServers = $this->parseConfiguredServers(Arr::get($config, 'servers', []));
        if ($this->notReachableServers->isEmpty()) {
            return true;
        }

        $this->notReachableServers = $this->notReachableServers->reject(static function (Server $server) {
            $ping = new Ping($server->getHost());
            $ping->setPort($server->getPort());
            $ping->setTimeout($server->getTimeout());

            if ($ping->getPort() === null) {
                $latency = $ping->ping('exec');
            } else {
                $latency = $ping->ping('fsockopen');
            }

            return $latency !== false;
        });

        return $this->notReachableServers->isEmpty();
    }

    /**
     * The error message to display in case the check does not pass.
     *
     * @param array $config
     * @return string
     */
    public function message(array $config): string
    {
        return trans('self-diagnosis::checks.redis_can_be_accessed.message.not_accessible', [
            'error' => $this->message,
        ]);
    }

    private function parseConfiguredServers(array $servers): Collection
    {
        $result = new Collection();

        foreach ($servers as $server) {
            if (is_array($server)) {
                if (! empty(Arr::except($server, ['host', 'port', 'timeout']))) {
                    throw new InvalidConfigurationException('Servers in array notation may only contain a host, port and timeout parameter.');
                }
                if (! Arr::has($server, 'host')) {
                    throw new InvalidConfigurationException('For servers in array notation, the host parameter is required.');
                }

                $host = Arr::get($server, 'host');
                $port = Arr::get($server, 'port');
                $timeout = Arr::get($server, 'timeout', self::DEFAULT_TIMEOUT);

                $parsed = parse_url($host);
                $host = $parsed['host'] ?? $parsed['path'];
                if (empty($port)) {
                    $port = $parsed['port'] ?? null;
                }
                $result->push(new Server($host, $port, $timeout));
            } elseif (is_string($server)) {
                $result->push(new Server($server, null, self::DEFAULT_TIMEOUT));
            } else {
                throw new InvalidConfigurationException('The server configuration may only contain arrays or strings.');
            }
        }

        return $result;
    }

    /**
     * Tests a redis connection and returns whether the connection is opened or not.
     *
     * @param string|null $name
     * @return bool
     */
    private function testConnection(?string $name = null): bool
    {
        $redis = app(RedisFactory::class);
        $connection = $redis->connection($name);

        // PHPRedis connects automatically
        if ($connection instanceof PhpRedisConnection || $connection instanceof PhpRedisClusterConnection) {
            return ! empty($connection->info());
        }

        $connection->connect();

        return $connection->isConnected();
    }
}
