<?php
/*
 * Example: Iterate over all elements
 *
 * This file is part of XMLReaderIterator.
 */

require('xmlreader-iterators.php'); // require XMLReaderIterator library

$xmlFile = 'data/movies.xml';

$reader = new XMLReader();
$reader->open($xmlFile);
$element = new XMLReaderNode($reader);
$it = new XMLElementIterator($reader);

foreach($it as $index => $element) {
    printf("#%02d: %s\n", $index, XMLBuild::readerNode($reader));
}
