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
use rdfInterface\Literal as iLiteral;
use simpleRdf\DataFactory as DF;

/**
 * Description of Literal
 *
 * @author zozlak
 */
class Literal implements \rdfInterface\Literal {

    /**
     *
     * @var int | float | string | bool | Stringable
     */
    private $value;

    /**
     *
     * @var string|null
     */
    private $lang;

    /**
     *
     * @var string|null
     */
    private $datatype;

    public function __construct(
        int | float | string | bool | Stringable $value, ?string $lang = null,
        ?string $datatype = null
    ) {
        $lang     = empty($lang) ? null : $lang;
        $datatype = empty($datatype) ? RDF::XSD_STRING : $datatype;

        $this->value    = $value;
        $this->lang     = $lang;
        $this->datatype = $datatype;
    }

    public function __toString(): string {
        $langtype = '';
        if (!empty($this->lang)) {
            $langtype = "@" . $this->lang;
        } elseif (!empty($this->datatype)) {
            $langtype = "^^<$this->datatype>";
        }
        return '"' . $this->value . '"' . $langtype;
    }

    public function getValue(): int | float | string | bool | Stringable {
        return $this->value;
    }

    public function getLang(): ?string {
        return $this->lang;
    }

    public function getDatatype(): string {
        return $this->datatype ?? RDF::XSD_STRING;
    }

    public function getType(): string {
        return \rdfInterface\TYPE_LITERAL;
    }

    public function equals(\rdfInterface\Term $term): bool {
        if ($term instanceof iLiteral) {
            return $this->getValue() === $term->getValue() &&
                $this->getLang() === $term->getLang() &&
                $this->getDatatype() === $term->getDatatype();
        } else {
            return false;
        }
    }

    public function withValue(int | float | string | bool | Stringable $value): \rdfInterface\Literal {
        return DF::literal($value, $this->lang, $this->datatype);
    }

    public function withLang(?string $lang): \rdfInterface\Literal {
        $datatype = empty($lang) ? $this->datatype : RDF::XSD_STRING;
        return DF::literal($this->value, $lang, $datatype);
    }

    public function withDatatype(?string $datatype): \rdfInterface\Literal {
        $datatype = empty($datatype) ? RDF::XSD_STRING : $datatype;
        $lang     = $datatype === RDF::XSD_STRING ? $this->lang : null;
        return DF::literal($this->value, $lang, $datatype);
    }
}
