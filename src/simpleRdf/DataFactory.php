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

use Stringable;
use rdfInterface\DataFactoryInterface as iDataFactory;
use rdfInterface\TermInterface as iTerm;
use rdfInterface\BlankNodeInterface as iBlankNode;
use rdfInterface\NamedNodeInterface as iNamedNode;
use rdfInterface\LiteralInterface as iLiteral;
use rdfInterface\DefaultGraphInterface as iDefaultGraph;
use rdfInterface\QuadInterface as iQuad;
use rdfHelpers\DefaultGraph;
use rdfHelpers\QuadNoSubject;

/**
 * Description of DataFactory
 *
 * @author zozlak
 */
class DataFactory implements iDataFactory {

    public static function blankNode(string | Stringable | null $iri = null): iBlankNode {
        return new BlankNode($iri);
    }

    public static function namedNode(string | Stringable $iri): iNamedNode {
        return new NamedNode($iri);
    }

    public static function defaultGraph(): iDefaultGraph {
        return new DefaultGraph();
    }

    public static function literal(
        int | float | string | bool | Stringable $value,
        string | Stringable | null $lang = null,
        string | Stringable | null $datatype = null
    ): iLiteral {
        return new Literal($value, $lang, $datatype);
    }

    public static function quad(
        iTerm $subject, iNamedNode $predicate, iTerm $object,
        iNamedNode | iBlankNode | iDefaultGraph | null $graphIri = null
    ): iQuad {
        return new Quad(clone $subject, clone $predicate, clone $object, $graphIri);
    }

    public static function quadNoSubject(
        iNamedNode $predicate, iTerm $object,
        iNamedNode | iBlankNode | iDefaultGraph | null $graphIri = null
    ): QuadNoSubject {
        return new QuadNoSubject(clone $predicate, clone $object, $graphIri);
    }
}
