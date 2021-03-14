<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace simpleRdf;

use simpleRdf\DataFactory as DF;
use rdfInterface\Term as iTerm;
use rdfInterface\BlankNode as iBlankNode;
use rdfInterface\NamedNode as iNamedNode;
use rdfInterface\Literal as iLiteral;
use rdfInterface\DefaultGraph as iDefaultGraph;
use rdfInterface\Quad as iQuad;
use rdfInterface\QuadTemplate as iQuadTemplate;

/**
 * Description of LoggerTest
 *
 * @author zozlak
 */
class DataFactoryTest extends \PHPUnit\Framework\TestCase
{

    public function testCreateBasic(): void
    {
        $bn = DF::blankNode();
        $nn = DF::namedNode('foo');
        $l  = DF::literal('foo', 'lang');
        $dg = DF::defaultGraph();
        $q  = DF::quad($bn, $nn, $l, $dg);
        $qt = DF::quadTemplate($bn, $nn, $l, $dg);

        $this->assertInstanceOf(iTerm::class, $bn);
        $this->assertInstanceOf(iTerm::class, $nn);
        $this->assertInstanceOf(iTerm::class, $l);
        $this->assertInstanceOf(iTerm::class, $dg);
        $this->assertInstanceOf(iTerm::class, $q);
        $this->assertInstanceOf(iTerm::class, $qt);
        $this->assertInstanceOf(iBlankNode::class, $bn);
        $this->assertInstanceOf(iNamedNode::class, $nn);
        $this->assertInstanceOf(iLiteral::class, $l);
        $this->assertInstanceOf(iDefaultGraph::class, $dg);
        $this->assertInstanceOf(iQuad::class, $q);
        $this->assertInstanceOf(iQuadTemplate::class, $qt);
    }

}
