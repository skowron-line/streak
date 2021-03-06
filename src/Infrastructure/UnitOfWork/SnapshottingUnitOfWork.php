<?php

/**
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Streak\Infrastructure\UnitOfWork;

use InvalidArgumentException;
use Streak\Domain\Event;
use Streak\Infrastructure\AggregateRoot\Snapshotter;
use Streak\Infrastructure\UnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class SnapshottingUnitOfWork implements UnitOfWork
{
    private $uow;
    private $snapshotter;
    private $versions;
    private $committing = false;

    /** @var int */
    private $interval = 1;

    public function __construct(UnitOfWork $uow, Snapshotter $snapshotter, int $interval = 1)
    {
        if ($interval < 1) {
            throw new InvalidArgumentException('Interval must be positive!');
        }

        $this->uow = $uow;
        $this->snapshotter = $snapshotter;
        $this->versions = new \SplObjectStorage();
        $this->interval = $interval;
    }

    public function add($producer) : void
    {
        $this->uow->add($producer);

        if ($producer instanceof Event\Sourced\AggregateRoot) {
            $id = $producer->producerId();
            $version = $producer->version();
            $this->versions->attach($id, $version);
        }
    }

    public function remove($producer) : void
    {
        if ($producer instanceof Event\Sourced\AggregateRoot) {
            $id = $producer->producerId();
            $this->versions->offsetUnset($id);
        }

        $this->uow->remove($producer);
    }

    public function has($producer) : bool
    {
        return $this->uow->has($producer);
    }

    /**
     * @return Event\Producer[]
     */
    public function uncommitted() : array
    {
        $uncommitted = $this->uow->uncommitted();

        return $uncommitted;
    }

    public function count() : int
    {
        return $this->uow->count();
    }

    public function commit() : \Generator
    {
        if (false === $this->committing) {
            $this->committing = true;

            try {
                foreach ($this->uow->commit() as $committed) {
                    if (!$committed instanceof Event\Sourced\AggregateRoot) {
                        yield $committed;
                        continue;
                    }

                    $versionBeforeCommit = $this->versions->offsetGet($committed->producerId());
                    $versionAfterCommit = $committed->version();

                    $this->versions->offsetUnset($committed->producerId());

                    if (!$this->isReadyForSnapshot($versionBeforeCommit, $versionAfterCommit)) {
                        yield $committed;
                        continue;
                    }

                    $this->snapshotter->takeSnapshot($committed);

                    yield $committed;
                }

                $this->clear();
            } finally {
                $this->committing = false;
            }
        }
    }

    public function clear() : void
    {
        $this->versions = new \SplObjectStorage(); // clear
        $this->uow->clear();
    }

    private function isReadyForSnapshot(int $before, int $after) : bool
    {
        if ((int) ($before / $this->interval) !== (int) ($after / $this->interval)) {
            return true;
        }

        return false;
    }
}
