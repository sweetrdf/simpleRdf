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

use Generator;
use Iterator;
use OutOfBoundsException;
use rdfHelpers\GenericTermIterator;
use rdfHelpers\GenericQuadIterator;
use rdfInterface\BlankNodeInterface as iBlankNode;
use rdfInterface\QuadInterface as iQuad;
use rdfInterface\QuadCompareInterface as iQuadCompare;
use rdfInterface\QuadIteratorInterface as iQuadIterator;
use rdfInterface\QuadIteratorAggregateInterface as iQuadIteratorAggregate;
use rdfInterface\TermIteratorInterface as iTermIterator;
use rdfInterface\DatasetInterface as iDataset;
use rdfInterface\DatasetMapReduceInterface as iDatasetMapReduce;
use rdfInterface\DatasetListQuadPartsInterface as iDatasetListQuadParts;
use rdfInterface\DatasetCompareInterface as iDatasetCompare;

/**
 * Description of Graph
 *
 * @author zozlak
 */
class Dataset implements iDataset, iDatasetMapReduce, iDatasetCompare, iDatasetListQuadParts {

    /**
     *
     * @var array<int, iQuad>
     */
    private array $quads = [];

    public function __construct() {
        
    }

    public function __toString(): string {
        $ret = '';
        foreach ($this->quads as $i) {
            $ret .= $i . "\n";
        }
        return $ret;
    }

    public function equals(iDataset $other): bool {
        $n = 0;
        // $other contained in $this
        foreach ($other as $i) {
            if ($i !== null && !($i->getSubject() instanceof iBlankNode) && !($i->getObject() instanceof iBlankNode)) {
                if (!$this->offsetExists($i)) {
                    return false;
                }
                $n++;
            }
        }
        // $this contained in $other
        foreach ($this as $i) {
            if (!($i->getSubject() instanceof iBlankNode) && !($i->getObject() instanceof iBlankNode)) {
                $n--;
            }
        }
        return $n === 0;
    }

    public function add(iQuad | iQuadIterator | iQuadIteratorAggregate $quads): void {
        if ($quads instanceof iQuad) {
            $quads = [$quads];
        }
        foreach ($quads as $i) {
            $match = false;
            foreach ($this->quads as $j) {
                if ($i !== null && $j->equals($i)) {
                    $match = true;
                    break;
                }
            }
            if (!$match && $i !== null) {
                $this->quads[] = $i;
            }
        }
    }

    public function copy(iQuadCompare | iQuadIterator | iQuadIteratorAggregate | callable | null $filter = null): Dataset {
        $dataset = new Dataset();
        try {
            foreach ($this->findMatchingQuads($filter) as $i) {
                $dataset->add($this->quads[$i]);
            }
        } catch (OutOfBoundsException) {
            
        }
        return $dataset;
    }

    public function copyExcept(iQuadCompare | iQuadIterator | iQuadIteratorAggregate | callable | null $filter): Dataset {
        $dataset = new Dataset();
        foreach ($this->findNotMatchingQuads($filter) as $i) {
            $dataset->add($this->quads[$i]);
        }
        return $dataset;
    }

    public function union(iQuad | iQuadIterator | iQuadIteratorAggregate $other): Dataset {
        $ret = new Dataset();
        $ret->add($this);
        $ret->add($other);
        return $ret;
    }

    public function xor(iQuad | iQuadIterator | iQuadIteratorAggregate $other): Dataset {
        $ret = $this->union($other);
        $ret->delete($this->copy($other));
        return $ret;
    }

    public function delete(iQuadCompare | iQuadIterator | iQuadIteratorAggregate | callable $filter): Dataset {
        $deleted = new Dataset();
        try {
            foreach ($this->findMatchingQuads($filter) as $i) {
                $deleted->add($this->quads[$i]);
                unset($this->quads[$i]);
            }
        } catch (OutOfBoundsException) {
            
        }
        return $deleted;
    }

    public function deleteExcept(iQuadCompare | iQuadIterator | iQuadIteratorAggregate | callable $filter): Dataset {
        $deleted = new Dataset();
        foreach ($this->findNotMatchingQuads($filter) as $i) {
            $deleted->add($this->quads[$i]);
            unset($this->quads[$i]);
        }
        return $deleted;
    }

    public function forEach(callable $fn,
                            iQuadCompare | iQuadIterator | iQuadIteratorAggregate | callable $filter = null): void {
        try {
            $idx = iterator_to_array($this->findMatchingQuads($filter)); // we need a copy as $this->quads will be modified in-place
            foreach ($idx as $i) {
                $val = $fn($this->quads[$i], $this);
                unset($this->quads[$i]);
                if ($val !== null) {
                    $this->add($val);
                }
            }
            $this->quads = array_values($this->quads);
        } catch (OutOfBoundsException $e) {
            
        }
    }

    // QuadIteratorAggregate

    public function getIterator(iQuadCompare | iQuadIterator | iQuadIteratorAggregate | callable | null $filter = null): GenericQuadIterator {
        return new GenericQuadIterator($filter !== null ? $this->copy($filter)->quads : $this->quads);
    }

    // Countable

    public function count(): int {
        return count($this->quads);
    }
    // ArrayAccess

    /**
     *
     * @param iQuad|iQuadCompare|callable|int<0, 0> $offset
     * @return bool
     */
    public function offsetExists($offset): bool {
        return $this->exists($offset);
    }

    private function exists(iQuadCompare | callable | int $offset): bool {
        try {
            $iter = $this->findMatchingQuads($offset);
            $this->checkIteratorEnd($iter);
            return true;
        } catch (OutOfBoundsException) {
            return false;
        }
    }

    /**
     *
     * @param iQuad|iQuadCompare|callable|int<0, 0> $offset
     * @return iQuad
     */
    public function offsetGet($offset): iQuad {
        return $this->get($offset);
    }

    /**
     * 
     * @param iQuadCompare|callable|int $offset
     * @return iQuad
     * @throws OutOfBoundsException
     */
    private function get(iQuadCompare | callable | int $offset): iQuad {
        $iter = $this->findMatchingQuads($offset);
        $idx  = $iter->current();
        $this->checkIteratorEnd($iter);
        return $this->quads[$idx];
    }

    /**
     *
     * @param iQuadCompare|callable|null $offset
     * @param iQuad $value
     * @return void
     */
    public function offsetSet($offset, $value): void {
        if ($offset === null) {
            $this->add($value);
        } else {
            $this->set($offset, $value);
        }
    }

    private function set(iQuadCompare | callable $offset, iQuad $value): void {
        $iter = $this->findMatchingQuads($offset);
        $idx  = $iter->current();
        $this->checkIteratorEnd($iter);
        if (!$this->quads[$idx]->equals($value)) {
            unset($this->quads[$idx]);
            $this->add($value);
        }
    }

    /**
     *
     * @param iQuadCompare|callable $offset
     * @return void
     */
    public function offsetUnset($offset): void {
        try {
            $iter  = $this->findMatchingQuads($offset);
            $match = $iter->current();
        } catch (OutOfBoundsException) {
            
        }
        if (isset($match) && isset($iter)) {
            $this->checkIteratorEnd($iter);
            array_splice($this->quads, $match, 1);
        }
    }

    public function map(callable $fn,
                        iQuadCompare | iQuadIterator | iQuadIteratorAggregate | callable $filter = null): Dataset {
        $ret = new Dataset();
        try {
            $idx = $this->findMatchingQuads($filter);
            foreach ($idx as $i) {
                $ret->add($fn($this->quads[$i], $this));
            }
        } catch (OutOfBoundsException $e) {
            
        }
        return $ret;
    }

    public function reduce(callable $fn, $initialValue = null,
                           iQuadCompare | iQuadIterator | iQuadIteratorAggregate | callable $filter = null): mixed {
        try {
            $idx = $this->findMatchingQuads($filter);
            foreach ($idx as $i) {
                $initialValue = $fn($initialValue, $this->quads[$i], $this);
            }
        } catch (OutOfBoundsException $e) {
            
        }
        return $initialValue;
    }

    public function any(iQuadCompare | iQuadIterator | iQuadIteratorAggregate | callable $filter): bool {
        try {
            $iter = $this->findMatchingQuads($filter);
            $iter = $iter->current(); // so PHP doesn't optimize previous line out
            return true;
        } catch (OutOfBoundsException) {
            return false;
        }
    }

    public function every(iQuadCompare | iQuadIterator | iQuadIteratorAggregate | callable $filter): bool {
        try {
            $n = 0;
            foreach ($this->findMatchingQuads($filter) as $i) {
                $n++;
            }
            return $n === $this->count();
        } catch (OutOfBoundsException) {
            return false;
        }
    }

    public function none(iQuadCompare | iQuadIterator | iQuadIteratorAggregate | callable $filter): bool {
        try {
            $iter = $this->findMatchingQuads($filter);
            $iter = $iter->current(); // so PHP doesn't optimize previous line out
            return false;
        } catch (OutOfBoundsException) {
            return true;
        }
    }
    // DatasetListQuadParts

    /**
     * 
     * @param iQuadCompare|iQuadIterator|iQuadIteratorAggregate|callable|null $filter
     * @return GenericTermIterator
     */
    public function listSubjects(iQuadCompare | iQuadIterator | iQuadIteratorAggregate | callable | null $filter = null): GenericTermIterator {
        return $this->listQuadElement($filter, 'getSubject');
    }

    /**
     * 
     * @param iQuadCompare|iQuadIterator|iQuadIteratorAggregate|callable|null $filter
     * @return GenericTermIterator
     */
    public function listPredicates(iQuadCompare | iQuadIterator | iQuadIteratorAggregate | callable | null $filter = null): GenericTermIterator {
        return $this->listQuadElement($filter, 'getPredicate');
    }

    /**
     * 
     * @param iQuadCompare|iQuadIterator|iQuadIteratorAggregate|callable|null $filter
     * @return GenericTermIterator
     */
    public function listObjects(iQuadCompare | iQuadIterator | iQuadIteratorAggregate | callable | null $filter = null): GenericTermIterator {
        return $this->listQuadElement($filter, 'getObject');
    }

    /**
     * 
     * @param iQuadCompare|iQuadIterator|iQuadIteratorAggregate|callable|null $filter
     * @return GenericTermIterator
     */
    public function listGraphs(iQuadCompare | iQuadIterator | iQuadIteratorAggregate | callable | null $filter = null): GenericTermIterator {
        return $this->listQuadElement($filter, 'getGraph');
    }

    private function listQuadElement(iQuadCompare | iQuadIterator | iQuadIteratorAggregate | callable | null $filter,
                                     string $elementFn): GenericTermIterator {
        $spotted = [];
        try {
            foreach ($this->findMatchingQuads($filter) as $i) {
                $i    = $this->quads[$i]->$elementFn();
                $flag = true;
                foreach ($spotted as $j) {
                    if ($j->equals($i)) {
                        $flag = false;
                        break;
                    }
                }
                if ($flag) {
                    $spotted[] = $i;
                }
            }
        } catch (OutOfBoundsException $ex) {
            
        }
        return new GenericTermIterator($spotted);
    }
    // Private Part

    /**
     *
     * @param iQuadCompare|iQuadIterator|iQuadIteratorAggregate|callable|null $offset
     * @return Generator<int>
     * @throws OutOfBoundsException
     */
    private function findMatchingQuads(
        iQuadCompare | iQuadIterator | iQuadIteratorAggregate | callable | int | null $offset
    ): Generator {
        if (is_int($offset) && $offset !== 0) {
            throw new OutOfBoundsException("Only integer offset of 0 is allowed");
        }
        $fn = $this->prepareMatchFunction($offset ?? true);
        $n  = 0;
        foreach ($this->quads as $i => $q) {
            if ($fn($q, $this)) {
                $n++;
                yield $i;
            }
        }
        if ($n === 0) {
            throw new OutOfBoundsException();
        }
    }

    /**
     *
     * @param iQuadCompare|iQuadIterator|iQuadIteratorAggregate|callable|null $offset
     * @return Generator<int>
     */
    private function findNotMatchingQuads(
        iQuadCompare | iQuadIterator | iQuadIteratorAggregate | callable | null $offset
    ): Generator {
        $fn = $this->prepareMatchFunction($offset ?? false);
        foreach ($this->quads as $i => $q) {
            if (!$fn($q, $this)) {
                yield $i;
            }
        }
    }

    private function prepareMatchFunction(iQuadCompare | iQuadIterator | iQuadIteratorAggregate | callable | bool | int $offset): callable {
        $fn = function () use ($offset) {
            return $offset;
        };
        if (is_callable($offset)) {
            $fn = $offset;
        } elseif ($offset instanceof iQuad || $offset instanceof iQuadCompare) {
            $fn = function (iQuad $x) use ($offset): bool {
                return $offset->equals($x);
            };
        } elseif ($offset instanceof iQuadIterator || $offset instanceof iQuadIteratorAggregate) {
            $fn = function (iQuad $x) use ($offset): bool {
                foreach ($offset as $i) {
                    if ($i->equals($x)) {
                        return true;
                    }
                }
                return false;
            };
        } elseif (is_int($offset)) {
            $fn = function (iQuad $x): bool {
                static $n = 0;
                return $n++ === 0;
            };
        }
        return $fn;
    }

    /**
     * 
     * @param Iterator<int> $i
     * @return void
     * @throws OutOfBoundsException
     */
    private function checkIteratorEnd(Iterator $i): void {
        $i->next();
        if ($i->key() !== null) {
            throw new OutOfBoundsException("More than one quad matched");
        }
    }
}
