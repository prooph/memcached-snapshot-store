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
use PHPUnit\Framework\TestCase;
use Prooph\SnapshotStore\Memcached\MemcachedSnapshotStore;
use Prooph\SnapshotStore\Snapshot;

class MemcachedSnapshotStoreTest extends TestCase
{
    /**
     * @var MemcachedSnapshotStore
     */
    private $snapshotStore;

    /**
     * @var Memcached
     */
    private $connection;

    /**
     * @test
     */
    public function it_saves_and_reads()
    {
        $aggregateType = 'baz';
        $aggregateRoot = new \stdClass();
        $aggregateRoot->foo = 'bar';

        $time = (string) \microtime(true);
        if (false === \strpos($time, '.')) {
            $time .= '.0000';
        }

        $now = \DateTimeImmutable::createFromFormat('U.u', $time);

        $snapshot = new Snapshot($aggregateType, 'id', $aggregateRoot, 1, $now);

        $this->snapshotStore->save($snapshot);

        $snapshot = new Snapshot($aggregateType, 'id', $aggregateRoot, 2, $now);

        $this->snapshotStore->save($snapshot);

        $this->assertNull($this->snapshotStore->get($aggregateType, 'invalid'));

        $readSnapshot = $this->snapshotStore->get($aggregateType, 'id');

        $this->assertEquals($snapshot, $readSnapshot);

        $keys = $this->connection->getAllKeys();

        $this->assertCount(1, $keys);
    }

    /**
     * @test
     */
    public function it_saves_multiple_snapshots_and_removes_them()
    {
        $aggregateRoot1 = new \stdClass();
        $aggregateRoot1->foo = 'bar';

        $aggregateRoot2 = ['foo' => 'baz'];

        $time = (string) \microtime(true);
        if (false === \strpos($time, '.')) {
            $time .= '.0000';
        }

        $now = \DateTimeImmutable::createFromFormat('U.u', $time);

        $snapshot1 = new Snapshot('object', 'id_one', $aggregateRoot1, 1, $now);

        $snapshot2 = new Snapshot('array', 'id_two', $aggregateRoot2, 2, $now);

        $snapshot3 = new Snapshot('array', 'id_three', $aggregateRoot2, 1, $now);

        $this->snapshotStore->save($snapshot1, $snapshot2, $snapshot3);

        $this->assertEquals($snapshot1, $this->snapshotStore->get('object', 'id_one'));
        $this->assertEquals($snapshot2, $this->snapshotStore->get('array', 'id_two'));
        $this->assertEquals($snapshot3, $this->snapshotStore->get('array', 'id_three'));

        $this->snapshotStore->removeAll('array');

        $keys = $this->connection->getAllKeys();

        $this->assertCount(1, $keys);
    }

    /**
     * @test
     */
    public function it_returns_early_when_no_snapshots_given()
    {
        $connection = $this->prophesize(Memcached::class);

        $snapshotStore = new MemcachedSnapshotStore($connection->reveal());

        $snapshotStore->save();
    }

    protected function setUp(): void
    {
        $this->connection = TestUtil::getConnection();

        $this->snapshotStore = new MemcachedSnapshotStore($this->connection);
    }

    protected function tearDown(): void
    {
        $this->connection->flush();
    }
}
