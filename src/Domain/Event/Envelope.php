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

namespace Streak\Domain\Event;

use Streak\Domain;
use Streak\Domain\Event;
use Streak\Domain\Id\UUID;

/**
 * @author Alan Gabriel Bem <alan.bem@gmail.com>
 */
final class Envelope implements Domain\Envelope
{
    public const METADATA_UUID = 'uuid';
    public const METADATA_NAME = 'name';
    public const METADATA_VERSION = 'version';
    public const METADATA_PRODUCER_TYPE = 'producer_type';
    public const METADATA_PRODUCER_ID = 'producer_id';

    /** @var Event */
    private $message;
    /** @var array<string, scalar> */
    private $metadata = [];

    public function __construct(UUID $uuid, string $name, Event $message, Domain\Id $producerId, ?int $version = null)
    {
        $this->metadata[self::METADATA_UUID] = $uuid->toString();
        $this->metadata[self::METADATA_NAME] = $name;
        $this->message = $message;
        $this->metadata[self::METADATA_PRODUCER_TYPE] = get_class($producerId);
        $this->metadata[self::METADATA_PRODUCER_ID] = $producerId->toString();
        if (null !== $version) {
            $this->metadata[self::METADATA_VERSION] = $version;
        }
    }

    public static function new(Event $event, Domain\Id $producerId, ?int $version = null) : self
    {
        return new self(UUID::random(), get_class($event), $event, $producerId, $version);
    }

    public function uuid() : UUID
    {
        /** @var string $uuid */
        $uuid = $this->get(self::METADATA_UUID);

        return new UUID($uuid);
    }

    public function name() : string
    {
        /** @var string $name */
        $name = $this->get(self::METADATA_NAME);

        return $name;
    }

    public function message() : Event
    {
        return $this->message;
    }

    public function producerId() : Domain\Id
    {
        /** @var string */
        $class = $this->get(self::METADATA_PRODUCER_TYPE);
        /** @var string */
        $id = $this->get(self::METADATA_PRODUCER_ID);

        return call_user_func([$class, 'fromString'], $id);
    }

    public function version() : ?int
    {
        /** @var int|null $version */
        $version = $this->get(self::METADATA_VERSION);

        return $version;
    }

    /**
     * @param string                       $name
     * @param string|float|integer|boolean $value
     *
     * @return Envelope
     */
    public function set(string $name, $value) : self
    {
        if (empty($name)) {
            throw new \InvalidArgumentException('Name of the attribute can not be empty.');
        }
        /**
         * @psalm-suppress DocblockTypeContradiction
         */
        if (!is_scalar($value)) {
            throw new \InvalidArgumentException(sprintf('Value for attribute "%s" is not scalar.', $name));
        }

        $new = new self(
            $this->uuid(),
            $this->name(),
            $this->message(),
            $this->producerId(),
            $this->version()
        );

        $new->metadata = $this->metadata;
        $new->metadata[$name] = $value;

        return $new;
    }

    /**
     * @param string $name
     *
     * @return scalar|null
     */
    public function get($name)
    {
        return $this->metadata[$name] ?? null;
    }

    public function metadata() : array
    {
        return $this->metadata;
    }

    /**
     * @param object $envelope
     */
    public function equals($envelope) : bool
    {
        if (!$envelope instanceof static) {
            return false;
        }

        if (!$this->uuid()->equals($envelope->uuid())) { // in a way envelope is an entity containing value object which event is.
            return false;
        }

        return true;
    }
}