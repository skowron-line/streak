<?php

/*
 * This file is part of the streak package.
 *
 * (C) Alan Gabriel Bem <alan.bem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Streak\Domain\Message;

use Streak\Domain;
use Streak\Domain\Message;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
trait Listening // implements Message\Listener
{
    public function onMessage(Domain\Message $message) : void
    {
        $reflection = new \ReflectionObject($this);

        $methods = [];
        foreach ($reflection->getMethods() as $method) {
            // method is not current method...
            if ($method->getName() === __FUNCTION__) {
                continue;
            }

            // ...and its name must start with "on"
            if (\mb_substr($method->getName(), 0, 2) !== 'on') {
                continue;
            }

            // ...and have exactly one parameter...
            if ($method->getNumberOfParameters() !== 1) {
                continue;
            }

            // ...which is required...
            if ($method->getNumberOfRequiredParameters() !== 1) {
                continue;
            }

            $parameter = $method->getParameters()[0];
            $parameter = $parameter->getClass();

            // ..and its a message...
            if (false === $parameter->isSubclassOf(Domain\Message::class)) {
                continue;
            }

            $target = new \ReflectionClass($message);

            // .. and $message is type or subtype of defined $parameter
            while($parameter->getName() !== $target->getName()) {
                $target = $target->getParentClass();

                if (false === $target) {
                    continue 2;
                }
            }

            $methods[] = $method;
        }

        // TODO: filter methods matching given event exactly and if it wont work, than filter by direct ascendants of given event and so on

        if (\count($methods) === 0) {
            return;
        }

        $method = array_shift($methods);

        $isPublic = $method->isPublic();

        if (false === $isPublic) {
            $method->setAccessible(true);
        }

        $method->invoke($this, $message);

        if (false === $isPublic) {
            $method->setAccessible(false);
        }
    }
}
