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
use zozlak\RdfConstants as RDF;
use rdfInterface\Term as iTerm;
use rdfInterface\BlankNode as iBlankNode;
use rdfInterface\NamedNode as iNamedNode;
use rdfInterface\Literal as iLiteral;
use rdfInterface\DefaultGraph as iDefaultGraph;
use rdfInterface\Quad as iQuad;
use rdfInterface\QuadTemplate as iQuadTemplate;

/**
 * Description of DataFactory
 *
 * @author zozlak
 */
class DataFactory implements \rdfInterface\DataFactory {

    public static function blankNode(string | Stringable | null $iri = null): iBlankNode {
        return new BlankNode($iri);
    }

    public static function namedNode(string | Stringable $iri): iNamedNode {
        return new NamedNode($iri);
    }

    public static function defaultGraph(string | Stringable | null $iri = null): iDefaultGraph {
        return new DefaultGraph($iri);
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
        iNamedNode | iBlankNode | null $graphIri = null
    ): iQuad {
        return new Quad($subject, $predicate, $object, $graphIri);
    }

    public static function quadTemplate(
        iTerm | null $subject = null, iNamedNode | null $predicate = null,
        iTerm | null $object = null,
        iNamedNode | iBlankNode | null $graphIri = null
    ): iQuadTemplate {
        return new QuadTemplate($subject, $predicate, $object, $graphIri);
    }

    public static function variable(string | Stringable $name): \rdfInterface\Variable {
        throw new RdfException('Variables are not implemented');
    }
}
