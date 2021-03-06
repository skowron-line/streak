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

namespace Streak\Infrastructure\Event\Subscription;

use Streak\Domain;
use Streak\Domain\Event;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionCompleted;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionRestarted;
use Streak\Domain\Event\Sourced\Subscription\Event\SubscriptionStarted;
use Streak\Domain\Event\Subscription;
use Streak\Domain\Event\Subscription\Repository\Filter;
use Streak\Domain\EventStore;
use Streak\Domain\Exception;
use Streak\Infrastructure;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class EventSourcedRepository implements Subscription\Repository
{
    /**
     * @var Subscription\Factory
     */
    private $subscriptions;

    /**
     * @var Event\Listener\Factory
     */
    private $listeners;

    /**
     * @var Domain\EventStore
     */
    private $store;

    /**
     * @var Infrastructure\UnitOfWork
     */
    private $uow;

    public function __construct(Subscription\Factory $subscriptions, Event\Listener\Factory $listeners, Domain\EventStore $store, Infrastructure\UnitOfWork $uow)
    {
        $this->subscriptions = $subscriptions;
        $this->listeners = $listeners;
        $this->store = $store;
        $this->uow = $uow;
    }

    public function find(Event\Listener\Id $id) : ?Event\Subscription
    {
        $listener = $this->listeners->create($id);
        $subscription = $this->subscriptions->create($listener);

        $unwrapped = $this->unwrap($subscription);

        $filter = new EventStore\Filter();
        $filter = $filter->filterProducerIds($unwrapped->producerId());

        $stream = $this->store->stream($filter);

        if ($stream->empty()) {
            return null;
        }

        $unwrapped->replay($stream);

        $this->uow->add($unwrapped);

        return $subscription;
    }

    /**
     * @throws Exception\ObjectNotSupported
     */
    public function has(Event\Subscription $subscription) : bool
    {
        $unwrapped = $this->unwrap($subscription);

        $filter = new EventStore\Filter();
        $filter = $filter->filterProducerIds($unwrapped->producerId());

        $stream = $this->store->stream($filter);

        if ($stream->empty()) {
            return false;
        }

        return true;
    }

    /**
     * @throws Exception\ObjectNotSupported
     */
    public function add(Event\Subscription $subscription) : void
    {
        $unwrapped = $this->unwrap($subscription);

        $this->uow->add($unwrapped);
    }

    /**
     * @return iterable|Event\Subscription[]
     */
    public function all(?Filter $filter = null) : iterable
    {
        if (null === $filter) {
            $filter = Filter::nothing();
        }

        $streamFilter = new EventStore\Filter();
        $streamFilter = $streamFilter->filterProducerTypes(...$filter->subscriptionTypes());

        $stream = $this->store->stream($streamFilter);
        $stream = $stream->only(SubscriptionStarted::class, SubscriptionRestarted::class, SubscriptionCompleted::class);

        $ids = [];
        foreach ($stream as $event) {
            if ($event->message() instanceof SubscriptionStarted) {
                $ids[] = $event->producerId();
            }

            if (true === $filter->areCompletedSubscriptionsIgnored()) {
                if ($event->message() instanceof SubscriptionCompleted) {
                    foreach ($ids as $key => $id) {
                        if ($event->producerId()->equals($id)) {
                            unset($ids[$key]);
                            break;
                        }
                    }
                }
            }

            if ($event->message() instanceof SubscriptionRestarted) {
                foreach ($ids as $key => $id) {
                    if ($event->producerId()->equals($id)) {
                        unset($ids[$key]);
                        break;
                    }
                }
                $ids[] = $event->producerId();
            }
        }

        foreach ($ids as $id) {
            yield new LazyLoadedSubscription($id, $this);
        }
    }

    /**
     * @param Subscription $subscription
     *
     * @return Event\Sourced|Subscription
     */
    private function unwrap(Event\Subscription $subscription) : Event\Sourced
    {
        $exception = new Exception\ObjectNotSupported($subscription);

        if ($subscription instanceof Event\Sourced) {
            return $subscription;
        }

        while ($subscription instanceof Subscription\Decorator) {
            $subscription = $subscription->subscription();

            if ($subscription instanceof Event\Sourced) {
                return $subscription;
            }
        }

        throw $exception;
    }
}
