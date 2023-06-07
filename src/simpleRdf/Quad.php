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

use BadMethodCallException;
use rdfInterface\NamedNodeInterface;
use rdfInterface\BlankNodeInterface;
use rdfInterface\DefaultGraphInterface;
use rdfInterface\LiteralInterface;
use rdfInterface\TermInterface;
use rdfInterface\TermCompareInterface;
use rdfInterface\QuadInterface;
use simpleRdf\DataFactory as DF;

/**
 * Description of Triple
 *
 * @author zozlak
 */
class Quad implements QuadInterface {

    private TermInterface $subject;
    private NamedNodeInterface $predicate;
    private TermInterface $object;
    private NamedNodeInterface | BlankNodeInterface | DefaultGraphInterface $graph;

    public function __construct(
        TermInterface $subject, NamedNodeInterface $predicate, TermInterface $object,
        NamedNodeInterface | BlankNodeInterface | DefaultGraphInterface | null $graph = null
    ) {
        if ($subject instanceof LiteralInterface) {
            throw new BadMethodCallException("subject can't be a literal");
        }
        $this->subject   = $subject;
        $this->predicate = $predicate;
        $this->object    = $object;
        $this->graph  = $graph ?? DF::defaultGraph();
    }

    public function __toString(): string {
        $sbj   = (string) $this->subject;
        $pred  = (string) $this->predicate;
        $obj   = (string) $this->object;
        $graph = $this->graph instanceof DefaultGraphInterface ? '' : (string) $this->graph;
        if ($this->subject instanceof QuadInterface) {
            $sbj = "<< $sbj >>";
        }
        if ($this->object instanceof QuadInterface) {
            $obj = "<< $obj >>";
        }
        return rtrim("$sbj $pred $obj $graph");
    }

    public function equals(TermCompareInterface $term): bool {
        if ($term instanceof QuadInterface) {
            /* @var $term QuadInterface */
            return $this->subject->equals($term->getSubject()) &&
                $this->predicate->equals($term->getPredicate()) &&
                $this->object->equals($term->getObject()) &&
                $this->graph->equals($term->getGraph());
        } else {
            return false;
        }
    }

    public function getValue(): string {
        throw new BadMethodCallException();
    }

    public function getSubject(): TermInterface {
        return $this->subject;
    }

    public function getPredicate(): NamedNodeInterface {
        return $this->predicate;
    }

    public function getObject(): TermInterface {
        return $this->object;
    }

    public function getGraph(): NamedNodeInterface | BlankNodeInterface | DefaultGraphInterface {
        return $this->graph;
    }

    public function withSubject(TermInterface $subject): QuadInterface {
        return DF::quad($subject, $this->predicate, $this->object, $this->graph);
    }

    public function withPredicate(NamedNodeInterface $predicate): QuadInterface {
        return DF::quad($this->subject, $predicate, $this->object, $this->graph);
    }

    public function withObject(TermInterface $object): QuadInterface {
        return DF::quad($this->subject, $this->predicate, $object, $this->graph);
    }

    public function withGraph(NamedNodeInterface | BlankNodeInterface | DefaultGraphInterface | null $graph): QuadInterface {
        return DF::quad($this->subject, $this->predicate, $this->object, $graph);
    }
}
