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

namespace Streak\Infrastructure;

use Generator;
use Streak\Infrastructure\UnitOfWork\Exception;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface UnitOfWork
{
    /**
     * @throws Exception\ObjectNotSupported
     */
    public function add($object) : void;

    public function remove($object) : void;

    public function has($object) : bool;

    /**
     * @return object[]
     */
    public function uncommitted() : array;

    public function count() : int;

    /**
     * @return Generator|object[]
     */
    public function commit() : \Generator;

    public function clear() : void;
}
