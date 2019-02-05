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

namespace Streak\Infrastructure\AggregateRoot\Snapshotter;

use Streak\Domain\AggregateRoot;
use Streak\Infrastructure\AggregateRoot\Snapshotter;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
class NullSnapshotter implements Snapshotter
{
    public function restoreToSnapshot(AggregateRoot $aggregate) : AggregateRoot
    {
        return $aggregate;
    }

    public function takeSnapshot(AggregateRoot $aggregate) : AggregateRoot
    {
        return $aggregate;
    }
}