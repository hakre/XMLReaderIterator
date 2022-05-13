<?php
/*
 * This file is part of the XMLReaderIterator package.
 *
 * Copyright (C) 2021, 2022 hakre <http://hakre.wordpress.com>
 *
 * Example: Write XML with XMLWriter while reading from XMLReader with
 * XMLWriterIteration - with CDATA; insert a new child element, here with
 * SimpleXMLElement.
 */

require('xmlreader-iterators.php'); // require XMLReaderIterator library

$xmlInputFile  = 'data/offers.xml';
$xmlOutputFile = 'php://output';

$reader = new XMLReader();
$reader->open($xmlInputFile);

$writer = new XMLWriter();
$writer->openUri($xmlOutputFile);

$iterator = new XMLWritingIteration($writer, $reader);

$writer->startDocument();

foreach ($iterator as $node) {
    $isElement = $node->nodeType === XMLReader::ELEMENT;

    if ($isElement && $node->name === 'offer' && !$node->isEmptyElement) {
        $node = new XMLReaderNode($node);
        $elem = $node->getSimpleXMLElement();
        $elem->is_active = 'true';

        $writer->writeRaw($elem->asXML());
        $reader->next();
        $iterator->skipNextRead();
    } else {
        $iterator->write();
    }
}

$writer->endDocument();

