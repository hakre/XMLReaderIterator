<?php
/*
 * XMLReaderIterator <http://git.io/xmlreaderiterator>
 *
 * Copyright (C) 2012, 2013 hakre <http://hakre.wordpress.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author hakre <http://hakre.wordpress.com>
 * @license AGPL-3.0 <http://spdx.org/licenses/AGPL-3.0>
 * @version 0.1.0
 */

/**
 * Class XMLReaderAggregate
 *
 * @since 0.0.21
 */
interface XMLReaderAggregate
{
    /**
     * @return XMLReader
     */
    public function getReader();
}

/**
 * Module XMLBuild
 *
 * Some string functions helping to create XML
 *
 * @since 0.0.23
 */
abstract class XMLBuild
{

    /**
     * indentLines()
     *
     * this will add a line-separator at the end of the last line because if it was
     * empty it is not any longer and deserves one.
     *
     * @param string $lines
     * @param string $indent (optional)
     *
     * @return string
     */
    public static function indentLines($lines, $indent = '  ')
    {
        $lineSeparator = "\n";
        $buffer        = '';
        $line          = strtok($lines, $lineSeparator);
        while ($line) {
            $buffer .= $indent . $line . $lineSeparator;
            $line = strtok($lineSeparator);
        }
        strtok(null, null);

        return $buffer;
    }

    /**
     * @param string            $name
     * @param array|Traversable $attributes  attributeName => attributeValue string pairs
     * @param bool              $emptyTag    create an empty element tag (commonly known as short tags)
     *
     * @return string
     */
    public static function startTag($name, $attributes, $emptyTag = false)
    {
        $buffer = '<' . $name;
        $buffer .= static::attributes($attributes);
        $buffer .= $emptyTag ? '/>' : '>';

        return $buffer;
    }

    /**
     * @param array|Traversable $attributes  attributeName => attributeValue string pairs
     *
     * @return string
     */
    public static function attributes($attributes)
    {
        $buffer = '';

        foreach ($attributes as $name => $value) {
            $buffer .= ' ' . $name . '="' . static::attributeValue($value) . '"';
        }

        return $buffer;
    }

    /**
     * @param string $value
     *
     * @return string
     */
    public static function attributeValue($value)
    {
        $buffer = $value;

        // REC-xml/#AVNormalize - preserve
        // REC-xml/#sec-line-ends - preserve
        $buffer = preg_replace_callback('~\r\n|\r(?!\n)|\t~', 'self::numericEntitiesSingleByte', $buffer);

        return htmlspecialchars($buffer, ENT_QUOTES, 'UTF-8', false);
    }

    /**
     * @param string            $name
     * @param array|Traversable $attributes  attributeName => attributeValue string pairs
     * @param string            $innerXML
     *
     * @return string
     */
    public static function wrapTag($name, $attributes, $innerXML)
    {
        if (!strlen($innerXML)) {
            return XMLBuild::startTag($name, $attributes, true);
        }

        return
            XMLBuild::startTag($name, $attributes)
            . "\n"
            . XMLBuild::indentLines($innerXML)
            . "</$name>";
    }

    /**
     * @param XMLReader $reader
     *
     * @return string
     */
    public static function readerNode(XMLReader $reader)
    {
        switch ($reader->nodeType) {
            case XMLREADER::NONE:
                return '%(0)%';

            case XMLReader::ELEMENT:
                return XMLBuild::startTag($reader->name, new XMLAttributeIterator($reader));

            default:
                $node = new XMLReaderNode($reader);
                $nodeTypeName = $node->getNodeTypeName();
                $nodeType = $reader->nodeType;
                return sprintf('%%%s (%d)%%', $nodeTypeName, $nodeType);
        }
    }

    /**
     * @param array $matches
     *
     * @return string
     * @see attributeValue()
     */
    private static function numericEntitiesSingleByte($matches)
    {
        $buffer = str_split($matches[0]);
        foreach ($buffer as &$char) {
            $char = sprintf('&#%d;', ord($char));
        }

        return implode('', $buffer);
    }
}

/**
 * Class XMLAttributeIterator
 *
 * Iterator over all attributes of the current node (if any)
 */
class XMLAttributeIterator implements Iterator, Countable, ArrayAccess, XMLReaderAggregate
{
    private $reader;
    private $valid;
    private $array;

    public function __construct(XMLReader $reader)
    {
        $this->reader = $reader;
    }

    public function count()
    {
        return $this->reader->attributeCount;
    }

    public function current()
    {
        return $this->reader->value;
    }

    public function key()
    {
        return $this->reader->name;
    }

    public function next()
    {
        $this->valid = $this->reader->moveToNextAttribute();
        if (!$this->valid) {
            $this->reader->moveToElement();
        }
    }

    public function rewind()
    {
        $this->valid = $this->reader->moveToFirstAttribute();
    }

    public function valid()
    {
        return $this->valid;
    }

    public function getArrayCopy()
    {
        if ($this->array === null) {
            $this->array = iterator_to_array($this);
        }

        return $this->array;
    }

    public function getAttributeNames()
    {
        return array_keys($this->getArrayCopy());
    }

    public function offsetExists($offset)
    {
        $attributes = $this->getArrayCopy();

        return isset($attributes[$offset]);
    }

    public function offsetGet($offset)
    {
        $attributes = $this->getArrayCopy();

        return $attributes[$offset];
    }

    public function offsetSet($offset, $value)
    {
        throw new BadMethodCallException('XMLReader attributes are read-only');
    }

    public function offsetUnset($offset)
    {
        throw new BadMethodCallException('XMLReader attributes are read-only');
    }

    /**
     * @return XMLReader
     */
    public function getReader()
    {
        return $this->getReader();
    }
}

/**
 * Class XMLReaderIterator
 *
 * Iterate over all nodes of a reader
 */
class XMLReaderIterator implements Iterator, XMLReaderAggregate
{
    protected $reader;
    private $index;
    private $lastRead;
    private $elementStack;

    public function __construct(XMLReader $reader)
    {
        $this->reader = $reader;
    }

    public function getReader()
    {
        return $this->reader;
    }

    public function moveToNextElementByName($name = null)
    {
        while (self::moveToNextElement()) {
            if (!$name || $name === $this->reader->name) {
                break;
            }
            self::next();
        }
        ;

        return self::valid() ? self::current() : false;
    }

    public function moveToNextElement()
    {
        return $this->moveToNextByNodeType(XMLReader::ELEMENT);
    }

    /**
     * @param int $nodeType
     *
     * @return bool|\XMLReaderNode
     */
    public function moveToNextByNodeType($nodeType)
    {
        if (null === self::valid()) {
            self::rewind();
        }

        while ($this->valid()) {
            if ($this->reader->nodeType === $nodeType) {
                break;
            }
            self::next();
        }

        return self::valid() ? self::current() : false;
    }

    public function rewind()
    {
        // this iterator can not really rewind
        if ($this->reader->nodeType === XMLREADER::NONE) {
            self::next();
        } elseif ($this->lastRead === null) {
            $this->lastRead = true;
        }
        $this->index = 0;
    }

    public function valid()
    {
        return $this->lastRead;
    }

    public function current()
    {
        return new XMLReaderNode($this->reader);
    }

    public function key()
    {
        return $this->index;
    }

    public function next()
    {
        if ($this->lastRead = $this->reader->read() and $this->reader->nodeType === XMLReader::ELEMENT) {
            $depth                      = $this->reader->depth;
            $this->elementStack[$depth] = new XMLReaderElement($this->reader);
            if (count($this->elementStack) !== $depth + 1) {
                $this->elementStack = array_slice($this->elementStack, 0, $depth + 1);
            }
        }
        ;
        $this->index++;
    }

    /**
     * @return string
     * @since 0.0.19
     */
    public function getNodePath()
    {
        return '/' . implode('/', $this->elementStack);
    }

    /**
     * @return string
     * @since 0.0.19
     */
    public function getNodeTree()
    {
        $stack  = $this->elementStack;
        $buffer = '';
        /* @var $element XMLReaderElement */
        while ($element = array_pop($stack)) {
            $buffer = $element->getXMLElementAround($buffer);
        }

        return $buffer;
    }

}

/**
 * Class XMLReaderIteration
 *
 * Very basic XMLReader iteration
 */
class XMLReaderIteration implements Iterator
{
    /**
     * @var XMLReader
     */
    private $reader;

    /**
     * @var boolean
     */
    private $valid;

    /**
     * @var int
     */
    private $index;

    function __construct(XMLReader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * @return XMLReader
     */
    public function current()
    {
        return $this->reader;
    }

    public function next()
    {
        $this->index++;
        $this->valid = $this->reader->read();
    }

    public function key()
    {
        return $this->index;
    }

    public function valid()
    {
        return $this->valid;
    }

    public function rewind()
    {
        if ($this->reader->nodeType !== XMLReader::NONE) {
            throw new BadMethodCallException('Reader can not be rewound');
        }

        $this->index = 0;
        $this->valid = $this->reader->read();
    }
}


/**
 * Class DOMReadingIteration
 */
class DOMReadingIteration extends IteratorIterator
{
    private $rootNode;

    private $reader;

    /**
     * @var array|DOMNode[]
     */
    private $stack;

    /**
     * @var int
     */
    private $depth;

    /**
     * @var int
     */
    private $lastDepth;

    /**
     * @var DOMNode
     */
    private $node;

    /**
     * @var DOMNode
     */
    private $lastNode;

    public function __construct(DOMNode $node, XMLReader $reader)
    {
        $this->rootNode = $node;
        $this->reader   = $reader;
        parent::__construct(new XMLReaderIteration($reader));
    }

    /**
     * The element by marked by type XMLReader::END_ELEMENT
     * is empty (has no children) but not self-closing.
     *
     * @return bool
     */
    public function isEndElementOfEmptyElement()
    {
        return
            $this->reader->nodeType === XMLReader::END_ELEMENT
            && $this->lastDepth === $this->reader->depth
            && $this->lastNode instanceof DOMElement
            && !$this->reader->isEmptyElement;
    }

    public function rewind()
    {
        $this->stack = array($this->rootNode);
        parent::rewind();
        $this->build();
    }

    private function build()
    {
        if (!$this->valid()) {
            $this->depth      = NULL;
            $this->lastDetpth = NULL;
            $this->node       = NULL;
            $this->lastNode   = NULL;
            $this->stack      = NULL;
            return;
        }

        $depth = $this->reader->depth;

        switch ($this->reader->nodeType) {
            case XMLReader::ELEMENT:
                $parent = $this->stack[$depth];
                $prefix = $this->reader->prefix;
                /* @var $node DOMElement */
                if ($prefix) {
                    $uri = $parent->lookupNamespaceURI($prefix) ?: $this->nsUriSelfLookup($prefix);
                    if ($uri === NULL) {
                        trigger_error(sprintf('Unable to lookup NS URI for element prefix "%s"', $prefix));
                    }
                    /* @var $doc DOMDocument */
                    $doc  = ($parent->ownerDocument?:$parent);
                    $node = $doc->createElementNS($uri, $this->reader->name);
                    $node = $parent->appendChild($node);
                } else {
                    $node = $parent->appendChild(new DOMElement($this->reader->name));
                }
                $this->stack[$depth + 1] = $node;
                if ($this->reader->moveToFirstAttribute()) {
                    $nsUris = array();
                    do {
                        if ($this->reader->prefix === 'xmlns') {
                            $nsUris[$this->reader->localName] = $this->reader->value;
                        }
                    } while ($this->reader->moveToNextAttribute());

                    $this->reader->moveToFirstAttribute();
                    do {
                        $prefix = $this->reader->prefix;
                        if ($prefix === 'xmlns') {
                            $node->setAttributeNS('http://www.w3.org/2000/xmlns/', $this->reader->name, $this->reader->value);
                        } elseif ($prefix) {
                            $uri = $parent->lookupNamespaceUri($prefix) ?: @$nsUris[$prefix];
                            if ($uri === NULL) {
                                trigger_error(sprintf('Unable to lookup NS URI for attribute prefix "%s"', $prefix));
                            }
                            $node->setAttributeNS($uri, $this->reader->name, $this->reader->value);
                        } else {
                            $node->appendChild(new DOMAttr($this->reader->name, $this->reader->value));
                        }
                    } while ($this->reader->moveToNextAttribute());
                }
                break;

            case XMLReader::END_ELEMENT:
                $node = NULL;
                break;

            case XMLReader::COMMENT:
                $node = $this->stack[$depth]->appendChild(new DOMComment($this->reader->value));
                break;

            case XMLReader::SIGNIFICANT_WHITESPACE:
            case XMLReader::TEXT:
            case XMLReader::WHITESPACE:
                $node = $this->stack[$depth]->appendChild(new DOMText($this->reader->value));
                break;

            case XMLReader::PI:
                $node = $this->stack[$depth]->appendChild(new DOMProcessingInstruction($this->reader->name, $this->reader->value));
                break;

            default:
                $node    = NULL;
                $message = sprintf('Unhandeled XMLReader node type %s', XMLReaderNode::dump($this->reader, TRUE));
                trigger_error($message);
        }

        $this->depth = $depth;
        $this->node  = $node;
    }

    private function nsUriSelfLookup($prefix) {
        $uri = NULL;

        if ($this->reader->moveToFirstAttribute()) {
            do {
                if ($this->reader->prefix === 'xmlns' && $this->reader->localName === $prefix) {
                    $uri = $this->reader->value;
                    break;
                }
            } while ($this->reader->moveToNextAttribute());
            $this->reader->moveToElement();
        }

        return $uri;
    }

    public function next()
    {
        parent::next();
        $this->lastDepth = $this->depth;
        $this->lastNode  = $this->node;
        $this->build();
    }

    /**
     * @return \DOMNode
     */
    public function getLastNode()
    {
        return $this->lastNode;
    }
}

/**
 * Class XMLReaderNode
 */
class XMLReaderNode implements XMLReaderAggregate
{
    public $name;
    private $reader;
    private $nodeType;
    private $nodeTypeString;
    private $string;
    private $attributes;
    private $simpleXML;

    // TODO check which example used string and check if it can be removed (must been one of the earlier ones)

    public function __construct(XMLReader $reader, $string = null)
    {
        $this->reader         = $reader;
        $this->nodeType       = $reader->nodeType;
        $this->nodeTypeString = $this->getNodeTypeName();
        $this->name           = $this->reader->name;
        $this->string         = $string;
    }

    public function __toString()
    {
        // TODO CLEAN $reader->readString()? / value?
        return $this->string ? $this->string : $this->reader->value;
    }

    /**
     * @return SimpleXMLElement
     */
    public function asSimpleXML()
    {
        if (null === $this->simpleXML) {
            $this->simpleXML = new SimpleXMLElement($this->readOuterXml());
        }

        return $this->simpleXML;
    }

    /**
     * @return XMLAttributeIterator|XMLReaderNode[]
     */
    public function getAttributes()
    {
        if (null === $this->attributes) {
            $this->attributes = new XMLAttributeIterator($this->reader);
        }

        return $this->attributes;
    }

    /**
     * @param string $name    attribute name
     * @param string $default (optional) if the attribute with $name does not exists, the value to return
     *
     * @return null|string value of the attribute, if attribute with $name does not exists null (by $default)
     */
    public function getAttribute($name, $default = null)
    {
        $value = $this->reader->getAttribute($name);

        return null !== $value ? $value : $default;
    }

    /**
     * @param string $name           (optional) element name, null or '*' stand for each element
     * @param bool   $descendantAxis descend into children of children and so on?
     *
     * @return XMLChildElementIterator|XMLReaderNode[]
     */
    public function getChildElements($name = null, $descendantAxis = false)
    {
        return new XMLChildElementIterator($this->reader, $name, $descendantAxis);
    }

    /**
     * @return XMLChildIterator
     */
    public function getChildren()
    {
        return new XMLChildIterator($this->reader);
    }

    public function getName()
    {
        return $this->name;
    }

    public function getReader()
    {
        return $this->reader;
    }

    /**
     * Decorated method
     *
     * @throws BadMethodCallException
     * @return string
     */
    public function readOuterXml()
    {
        // Compat libxml 20620 (2.6.20) or later - LIBXML_VERSION  / LIBXML_DOTTED_VERSION
        if (method_exists($this->reader, 'readOuterXml')) {
            return $this->reader->readOuterXml();
        }

        if (0 === $this->reader->nodeType) {
            return '';
        }

        if (false === $node = $this->reader->expand()) {
            throw new BadMethodCallException('Unable to expand node.');
        }

        $dom               = new DomDocument();
        $dom->formatOutput = true;

        $docNode   = $dom->importNode($node, true);
        $childNode = $dom->appendChild($docNode);

        return $dom->saveXML($childNode);
    }

    /**
     * Decorated method
     *
     * @throws BadMethodCallException
     * @return string
     */
    public function readString()
    {
        // Compat libxml 20620 (2.6.20) or later - LIBXML_VERSION  / LIBXML_DOTTED_VERSION
        if (method_exists($this->reader, 'readString')) {
            return $this->reader->readString();
        }

        if (0 === $this->reader->nodeType) {
            return '';
        }

        if (false === $node = $this->reader->expand()) {
            throw new BadMethodCallException('Unable to expand node.');
        }

        return $node->textContent;
    }

    /**
     * Return Nodetype as human readable string (constant name)
     *
     * @param null $nodeType
     *
     * @return string
     */
    public function getNodeTypeName($nodeType = null)
    {
        $strings = array(
            XMLReader::NONE                   => 'NONE',
            XMLReader::ELEMENT                => 'ELEMENT',
            XMLReader::ATTRIBUTE              => 'ATTRIBUTE',
            XMLREADER::TEXT                   => 'TEXT',
            XMLREADER::CDATA                  => 'CDATA',
            XMLReader::ENTITY_REF             => 'ENTITIY_REF',
            XMLReader::ENTITY                 => 'ENTITY',
            XMLReader::PI                     => 'PI',
            XMLReader::COMMENT                => 'COMMENT',
            XMLReader::DOC                    => 'DOC',
            XMLReader::DOC_TYPE               => 'DOC_TYPE',
            XMLReader::DOC_FRAGMENT           => 'DOC_FRAGMENT',
            XMLReader::NOTATION               => 'NOTATION',
            XMLReader::WHITESPACE             => 'WHITESPACE',
            XMLReader::SIGNIFICANT_WHITESPACE => 'SIGNIFICANT_WHITESPACE',
            XMLReader::END_ELEMENT            => 'END_ELEMENT',
            XMLReader::END_ENTITY             => 'END_ENTITY',
            XMLReader::XML_DECLARATION        => 'XML_DECLARATION',
        );

        if (null === $nodeType) {
            $nodeType = $this->nodeType;
        }

        return $strings[$nodeType];
    }

    /**
     * decorate method calls
     */
    public function __call($name, $args)
    {
        return call_user_func_array(array($this->reader, $name), $args);
    }

    /**
     * decorate property get
     */
    public function __get($name)
    {
        return $this->reader->$name;
    }

    /**
     * debug utility method
     *
     * @param XMLReader $reader
     * @param bool $return (optional) prints by default but can return string
     * @return string
     */
    public static function dump(XMLReader $reader, $return = FALSE)
    {
        $node = new self($reader);

        $nodeType = $reader->nodeType;
        $nodeName = $node->getNodeTypeName();

        $isEmptyElement = $reader->nodeType !== XMLReader::NONE && $reader->isEmptyElement;

        $extra = '';

        if ($reader->nodeType === XMLReader::ELEMENT) {
            $extra = '<' . $reader->name . '> ';
        }

        if ($reader->nodeType === XMLReader::END_ELEMENT) {
            $extra = '</' . $reader->name . '> ';
        }

        if ($reader->nodeType === XMLReader::TEXT || $reader->nodeType === XMLReader::WHITESPACE || $reader->nodeType === XMLReader::SIGNIFICANT_WHITESPACE) {
            $str = $reader->readString();
            $len = strlen($str);
            if ($len > 20) {
                $str = substr($str, 0, 17) . '...';
            }
            $str   = strtr($str, ["\n" => '\n']);
            $extra = sprintf('(%d) "%s" ', strlen($str), $str);
        }

        $label = sprintf("(#%d) %s %s", $nodeType, $nodeName, $extra);

        if ($return) {
            return $label;
        }

        printf("%s%s(isEmptyElement: %s)\n", str_repeat('  ', $reader->depth), $label, $isEmptyElement ? 'Yes' : 'No');
    }
}

/**
 * Class XMLReaderElement
 *
 * This node is used in the elementStack
 *
 * @since 0.0.19
 */
class XMLReaderElement extends XMLReaderNode
{
    private $name_;
    private $attributes_;

    public function __construct(XMLReader $reader)
    {
        parent::__construct($reader);
        $this->initializeFrom($reader);
    }

    public function getXMLElementAround($innerXML = '')
    {
        return XMLBuild::wrapTag($this->name_, $this->attributes_, $innerXML);
    }

    public function getAttributes()
    {
        return $this->attributes_;
    }

    public function getAttribute($name, $default = null)
    {
        return isset($this->attributes_[$name])
            ? $this->attributes_[$name] : $default;
    }

    public function __toString()
    {
        return $this->name_;
    }

    private function initializeFrom(XMLReader $reader)
    {
        if ($reader->nodeType !== XMLReader::ELEMENT) {
            $node = new XMLReaderNode($reader);
            throw new RuntimeException(sprintf(
                'Reader must be at an XMLReader::ELEMENT, is XMLReader::%s given.',
                $node->getNodeTypeName()
            ));
        }
        $this->name_       = $reader->name;
        $this->attributes_ = parent::getAttributes()->getArrayCopy();
    }
}

/**
 * Class XMLChildIterator
 *
 * Iterate over child-nodes of the current XMLReader node
 */
class XMLChildIterator extends XMLReaderIterator
{
    private $stopDepth;

    public function __construct(XMLReader $reader)
    {
        parent::__construct($reader);
        $this->stopDepth = $reader->depth;
    }

    public function rewind()
    {
        parent::next();
        parent::rewind();
    }

    public function valid()
    {
        $parent = parent::valid();

        return $parent and $this->reader->depth > $this->stopDepth;
    }
}

/**
 * Class XMLElementIterator
 *
 * Iterate over XMLReader element nodes
 */
class XMLElementIterator extends XMLReaderIterator
{
    private $index;
    private $name;
    private $didRewind;

    /**
     * @param XMLReader   $reader
     * @param null|string $name element name, leave empty or use '*' for all elements
     */
    public function __construct(XMLReader $reader, $name = null)
    {
        parent::__construct($reader);
        $this->name = '*' === $name ? null : $name;
    }

    public function rewind()
    {
        parent::rewind();
        parent::moveToNextElementByName($this->name);
        $this->didRewind = true;
        $this->index     = 0;

        return $this;
    }

    /**
     * @return XMLReaderNode
     */
    public function current()
    {
        $this->didRewind || self::rewind();

        return new XMLReaderNode($this->reader);
    }

    public function key()
    {
        return $this->index;
    }

    public function next()
    {
        if (parent::valid()) {
            $this->index++;
        }
        parent::next();
        parent::moveToNextElementByName($this->name);
    }

    /**
     * read string from the first element (if not yet rewinded), otherwise from the current element as
     * long as valid. null if not valid.
     *
     * TODO test if it can be removed due to the fact of decorating ->current() via __call() and __get()
     *      port third example (one before the asSimeplXML / toArray() variant)
     *      one reason it can't be removed is the rewind when starting.
     *      -> most likely this whole function can be put into __toString()
     *
     * @return null|string
     */
    public function readString()
    {
        isset($this->index) || $this->rewind();

        return $this->current()->readString();
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $array = array();

        /* @var $element XMLReaderNode */
        foreach ($this as $element) {
            if ($this->reader->hasValue) {
                $string = $this->reader->value;
            } else {
                $string = $element->readString();
            }

            if ($this->name) {
                $array[] = $string;
            } else {
                $array[$element->name] = $string;
            }
        }

        return $array;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->readString();
    }

    /**
     * decorate method calls
     */
    public function __call($name, $args)
    {
        return call_user_func_array(array($this->current(), $name), $args);
    }

    /**
     * decorate property get
     */
    public function __get($name)
    {
        return $this->current()->$name;
    }
}

/**
 * Class XMLChildElementIterator
 *
 * Iterate over child element nodes of the current XMLReader node
 */
class XMLChildElementIterator extends XMLElementIterator
{
    private $stopDepth;
    private $descendTree;

    /**
     * @inheritdoc
     * @param bool $descendantAxis traverse deep into?
     */
    public function __construct(XMLReader $reader, $name = null, $descendantAxis = false)
    {
        parent::__construct($reader, $name);
        $this->stopDepth   = $reader->depth;
        $this->descendTree = $descendantAxis;
    }

    /**
     * @return XMLChildElementIterator
     */
    public function rewind()
    {
        parent::next();
        parent::rewind();

        return $this;
    }

    public function next()
    {
        do {
            parent::next();
            if ($this->descendTree || $this->reader->depth === $this->stopDepth + 1) {
                break;
            }
        } while ($this->valid());
    }

    public function valid()
    {
        return parent::valid() && $this->reader->depth > $this->stopDepth;
    }
}

/**
 * Class XMLReaderFilterBase
 *
 * @since 0.0.21
 */
abstract class XMLReaderFilterBase extends FilterIterator implements XMLReaderAggregate
{

    public function __construct(XMLReaderIterator $elements) {
        parent::__construct($elements);
    }

    /**
     * @return XMLReader
     */
    public function getReader()
    {
        return $this->getInnerIterator()->getHeader();
    }
}

/**
 * Class XMLTypeFilter
 *
 * FilterIterator to only accept one or more specific XMLReader nodeTypes
 *
 */
class XMLNodeTypeFilter extends XMLReaderFilterBase
{
    private $allowed;
    private $reader;
    private $invert;

    /**
     * @param XMLReaderIterator $iterator
     * @param int|int[] $nodeType one or more type constants  <http://php.net/class.xmlreader>
     *      XMLReader::NONE            XMLReader::ELEMENT         XMLReader::ATTRIBUTE       XMLReader::TEXT
     *      XMLReader::CDATA           XMLReader::ENTITY_REF      XMLReader::ENTITY          XMLReader::PI
     *      XMLReader::COMMENT         XMLReader::DOC             XMLReader::DOC_TYPE        XMLReader::DOC_FRAGMENT
     *      XMLReader::NOTATION        XMLReader::WHITESPACE      XMLReader::SIGNIFICANT_WHITESPACE
     *      XMLReader::END_ELEMENT     XMLReader::END_ENTITY      XMLReader::XML_DECLARATION
     * @param bool $invert
     */
    public function __construct(XMLReaderIterator $iterator, $nodeType, $invert = false)
    {
        parent::__construct($iterator);
        $this->allowed = (array) $nodeType;
        $this->reader  = $iterator->getReader();
        $this->invert  = $invert;
    }

    public function accept()
    {
        $result = in_array($this->reader->nodeType, $this->allowed);

        return $this->invert ? !$result : $result;
    }
}

/**
 * Class XMLAttributeFilterBase
 */
abstract class XMLAttributeFilterBase extends XMLReaderFilterBase
{
    private $attr;

    /**
     * @param XMLElementIterator $elements
     * @param string $attr name of the attribute, '*' for every attribute
     */
    public function __construct(XMLElementIterator $elements, $attr)
    {
        parent::__construct($elements);
        $this->attr = $attr;
    }

    protected function getAttributeValues()
    {
        /* @var $node XMLReaderNode */
        $node = parent::current();
        if ('*' === $this->attr) {
            $attrs = $node->getAttributes()->getArrayCopy();
        } else {
            $attrs = (array) $node->getAttribute($this->attr);
        }

        return $attrs;
    }
}

/**
 * Class XMLAttributeFilter
 *
 * FilterIterator for attribute value(s)
 */
class XMLAttributeFilter extends XMLAttributeFilterBase
{
    private $compare;
    private $invert;

    /**
     * @param XMLElementIterator $elements
     * @param string $attr name of the attribute, '*' for every attribute
     * @param string|array $compare value(s) to compare against
     * @param bool $invert
     */
    public function __construct(XMLElementIterator $elements, $attr, $compare, $invert = false)
    {

        parent::__construct($elements, $attr);

        $this->compare = (array) $compare;
        $this->invert  = (bool) $invert;
    }

    public function accept()
    {
        $result = $this->search($this->getAttributeValues(), $this->compare);

        return $this->invert ? !$result : $result;
    }

    private function search($values, $compares)
    {
        foreach ($compares as $compare) {
            if (in_array($compare, $values)) {
                return true;
            }
        }

        return false;
    }
}

/**
 * Class XMLAttributePreg
 *
 * PCRE regular expression based filter for elements with a certain attribute value
 */
class XMLAttributePreg extends XMLAttributeFilterBase
{
    private $pattern;
    private $invert;

    /**
     * @param XMLElementIterator $elements
     * @param string $attr name of the attribute, '*' for every attribute
     * @param string $pattern pcre based regex pattern for the attribute value
     * @param bool $invert
     * @throws InvalidArgumentException
     */
    public function __construct(XMLElementIterator $elements, $attr, $pattern, $invert = false)
    {
        parent::__construct($elements, $attr);

        if (false === preg_match("$pattern", '')) {
            throw new InvalidArgumentException("Invalid pcre pattern '$pattern'.");
        }
        $this->pattern = $pattern;
        $this->invert  = (bool) $invert;
    }

    public function accept()
    {
        return (bool) preg_grep($this->pattern, $this->getAttributeValues(), $this->invert ? PREG_GREP_INVERT : 0);
    }
}

/**
 * Class XMLElementXpathFilter
 *
 * Filter an XMLReaderIterator with an Xpath expression
 *
 * @since 0.0.19
 */
class XMLElementXpathFilter extends XMLReaderFilterBase
{
    private $expression;

    public function __construct(XMLElementIterator $iterator, $expression)
    {
        parent::__construct($iterator);
        $this->expression = $expression;
    }

    public function accept()
    {
        $buffer = $this->getInnerIterator()->getNodeTree();
        $result = simplexml_load_string($buffer)->xpath($this->expression);
        $count  = count($result);
        if ($count !== 1) {
            return false;
        }

        return !($result[0]->children()->count());
    }
}
