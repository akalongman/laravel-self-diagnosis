<?php

declare(strict_types=1);

namespace BeyondCode\SelfDiagnosis\Checks;

use BeyondCode\SelfDiagnosis\Exceptions\InvalidConfigurationException;
use BeyondCode\SelfDiagnosis\Server;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use JJG\Ping;

use function is_array;
use function is_string;

use const PHP_EOL;

class ServersArePingable implements Check
{
    protected const DEFAULT_TIMEOUT = 5;

    /** @var \Illuminate\Support\Collection */
    protected $notReachableServers;

    /**
     * The name of the check.
     *
     * @param array $config
     * @return string
     */
    public function name(array $config): string
    {
        return trans('self-diagnosis::checks.servers_are_pingable.name');
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
        return $this->notReachableServers->map(static function (Server $server) {
            return trans('self-diagnosis::checks.servers_are_pingable.message', [
                'host'    => $server->getHost(),
                'port'    => $server->getPort() ?? 'n/a',
                'timeout' => $server->getTimeout(),
            ]);
        })->implode(PHP_EOL);
    }

    /**
     * Parses an array of servers which can be given in different formats.
     * Unifies the format for the resulting collection.
     *
     * @param array $servers
     * @return \Illuminate\Support\Collection
     * @throws \BeyondCode\SelfDiagnosis\Exceptions\InvalidConfigurationException
     */
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

                $result->push(new Server($host, $port, $timeout));
            } elseif (is_string($server)) {
                $result->push(new Server($server, null, self::DEFAULT_TIMEOUT));
            } else {
                throw new InvalidConfigurationException('The server configuration may only contain arrays or strings.');
            }
        }

        return $result;
    }
}
