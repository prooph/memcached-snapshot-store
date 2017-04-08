<?php
/**
 * This file is part of the prooph/memcached-snapshot-store.
 * (c) 2017-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2017-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\SnapshotStore\Memcached;

use DateTimeImmutable;
use DateTimeZone;
use Memcached;
use Prooph\SnapshotStore\CallbackSerializer;
use Prooph\SnapshotStore\Serializer;
use Prooph\SnapshotStore\Snapshot;
use Prooph\SnapshotStore\SnapshotStore;

final class MemcachedSnapshotStore implements SnapshotStore
{
    /**
     * @var Memcached
     */
    private $connection;

    /**
     * @var Serializer
     */
    private $serializer;

    public function __construct(
        Memcached $connection,
        Serializer $serializer = null
    ) {
        $this->connection = $connection;
        $this->serializer = $serializer ?: new CallbackSerializer(null, null);
    }

    public function get(string $aggregateType, string $aggregateId): ?Snapshot
    {
        $result = $this->connection->get($aggregateType . '-' . $aggregateId);

        if (false === $result) {
            return null;
        }

        return new Snapshot(
            $result[1],
            $result[0],
            $this->serializer->unserialize($result[4]),
            $result[2],
            DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.u', $result[3], new DateTimeZone('UTC'))
        );
    }

    public function save(Snapshot ...$snapshots): void
    {
        if (empty($snapshots)) {
            return;
        }

        $data = [];

        foreach ($snapshots as $snapshot) {
            $data[$snapshot->aggregateType() . '-' . $snapshot->aggregateId()] = [
                $snapshot->aggregateId(),
                $snapshot->aggregateType(),
                $snapshot->lastVersion(),
                $snapshot->createdAt()->format('Y-m-d\TH:i:s.u'),
                $this->serializer->serialize($snapshot->aggregateRoot())
            ];
        }

        $this->connection->setMulti($data);
    }

    public function removeAll(string $aggregateType): void
    {
        $keys = $this->connection->getAllKeys();

        foreach($keys as $item) {
            if (substr($item, 0, strlen($aggregateType)) === $aggregateType) {
                $this->connection->delete($item);
            }
        }
    }
}
