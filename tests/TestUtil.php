<?php

/**
 * This file is part of prooph/memcached-snapshot-store.
 * (c) 2017-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2017-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\SnapshotStore\Memcached;

use Memcached;

abstract class TestUtil
{
    public static function getConnection(): Memcached
    {
        $connectionParams = self::getConnectionParams();

        $connection = new Memcached();
        $connection->addServer($connectionParams['host'], (int) $connectionParams['port']);

        return $connection;
    }

    public static function getConnectionParams(): array
    {
        if (! self::hasRequiredConnectionParams()) {
            throw new \RuntimeException('No connection params given');
        }

        return self::getSpecifiedConnectionParams();
    }

    private static function hasRequiredConnectionParams(): bool
    {
        return isset(
            $GLOBALS['memcached_host'],
            $GLOBALS['memcached_port']
        );
    }

    private static function getSpecifiedConnectionParams(): array
    {
        return [
            'host' => $GLOBALS['memcached_host'],
            'port' => $GLOBALS['memcached_port'],
        ];
    }
}
