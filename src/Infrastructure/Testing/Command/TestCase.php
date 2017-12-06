<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Infrastructure\Testing\Command;

use PHPUnit;
use Streak\Application;
use Streak\Domain;
use Streak\Infrastructure\EventStore\InMemoryEventStore;
use Streak\Infrastructure\Repository\EventSourcedRepository;
use Streak\Infrastructure\UnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * @codeCoverageIgnore
 */
abstract class TestCase extends PHPUnit\Framework\TestCase
{
    private $store;
    private $uow;
    private $repository;

    public function setUp()
    {
        $this->store = new InMemoryEventStore();
        $this->uow = new UnitOfWork($this->store);
        $this->repository = new EventSourcedRepository($this->createFactory(), $this->store, $this->uow);
    }

    private function createScenario() : Specification
    {
        return new Specification($this->createHandler($this->store), $this->store, $this->uow);
    }

    public function getRepository() : Domain\Repository
    {
        return $this->repository;
    }

    public function given(Domain\Event ...$events) : Scenario\When
    {
        return $this->createScenario()->given(...$events);
    }

    abstract protected function createFactory() : Domain\AggregateRoot\Factory;

    abstract protected function createHandler(Domain\EventStore $store) : Application\CommandHandler;
}