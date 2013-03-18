<?php
/*
 * Example: Filter XML elements by xpath expression
 *
 * This file is part of XMLReaderIterator.
 */

require('xmlreader-iterators.php'); // require XMLReaderIterator library

$xmlFile = 'data/posts.xml';

$reader = new XMLReader();
$reader->open($xmlFile);

$it = new XMLElementIterator($reader);
$list = new XMLElementXpathFilter($it, '//user[@id = "1" or @id = "6"]//message');

foreach($list as $message) {
    echo " * ",  $message->readString(), "\n";
}
