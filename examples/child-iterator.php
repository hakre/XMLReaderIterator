<?php
/*
 * This file is part of the XMLReaderIterator package.
 *
 * Copyright (C) 2012, 2013, 2022 hakre <https://hakre.wordpress.com>
 *
 * Example: Iterate over all elements
 */

require('xmlreader-iterators.php'); // require XMLReaderIterator library

$xmlFile = 'data/sample-rss-091.xml';

$reader = new XMLReader();
$reader->open($xmlFile);

$items = new XMLElementIterator($reader, 'item');

foreach ($items as $index => $item) {
    printf(
        "#%02d: %s %s\n",
        $index,
        XMLBuild::readerNode($reader),
        $item->getSimpleXMLElement()->title
    );

    foreach ($item->getChildElements() as $childIndex => $childElement) {
        printf(
            "  child #%02d: %s %s\n",
            $childIndex,
            XMLBuild::readerNode($childElement->getReader()),
            XMLBuild::dumpString($childElement->readString(), 64)
        );
    }

    $items->skipNextRead();
}
