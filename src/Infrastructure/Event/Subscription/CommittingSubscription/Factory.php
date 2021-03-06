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

namespace Streak\Infrastructure\Event\Subscription\CommittingSubscription;

use Streak\Domain\Event;
use Streak\Domain\Event\Subscription;
use Streak\Infrastructure\Event\Subscription\CommittingSubscription;
use Streak\Infrastructure\UnitOfWork;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class Factory implements Subscription\Factory
{
    private $factory;
    private $uow;

    public function __construct(Subscription\Factory $factory, UnitOfWork $uow)
    {
        $this->factory = $factory;
        $this->uow = $uow;
    }

    public function create(Event\Listener $listener) : Event\Subscription
    {
        $subscription = $this->factory->create($listener);
        $subscription = new CommittingSubscription($subscription, $this->uow);

        return $subscription;
    }
}
