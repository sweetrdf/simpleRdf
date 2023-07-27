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
use UnexpectedValueException;
use rdfHelpers\GenericTermIterator;
use rdfHelpers\GenericQuadIterator;
use rdfInterface\BlankNodeInterface;
use rdfInterface\QuadInterface;
use rdfInterface\QuadCompareInterface;
use rdfInterface\QuadIteratorInterface;
use rdfInterface\QuadIteratorAggregateInterface;
use rdfInterface\DatasetInterface;
use rdfInterface\MultipleQuadsMatchedException;

/**
 * Description of Graph
 *
 * @author zozlak
 */
class Dataset implements DatasetInterface {

    static public function factory(): Dataset {
        return new Dataset();
    }

    /**
     *
     * @var array<int, QuadInterface>
     */
    private array $quads = [];

    public function __toString(): string {
        $ret = '';
        foreach ($this->quads as $i) {
            $ret .= $i . "\n";
        }
        return $ret;
    }

    public function equals(DatasetInterface $other): bool {
        $n = 0;
        // $other contained in $this
        foreach ($other as $i) {
            if ($i !== null && !($i->getSubject() instanceof BlankNodeInterface) && !($i->getObject() instanceof BlankNodeInterface)) {
                if (!$this->offsetExists($i)) {
                    return false;
                }
                $n++;
            }
        }
        // $this contained in $other
        foreach ($this as $i) {
            if (!($i->getSubject() instanceof BlankNodeInterface) && !($i->getObject() instanceof BlankNodeInterface)) {
                $n--;
            }
        }
        return $n === 0;
    }

    /**
     * 
     * @param QuadInterface|\Traversable<\rdfInterface\QuadInterface>|array<\rdfInterface\QuadInterface> $quads
     * @return void
     */
    public function add(QuadInterface | \Traversable | array $quads): void {
        if ($quads instanceof QuadInterface) {
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

    public function copy(QuadCompareInterface | QuadIteratorInterface | QuadIteratorAggregateInterface | callable | null $filter = null): Dataset {
        $dataset = new Dataset();
        foreach ($this->findMatchingQuads($filter) as $i) {
            $dataset->add($this->quads[$i]);
        }
        return $dataset;
    }

    public function copyExcept(QuadCompareInterface | QuadIteratorInterface | QuadIteratorAggregateInterface | callable | null $filter): Dataset {
        $dataset = new Dataset();
        foreach ($this->findNotMatchingQuads($filter) as $i) {
            $dataset->add($this->quads[$i]);
        }
        return $dataset;
    }

    public function union(QuadInterface | QuadIteratorInterface | QuadIteratorAggregateInterface $other): Dataset {
        $ret = new Dataset();
        $ret->add($this);
        $ret->add($other);
        return $ret;
    }

    public function xor(QuadInterface | QuadIteratorInterface | QuadIteratorAggregateInterface $other): Dataset {
        $ret = $this->union($other);
        $ret->delete($this->copy($other));
        return $ret;
    }

    public function delete(QuadCompareInterface | QuadIteratorInterface | QuadIteratorAggregateInterface | callable $filter): Dataset {
        $deleted = new Dataset();
        foreach ($this->findMatchingQuads($filter) as $i) {
            $deleted->add($this->quads[$i]);
            unset($this->quads[$i]);
        }
        return $deleted;
    }

    public function deleteExcept(QuadCompareInterface | QuadIteratorInterface | QuadIteratorAggregateInterface | callable $filter): Dataset {
        $deleted = new Dataset();
        foreach ($this->findNotMatchingQuads($filter) as $i) {
            $deleted->add($this->quads[$i]);
            unset($this->quads[$i]);
        }
        return $deleted;
    }

    public function forEach(callable $fn,
                            QuadCompareInterface | QuadIteratorInterface | QuadIteratorAggregateInterface | callable $filter = null): void {
        $idx = iterator_to_array($this->findMatchingQuads($filter)); // we need a copy as $this->quads will be modified in-place
        foreach ($idx as $i) {
            $val = $fn($this->quads[$i], $this);
            unset($this->quads[$i]);
            if ($val !== null) {
                $this->add($val);
            }
        }
        $this->quads = array_values($this->quads);
    }

    // QuadIteratorAggregate

    public function getIterator(QuadCompareInterface | QuadIteratorInterface | QuadIteratorAggregateInterface | callable | null $filter = null): GenericQuadIterator {
        return new GenericQuadIterator($filter !== null ? $this->copy($filter)->quads : $this->quads);
    }

    // Countable

    public function count(): int {
        return count($this->quads);
    }
    // ArrayAccess

    /**
     *
     * @param QuadCompareInterface|callable|int<0, 0> $offset
     * @return bool
     * @throws UnexpectedValueException
     * @throws MultipleQuadsMatchedException
     */
    public function offsetExists($offset): bool {
        if (!($offset instanceof QuadCompareInterface || is_callable($offset) || $offset === 0)) {
            throw new UnexpectedValueException();
        }
        return $this->exists($offset);
    }

    private function exists(QuadCompareInterface | callable | int $offset): bool {
        $iter = $this->findMatchingQuads($offset);
        return $this->checkIterator($iter, false) !== null;
    }

    /**
     *
     * @param QuadCompareInterface|callable|int<0, 0> $offset
     * @return QuadInterface
     * @throws UnexpectedValueException
     * @throws MultipleQuadsMatchedException
     */
    public function offsetGet($offset): QuadInterface {
        if (!($offset instanceof QuadCompareInterface || is_callable($offset) || $offset === 0)) {
            throw new UnexpectedValueException();
        }
        return $this->get($offset);
    }

    private function get(QuadCompareInterface | callable | int $offset): QuadInterface {
        $iter = $this->findMatchingQuads($offset);
        return $this->quads[$this->checkIterator($iter, true)];
    }

    /**
     *
     * @param QuadCompareInterface|callable|null $offset
     * @param QuadInterface $value
     * @return void
     * @throws UnexpectedValueException
     * @throws MultipleQuadsMatchedException
     */
    public function offsetSet($offset, $value): void {
        if (!($offset instanceof QuadCompareInterface || is_callable($offset) || $offset === null)) {
            throw new UnexpectedValueException();
        }
        if ($offset === null) {
            $this->add($value);
        } else {
            $this->set($offset, $value);
        }
    }

    private function set(QuadCompareInterface | callable $offset,
                         QuadInterface $value): void {
        $iter = $this->findMatchingQuads($offset);
        $idx  = $this->checkIterator($iter, true);
        if (!$this->quads[$idx]->equals($value)) {
            unset($this->quads[$idx]);
            $this->add($value);
        }
    }

    /**
     *
     * @param QuadCompareInterface|callable $offset
     * @return void
     * @throws UnexpectedValueException
     * @throws MultipleQuadsMatchedException
     */
    public function offsetUnset($offset): void {
        if (!($offset instanceof QuadCompareInterface || is_callable($offset))) {
            throw new UnexpectedValueException();
        }
        $this->unset($offset);
    }

    private function unset(QuadCompareInterface | callable $offset): void {
        $iter = $this->findMatchingQuads($offset);
        $idx  = $this->checkIterator($iter, false);
        if ($idx !== null) {
            array_splice($this->quads, $idx, 1);
        }
    }

    public function map(callable $fn,
                        QuadCompareInterface | QuadIteratorInterface | QuadIteratorAggregateInterface | callable $filter = null): Dataset {
        $ret = new Dataset();
        $idx = $this->findMatchingQuads($filter);
        foreach ($idx as $i) {
            $ret->add($fn($this->quads[$i], $this));
        }
        return $ret;
    }

    public function reduce(callable $fn, $initialValue = null,
                           QuadCompareInterface | QuadIteratorInterface | QuadIteratorAggregateInterface | callable $filter = null): mixed {
        $idx = $this->findMatchingQuads($filter);
        foreach ($idx as $i) {
            $initialValue = $fn($initialValue, $this->quads[$i], $this);
        }
        return $initialValue;
    }

    public function any(QuadCompareInterface | QuadIteratorInterface | QuadIteratorAggregateInterface | callable $filter): bool {
        $iter = $this->findMatchingQuads($filter);
        return $iter->valid();
    }

    public function every(QuadCompareInterface | QuadIteratorInterface | QuadIteratorAggregateInterface | callable $filter): bool {
        return iterator_count($this->findMatchingQuads($filter)) === $this->count();
    }

    public function none(QuadCompareInterface | QuadIteratorInterface | QuadIteratorAggregateInterface | callable $filter): bool {
        return !$this->any($filter);
    }
    // DatasetListQuadParts

    /**
     * 
     * @param QuadCompareInterface|QuadIteratorInterface|QuadIteratorAggregateInterface|callable|null $filter
     * @return GenericTermIterator
     */
    public function listSubjects(QuadCompareInterface | QuadIteratorInterface | QuadIteratorAggregateInterface | callable | null $filter = null): GenericTermIterator {
        return $this->listQuadElement($filter, 'getSubject');
    }

    /**
     * 
     * @param QuadCompareInterface|QuadIteratorInterface|QuadIteratorAggregateInterface|callable|null $filter
     * @return GenericTermIterator
     */
    public function listPredicates(QuadCompareInterface | QuadIteratorInterface | QuadIteratorAggregateInterface | callable | null $filter = null): GenericTermIterator {
        return $this->listQuadElement($filter, 'getPredicate');
    }

    /**
     * 
     * @param QuadCompareInterface|QuadIteratorInterface|QuadIteratorAggregateInterface|callable|null $filter
     * @return GenericTermIterator
     */
    public function listObjects(QuadCompareInterface | QuadIteratorInterface | QuadIteratorAggregateInterface | callable | null $filter = null): GenericTermIterator {
        return $this->listQuadElement($filter, 'getObject');
    }

    /**
     * 
     * @param QuadCompareInterface|QuadIteratorInterface|QuadIteratorAggregateInterface|callable|null $filter
     * @return GenericTermIterator
     */
    public function listGraphs(QuadCompareInterface | QuadIteratorInterface | QuadIteratorAggregateInterface | callable | null $filter = null): GenericTermIterator {
        return $this->listQuadElement($filter, 'getGraph');
    }

    private function listQuadElement(QuadCompareInterface | QuadIteratorInterface | QuadIteratorAggregateInterface | callable | null $filter,
                                     string $elementFn): GenericTermIterator {
        $spotted = [];
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
        return new GenericTermIterator($spotted);
    }
    // Private Part

    /**
     *
     * @param QuadCompareInterface|QuadIteratorInterface|QuadIteratorAggregateInterface|callable|int<0, 0>|null $offset
     * @return Generator<int>
     * @throws UnexpectedValueException
     */
    private function findMatchingQuads(
        QuadCompareInterface | QuadIteratorInterface | QuadIteratorAggregateInterface | callable | int | null $offset
    ): Generator {
        if (is_int($offset) && $offset !== 0) {
            throw new UnexpectedValueException("Only integer offset of 0 is allowed");
        }
        $fn = $this->prepareMatchFunction($offset ?? true);
        $n  = 0;
        foreach ($this->quads as $i => $q) {
            if ($fn($q, $this)) {
                $n++;
                yield $i;
            }
        }
    }

    /**
     *
     * @param QuadCompareInterface|QuadIteratorInterface|QuadIteratorAggregateInterface|callable|null $offset
     * @return Generator<int>
     */
    private function findNotMatchingQuads(
        QuadCompareInterface | QuadIteratorInterface | QuadIteratorAggregateInterface | callable | null $offset
    ): Generator {
        $fn = $this->prepareMatchFunction($offset ?? false);
        foreach ($this->quads as $i => $q) {
            if (!$fn($q, $this)) {
                yield $i;
            }
        }
    }

    private function prepareMatchFunction(QuadCompareInterface | QuadIteratorInterface | QuadIteratorAggregateInterface | callable | bool | int $offset): callable {
        $fn = function () use ($offset) {
            return $offset;
        };
        if (is_callable($offset)) {
            $fn = $offset;
        } elseif ($offset instanceof QuadInterface || $offset instanceof QuadCompareInterface) {
            $fn = function (QuadInterface $x) use ($offset): bool {
                return $offset->equals($x);
            };
        } elseif ($offset instanceof QuadIteratorInterface || $offset instanceof QuadIteratorAggregateInterface) {
            $fn = function (QuadInterface $x) use ($offset): bool {
                foreach ($offset as $i) {
                    if ($i->equals($x)) {
                        return true;
                    }
                }
                return false;
            };
        } elseif (is_int($offset)) {
            $fn = function (QuadInterface $x): bool {
                static $n = 0;
                return $n++ === 0;
            };
        }
        return $fn;
    }

    /**
     * 
     * @param Iterator<int> $i
     * @return int|null
     * @throws UnexpectedValueException
     * @throws MultipleQuadsMatchedException
     */
    private function checkIterator(Iterator $i, bool $errorOrInvalid): int | null {
        if (!$i->valid()) {
            if ($errorOrInvalid) {
                throw new UnexpectedValueException();
            } else {
                return null;
            }
        }
        $ret = $i->current();
        $i->next();
        if ($i->valid()) {
            throw new MultipleQuadsMatchedException();
        }
        return $ret;
    }
}
