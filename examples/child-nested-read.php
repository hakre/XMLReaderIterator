<?php

require('xmlreader-iterators.php'); // require XMLReaderIterator library

$xmlFile = 'data/products.xml';

/** @var \Traversable<int,\XMLReaderNode> $list */
$iterator = new XMLElementIterator(XMLReader::open($xmlFile));
$list     = new XMLElementXpathFilter($iterator, '//product');

foreach ($list as $item) {
    printf('Found product "%s"' . \PHP_EOL, $item->getAttribute('sku'));

    foreach ($item->getChildElements('attributes') as $attributeList) {
        foreach ($attributeList->getChildElements() as $attribute) {
            printf('  - %s: %s' . \PHP_EOL, $attribute->name, (string)$attribute);
        }
    }
}