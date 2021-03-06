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

use rdfInterface\Term;
use rdfInterface\TermCompare;

/**
 * Description of TestTrait
 *
 * @author zozlak
 */
trait TestTrait {

    public static function getDataFactory(): \rdfInterface\DataFactory {
        return new DataFactory();
    }

    public static function getForeignDataFactory(): \rdfInterface\DataFactory {
        return new DataFactory();
    }

    public static function getDataset(): \rdfInterface\Dataset {
        return new Dataset();
    }

    public static function getForeignDataset(): \rdfInterface\Dataset {
        return new Dataset();
    }

    public static function getQuadTemplate(TermCompare | Term | null $subject = null,
                                           TermCompare | Term | null $predicate = null,
                                           TermCompare | Term | null $object = null,
                                           TermCompare | Term | null $graphIri = null): \rdfInterface\QuadCompare {
        return new \termTemplates\QuadTemplate($subject, $predicate, $object, $graphIri);
    }

    public static function getRdfNamespace(): \rdfInterface\RdfNamespace {
        return new RdfNamespace();
    }
}
