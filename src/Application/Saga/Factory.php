<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Application\Saga;

use Streak\Application;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
interface Factory
{
    public function create() : Application\Saga;
}
