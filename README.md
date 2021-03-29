# simpleRdf

[![Latest Stable Version](https://poser.pugx.org/sweetrdf/simple-rdf/v/stable)](https://packagist.org/packages/sweetrdf/simple-rdf)
![Build status](https://github.com/sweetrdf/simpleRdf/workflows/phpunit/badge.svg?branch=master)
[![Coverage Status](https://coveralls.io/repos/github/sweetrdf/simpleRdf/badge.svg?branch=master)](https://coveralls.io/github/sweetrdf/simpleRdf?branch=master)
[![License](https://poser.pugx.org/sweetrdf/simple-rdf/license)](https://packagist.org/packages/sweetrdf/simple-rdf)

An RDF library for PHP implementing the https://github.com/sweetrdf/rdfInterface interface.

The aim was to provide as simple, short and clear implementation as possible. Performance wasn't really important (see the Performance chapter below).

It can be used as a baseline for testing performance of other libraries as well for testing interoperability of the rdfInterface implementations (e.g. making sure they work correctly with `rdfInterface\Term` objects created by other library).

## Installation

* Obtain the [Composer](https://getcomposer.org)
* Run `composer require sweetrdf/simple-rdf`
* Run `composer require sweetrdf/quick-rdf-io` to install parsers and serializers.

## Automatically generated documentation

https://sweetrdf.github.io/simpleRdf/namespaces/simplerdf.html

It's very incomplete but better than nothing.\
[RdfInterface](https://github.com/sweetrdf/rdfInterface/) documentation is included which explains the most important design decisions.

## Usage

```php
include 'vendor/autoload.php';

use simpleRdf\DataFactory as DF;

$graph = new simpleRdf\Dataset();
$parser = new quickRdfIo\TriGParser(new simpleRdf\DataFactory());
$stream = fopen('pathToTurtleFile', 'r');
$graph->add($parser->parseStream($stream));
fclose($stream);

// count edges in the graph
echo count($graph);

// go trough all edges in the graph
foreach ($graph as $i) {
  echo "$i\n";
}

// find all graph edges with a given subject
echo $graph->copy(DF::quadTemplate(DF::namedNode('http://mySubject')));

// find all graph edges with a given predicate
echo $graph->copy(DF::quadTemplate(null, DF::namedNode('http://myPredicate')));

// find all graph edges with a given object
echo $graph->copy(DF::quadTemplate(null, null, DF::literal('value', 'en')));

// replace an edge in the graph
$edge = DF::quad(DF::namedNode('http://edgeSubject'), DF::namedNode('http://edgePredicate'), DF::namedNode('http://edgeObject'));
$graph[$edge] = $edge->withObject(DF::namedNode('http://anotherObject'));

// find intersection with other graph
$graph->copy($otherGraph); // immutable
$graph->delete($otherGraph); // in-place

// compute union with other graph
$graph->union($otherGraph); // immutable
$graph->add($otherGraph); // in-place

// compute set difference with other graph
$graph->copyExcept($otherGraph); // immutable
$graph->delete($otherGraph); // in-place

$serializer = new quickRdfIo\TurtleSerializer();
$stream = fopen('pathToOutputTurtleFile', 'w');
$serializer->serializeStream($stream, $graph);
fclose($stream);
```

## Performance

The `simpleRdf\Dataset` class **shouldn't be used** to deal with larger amounts of quads.

Adding a quad has computational complexity of `O(N)` where `N` is the number of quads in the dataset.
It means adding `n` quads has computational complexity of `O(n!)` (read - it quickly gets out of hand with larger `n`).

Just a sample results:

| quads count | execution time [s] | relative quads count | relative execution time |
|------|-----------|-----|--------|
|  125 |  0.018090 |   1 |    1.0 |
|  250 |  0.059559 |   2 |    3.3 |
|  500 |  0.210958 |   4 |   11.7 |
| 1000 |  0.849956 |   8 |   47.0 |
| 2000 |  2.941319 |  16 |  162.6 |
| 4000 | 10.697164 |  32 |  591.3 |
| 8000 | 45.792556 |  64 | 2531.4 |

You get the idea, don't you?

Other operations are also not performant, e.g. all searches and in-place deletions have complexity of `O(N)` and all methods returning a new copy of the dataset have complexity of `O(N) + O(n!)` (where `N` is number of quads in the dataset and `n` the number of quads in the returned dataset).

If you are looking for a performant implementation, please take a look at the [quickRdf](https://github.com/sweetrdf/quickRdf).

