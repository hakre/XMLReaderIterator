<?php

require('xmlreader-iterators.php'); // require XMLReaderIterator library

$xmlFile = 'data/products.xml';

$reader = XMLReader::open($xmlFile);
$iterator = new XMLElementIterator($reader);
$list = new XMLElementXpathFilter($iterator, '//product');


/** @var \XMLReaderNode $result */
foreach($list as $item) {
    var_dump($item->getName());
    var_dump($item->getAttribute('sku'));

    foreach ($item->getChildElements('attributes') as $attributeList)  {
        foreach($attributeList->getChildElements() as $attribute) {
            var_dump('  > ' . $attribute->name);
        }
    }
}