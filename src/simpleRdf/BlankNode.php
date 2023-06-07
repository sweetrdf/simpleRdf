<?php

/*
 * The MIT License
 *
 * Copyright 2021 zozlak.
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

namespace simpleRdf;

use rdfInterface\BlankNodeInterface;
use rdfInterface\TermCompareInterface;

/**
 * Description of BlankNode
 *
 * @author zozlak
 */
class BlankNode implements BlankNodeInterface {

    private static int $n = 0;
    private string $id;

    public function __construct(?string $id = null) {
        if (empty($id)) {
            $id = "_:genid" . self::$n;
            self::$n++;
        }
        if (!str_starts_with($id, '_:')) {
            $id = '_:' . $id;
        }
        $this->id = $id;
    }

    public function __toString(): string {
        return $this->id;
    }

    public function equals(TermCompareInterface $term): bool {
        return $term instanceof BlankNodeInterface && $this->getValue() == $term->getValue();
    }

    public function getValue(): string {
        return $this->id;
    }
}
