<?php

/**
 * This file is part of the prooph/memcached-snapshot-store.
 * (c) 2017-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2017-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\SnapshotStore\Memcached\Container;

use Memcached;
use PHPUnit\Framework\TestCase;
use Prooph\SnapshotStore\CallbackSerializer;
use Prooph\SnapshotStore\Memcached\Container\MemcachedSnapshotStoreFactory;
use Prooph\SnapshotStore\Memcached\MemcachedSnapshotStore;
use ProophTest\SnapshotStore\Memcached\TestUtil;
use Psr\Container\ContainerInterface;

class MemcachedSnapshotStoreFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_adapter_via_connection_service(): void
    {
        $config['prooph']['memcached_snapshot_store']['default'] = [
            'connection' => 'my_connection',
        ];

        $connection = TestUtil::getConnection();

        $container = $this->prophesize(ContainerInterface::class);

        $container->get('my_connection')->willReturn($connection)->shouldBeCalled();
        $container->get('config')->willReturn($config)->shouldBeCalled();

        $factory = new MemcachedSnapshotStoreFactory();
        $snapshotStore = $factory($container->reveal());

        $this->assertInstanceOf(MemcachedSnapshotStore::class, $snapshotStore);
    }

    /**
     * @test
     */
    public function it_still_works_with_deprecated_connection_service_key(): void
    {
        $config['prooph']['memcached_snapshot_store']['default'] = [
            'connection_service' => 'my_connection',
        ];

        $connection = TestUtil::getConnection();

        $container = $this->prophesize(ContainerInterface::class);

        $container->get('my_connection')->willReturn($connection)->shouldBeCalled();
        $container->get('config')->willReturn($config)->shouldBeCalled();

        $factory = new MemcachedSnapshotStoreFactory();
        $snapshotStore = $factory($container->reveal());

        $this->assertInstanceOf(MemcachedSnapshotStore::class, $snapshotStore);
    }

    /**
     * @test
     */
    public function it_still_works_with_deprecated_connection_service_key_for_config_objects(): void
    {
        $config['prooph']['memcached_snapshot_store']['default'] = [
            'connection_service' => 'my_connection',
        ];

        $connection = TestUtil::getConnection();

        $container = $this->prophesize(ContainerInterface::class);

        $container->get('my_connection')->willReturn($connection)->shouldBeCalled();
        $container->get('config')->willReturn(new \ArrayObject($config))->shouldBeCalled();

        $factory = new MemcachedSnapshotStoreFactory();
        $snapshotStore = $factory($container->reveal());

        $this->assertInstanceOf(MemcachedSnapshotStore::class, $snapshotStore);
        $this->assertArrayHasKey('connection_service', $container->reveal()->get('config')['prooph']['memcached_snapshot_store']['default']);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_invalid_container_given(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $eventStoreName = 'custom';
        MemcachedSnapshotStoreFactory::$eventStoreName('invalid container');
    }

    /**
     * @test
     */
    public function it_gets_serializer_from_container_when_not_instanceof_serializer(): void
    {
        $config['prooph']['memcached_snapshot_store']['default'] = [
            'connection' => 'my_connection',
            'serializer' => 'serializer_servicename',
        ];

        $connection = $this->prophesize(Memcached::class);

        $container = $this->prophesize(ContainerInterface::class);

        $container->get('my_connection')->willReturn($connection)->shouldBeCalled();
        $container->get('config')->willReturn($config)->shouldBeCalled();
        $container->get('serializer_servicename')->willReturn(new CallbackSerializer(function () {
        }, function () {
        }))->shouldBeCalled();

        $factory = new MemcachedSnapshotStoreFactory();
        $snapshotStore = $factory($container->reveal());

        $this->assertInstanceOf(MemcachedSnapshotStore::class, $snapshotStore);
    }
}
