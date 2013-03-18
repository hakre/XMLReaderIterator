## Iterators for [PHP `XMLReader`](http://php.net/XMLReader) for ease of parsing

### Changelog:

 - `0.0.23` first try of a compatibility layer for PHP installs with a libxml version below version 2.6.20.
  Functions with compat checks are `XMLReaderNode::readOuterXml()` and `XMLReaderNode::readString()`. Affected
  functions are  `XMLReaderNode::asSimpleXML()` and `XMLElementIterator::toArray()`.

 - `0.0.21` moved library into mew repository and added `XMLReaderAggregate`.

 - `0.0.19` added `XMLElementXpathFilter`, a `FilterIterator` for `XMLReaderIterator` by an Xpath
 expression.

        $reader = new XMLReader();
        $reader->open($xmlFile);
        $it = new XMLElementIterator($reader);
        $list = new XMLElementXpathFilter($it, '//user[@id = "1" or @id = "6"]//message');

        foreach($list as $message) {
            echo " * ",  $message->readString(), "\n";
        }

### Code examples for the XMLReader Iterators (latests on top):

- [PHP XML parser: How to read only part of the XML document?](http://stackoverflow.com/a/15443517/367456)
- [Parse XML with PHP and XMLReader](http://stackoverflow.com/a/15351723/367456)
- [Getting XML Attribute with XMLReader and PHP](http://stackoverflow.com/a/15399491/367456)