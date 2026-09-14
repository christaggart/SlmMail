<?php

/**
 * Copyright (c) 2012-2013 Jurian Sluiman.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the names of the copyright holders nor the names of the
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @author      Jurian Sluiman <jurian@juriansluiman.nl>
 * @copyright   2012-2013 Jurian Sluiman.
 * @license     http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link        http://juriansluiman.nl
 */

namespace SlmMail\Mail\Message;

use Laminas\Mail\Message;
use SlmMail\Mail\Message\Exception\InvalidArgumentException;

class Postmark extends Message
{
    /**
     * Postmark accepts at most this many metadata fields per message
     */
    public const METADATA_MAX_FIELDS = 10;

    /**
     * Longest metadata field name Postmark accepts
     */
    public const METADATA_MAX_KEY_LENGTH = 20;

    /**
     * Longest metadata value Postmark accepts
     */
    public const METADATA_MAX_VALUE_LENGTH = 80;

    /**
     * @var string
     */
    protected $tag;

    /**
     * Custom metadata sent with the message and returned by the Messages API
     *
     * @var array<string, string>
     */
    protected $metadata = [];

    /**
     * Get tag for the message
     *
     * @return string|null
     */
    public function getTag(): ?string
    {
        return $this->tag;
    }

    /**
     * Set tag for the message
     *
     * @param  string $tag Value to set
     * @return self
     */
    public function setTag(?string $tag): Postmark
    {
        $this->tag = $tag;
        return $this;
    }

    /**
     * Get the metadata for the message
     *
     * @return array<string, string>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Replace the metadata for the message
     *
     * @param  array<string, scalar> $metadata field name => value
     * @throws InvalidArgumentException
     * @return self
     */
    public function setMetadata(array $metadata): Postmark
    {
        $this->metadata = [];

        foreach ($metadata as $key => $value) {
            $this->addMetadata((string) $key, $value);
        }

        return $this;
    }

    /**
     * Add (or replace) a single metadata field
     *
     * Postmark only accepts string values, so scalars are cast; anything else
     * is rejected. Limits are enforced here so the API does not reject the
     * whole message later.
     *
     * @param  string $key   field name, at most METADATA_MAX_KEY_LENGTH characters
     * @param  mixed  $value scalar (or object with __toString), at most METADATA_MAX_VALUE_LENGTH characters
     * @throws InvalidArgumentException
     * @return self
     */
    public function addMetadata(string $key, $value): Postmark
    {
        if ($key === '' || strlen($key) > self::METADATA_MAX_KEY_LENGTH) {
            throw new InvalidArgumentException(sprintf(
                'Postmark metadata field names must be between 1 and %d characters, "%s" given',
                self::METADATA_MAX_KEY_LENGTH,
                $key
            ));
        }

        if (!is_scalar($value) && !(is_object($value) && method_exists($value, '__toString'))) {
            throw new InvalidArgumentException(sprintf(
                'Postmark metadata values must be scalar, %s given for "%s"',
                is_object($value) ? get_class($value) : gettype($value),
                $key
            ));
        }

        $value = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;

        if (strlen($value) > self::METADATA_MAX_VALUE_LENGTH) {
            throw new InvalidArgumentException(sprintf(
                'Postmark metadata values are limited to %d characters, %d given for "%s"',
                self::METADATA_MAX_VALUE_LENGTH,
                strlen($value),
                $key
            ));
        }

        if (!array_key_exists($key, $this->metadata) && count($this->metadata) >= self::METADATA_MAX_FIELDS) {
            throw new InvalidArgumentException(sprintf(
                'Postmark messages are limited to %d metadata fields',
                self::METADATA_MAX_FIELDS
            ));
        }

        $this->metadata[$key] = $value;

        return $this;
    }
}
