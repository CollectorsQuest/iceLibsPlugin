<?php
/**
 * Copyright (C) 2011 by Lars Strojny <lstrojny@php.net>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
namespace Functional;

/**
 * Partitions a collection by callback result. The thruthy partition is the first one
 * (array key "0"), the falsy the second one (array key "1")
 *
 * @param Traversable|array $collection
 * @param callable $callback
 * @return array
 */
function partition($collection, $callback)
{
    Exceptions\InvalidArgumentException::assertCollection($collection, __FUNCTION__, 1);
    Exceptions\InvalidArgumentException::assertCallback($callback, __FUNCTION__, 2);

    $partitions = array(
        0 => array(),
        1 => array()
    );

    foreach ($collection as $key => $element) {
        $partitionKey = call_user_func($callback, $element, $key, $collection) ? 0 : 1;
        $partitions[$partitionKey][$key] = $element;
    }

    return $partitions;
}
