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

namespace SlmMailTest\Mail\Message;

use PHPUnit\Framework\TestCase;
use SlmMail\Mail\Message\Exception\InvalidArgumentException;
use SlmMail\Mail\Message\Postmark;

class PostmarkTest extends TestCase
{
    public function testTagDefaultsToNull()
    {
        $message = new Postmark();

        $this->assertNull($message->getTag());
        $this->assertSame($message, $message->setTag('welcome'));
        $this->assertSame('welcome', $message->getTag());
    }

    public function testMetadataDefaultsToEmpty()
    {
        $this->assertSame([], (new Postmark())->getMetadata());
    }

    public function testMetadataIsStoredAsStrings()
    {
        $message = new Postmark();

        $this->assertSame($message, $message->setMetadata(['order_id' => 123, 'site' => 'cpop', 'rush' => true, 'ratio' => 1.5]));
        $this->assertSame(['order_id' => '123', 'site' => 'cpop', 'rush' => 'true', 'ratio' => '1.5'], $message->getMetadata());

        $message->addMetadata('site', 'pf');
        $message->addMetadata('note', new class {
            public function __toString(): string
            {
                return 'stringable';
            }
        });
        $this->assertSame(['order_id' => '123', 'site' => 'pf', 'rush' => 'true', 'ratio' => '1.5', 'note' => 'stringable'], $message->getMetadata());
    }

    public function testSetMetadataReplacesExistingFields()
    {
        $message = new Postmark();
        $message->setMetadata(['a' => '1', 'b' => '2']);
        $message->setMetadata(['c' => '3']);

        $this->assertSame(['c' => '3'], $message->getMetadata());
    }

    public function testEmptyKeyIsRejected()
    {
        $this->expectException(InvalidArgumentException::class);
        (new Postmark())->addMetadata('', 'x');
    }

    public function testLongKeyIsRejected()
    {
        $this->expectException(InvalidArgumentException::class);
        (new Postmark())->addMetadata(str_repeat('k', Postmark::METADATA_MAX_KEY_LENGTH + 1), 'x');
    }

    public function testLongValueIsRejected()
    {
        $message = new Postmark();
        $message->addMetadata('ok', str_repeat('v', Postmark::METADATA_MAX_VALUE_LENGTH));

        $this->expectException(InvalidArgumentException::class);
        $message->addMetadata('too_long', str_repeat('v', Postmark::METADATA_MAX_VALUE_LENGTH + 1));
    }

    public function testNonScalarValueIsRejected()
    {
        $this->expectException(InvalidArgumentException::class);
        (new Postmark())->addMetadata('list', ['not', 'allowed']);
    }

    public function testFieldCountIsCapped()
    {
        $message = new Postmark();
        for ($i = 1; $i <= Postmark::METADATA_MAX_FIELDS; $i++) {
            $message->addMetadata('field_' . $i, (string) $i);
        }

        // replacing an existing field is still fine at the cap
        $message->addMetadata('field_1', 'replaced');
        $this->assertSame('replaced', $message->getMetadata()['field_1']);

        $this->expectException(InvalidArgumentException::class);
        $message->addMetadata('one_too_many', 'x');
    }
}
