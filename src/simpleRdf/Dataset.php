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
use rdfInterface\BlankNode as iBlankNode;
use rdfInterface\Quad as iQuad;
use rdfInterface\QuadCompare as iQuadCompare;
use rdfInterface\QuadIterator as iQuadIterator;
use rdfInterface\TermIterator as iTermIterator;
use rdfInterface\Dataset as iDataset;
use rdfInterface\DatasetMapReduce as iDatasetMapReduce;
use rdfInterface\DatasetListQuadParts as iDatasetListQuadParts;
use rdfInterface\DatasetCompare as iDatasetCompare;

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
            if (!($i?->getSubject() instanceof iBlankNode) && !($i?->getObject() instanceof iBlankNode)) {
                $n--;
            }
        }
        return $n === 0;
    }

    public function add(iQuad | iQuadIterator $quads): void {
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

    public function copy(iQuadCompare | iQuadIterator | callable | null $filter = null): iDataset {
        $dataset = new Dataset();
        try {
            foreach ($this->findMatchingQuads($filter) as $i) {
                $dataset->add($this->quads[$i]);
            }
        } catch (OutOfBoundsException) {
            
        }
        return $dataset;
    }

    public function copyExcept(iQuadCompare | iQuadIterator | callable | null $filter): iDataset {
        $dataset = new Dataset();
        foreach ($this->findNotMatchingQuads($filter) as $i) {
            $dataset->add($this->quads[$i]);
        }
        return $dataset;
    }

    public function union(iQuad | iQuadIterator $other): iDataset {
        $ret = new Dataset();
        $ret->add($this);
        $ret->add($other);
        return $ret;
    }

    public function xor(iQuad | iQuadIterator $other): iDataset {
        $ret = $this->union($other);
        $ret->delete($this->copy($other));
        return $ret;
    }

    public function delete(iQuadCompare | iQuadIterator | callable $filter): iDataset {
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

    public function deleteExcept(iQuadCompare | iQuadIterator | callable $filter): iDataset {
        $deleted = new Dataset();
        foreach ($this->findNotMatchingQuads($filter) as $i) {
            $deleted->add($this->quads[$i]);
            unset($this->quads[$i]);
        }
        return $deleted;
    }

    public function forEach(callable $fn,
                            iQuadCompare | iQuadIterator | callable $filter = null): void {
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

    // QuadIterator

    public function current(): iQuad | null {
        $value = current($this->quads);
        return $value === false ? null : $value;
    }

    public function key(): mixed {
        $key = key($this->quads);
        if ($key === null) {
            throw new OutOfBoundsException();
        }
        return $key;
    }

    public function next(): void {
        next($this->quads);
    }

    public function rewind(): void {
        reset($this->quads);
    }

    public function valid(): bool {
        return key($this->quads) !== null;
    }

    // Countable

    public function count(): int {
        return count($this->quads);
    }
    // ArrayAccess

    /**
     *
     * @param iQuad|iQuadCompare|callable $offset
     * @return bool
     */
    public function offsetExists($offset): bool {
        return $this->exists($offset);
    }

    private function exists(iQuadCompare | callable $offset): bool {
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
     * @param iQuad|iQuadCompare|callable $offset
     * @return iQuad
     */
    public function offsetGet($offset): iQuad {
        return $this->get($offset);
    }

    private function get(iQuadCompare | callable $offset): iQuad {
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
                        iQuadCompare | iQuadIterator | callable $filter = null): iDatasetMapReduce {
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
                           iQuadCompare | iQuadIterator | callable $filter = null): mixed {
        try {
            $idx = $this->findMatchingQuads($filter);
            foreach ($idx as $i) {
                $initialValue = $fn($initialValue, $this->quads[$i], $this);
            }
        } catch (OutOfBoundsException $e) {
            
        }
        return $initialValue;
    }

    public function any(iQuadCompare | iQuadIterator | callable $filter): bool {
        try {
            $iter = $this->findMatchingQuads($filter);
            $iter = $iter->current(); // so PHP doesn't optimize previous line out
            return true;
        } catch (OutOfBoundsException) {
            return false;
        }
    }

    public function every(iQuadCompare | iQuadIterator | callable $filter): bool {
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

    public function none(iQuadCompare | iQuadIterator | callable $filter): bool {
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
     * @param iQuadCompare|iQuadIterator|callable|null $filter
     * @return iTermIterator
     */
    public function listSubjects(iQuadCompare | iQuadIterator | callable | null $filter = null): iTermIterator {
        return $this->listQuadElement($filter, 'getSubject');
    }

    /**
     * 
     * @param iQuadCompare|iQuadIterator|callable|null $filter
     * @return iTermIterator
     */
    public function listPredicates(iQuadCompare | iQuadIterator | callable | null $filter = null): iTermIterator {
        return $this->listQuadElement($filter, 'getPredicate');
    }

    /**
     * 
     * @param iQuadCompare|iQuadIterator|callable|null $filter
     * @return iTermIterator
     */
    public function listObjects(iQuadCompare | iQuadIterator | callable | null $filter = null): iTermIterator {
        return $this->listQuadElement($filter, 'getObject');
    }

    /**
     * 
     * @param iQuadCompare|iQuadIterator|callable|null $filter
     * @return iTermIterator
     */
    public function listGraphs(iQuadCompare | iQuadIterator | callable | null $filter = null): iTermIterator {
        return $this->listQuadElement($filter, 'getGraph');
    }

    private function listQuadElement(iQuadCompare | iQuadIterator | callable | null $filter,
                                     string $elementFn): iTermIterator {
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
     * @param iQuadCompare|iQuadIterator|callable|null $offset
     * @return Generator<int>
     * @throws OutOfBoundsException
     */
    private function findMatchingQuads(
        iQuadCompare | iQuadIterator | callable | null $offset
    ): Generator {
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
     * @param iQuadCompare|iQuadIterator|callable|null $offset
     * @return Generator<int>
     */
    private function findNotMatchingQuads(
        iQuadCompare | iQuadIterator | callable | null $offset
    ): Generator {
        $fn = $this->prepareMatchFunction($offset ?? false);
        foreach ($this->quads as $i => $q) {
            if (!$fn($q, $this)) {
                yield $i;
            }
        }
    }

    private function prepareMatchFunction(iQuadCompare | iQuadIterator | callable | bool $offset): callable {
        $fn = function () use ($offset) {
            return $offset;
        };
        if (is_callable($offset)) {
            $fn = $offset;
        } elseif ($offset instanceof iQuad || $offset instanceof iQuadCompare) {
            $fn = function (iQuad $x) use ($offset): bool {
                return $offset->equals($x);
            };
        } elseif ($offset instanceof iQuadIterator) {
            $fn = function (iQuad $x) use ($offset): bool {
                foreach ($offset as $i) {
                    if ($i?->equals($x)) {
                        return true;
                    }
                }
                return false;
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
