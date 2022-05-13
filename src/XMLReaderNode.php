<?php
/*
 * This file is part of the XMLReaderIterator package.
 *
 * Copyright (C) 2012, 2013, 2015 hakre <http://hakre.wordpress.com>
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
 * @license AGPL-3.0-or-later <https://spdx.org/licenses/AGPL-3.0-or-later>
 */

/**
 * Class XMLReaderNode
 *
 * @property string name inherited from XMLReader via IteratorIterator decoration
 */
class XMLReaderNode implements XMLReaderAggregate
{
    /** @var XMLReader */
    private $reader;

    /** @var int */
    private $nodeType;

    /** @var string */
    private $name;

    /** @var string */
    private $localName;

    /**
     * cache for expansion into SimpleXMLElement
     *
     * @var null|SimpleXMLElement
     * @see asSimpleXML
     */
    private $simpleXML;

    /**
     * cache for XMLAttributeIterator
     *
     * @var null|XMLAttributeIterator
     * @see getAttributes
     */
    private $attributes;

    /** @var string  */
    private $string;

    public function __construct(XMLReader $reader)
    {
        $this->reader   = $reader;
        $this->nodeType = $reader->nodeType;
        $this->name     = $reader->name;
    }

    public function __toString()
    {
        if (null === $this->string) {
            $this->string = $this->readString();
        }

        return $this->string;
    }

    /**
     * SimpleXMLElement for XMLReader::ELEMENT
     *
     * @param string $className SimpleXMLElement class name of the simplexml element
     * @return SimpleXMLElement|null in case the current node can not be converted into a SimpleXMLElement
     * @since 0.1.4
     */
    public function getSimpleXMLElement($className = null)
    {
        if (null === $this->simpleXML) {
            if ($this->reader->nodeType !== XMLReader::ELEMENT) {
                return null;
            }

            $this->simpleXML = simplexml_import_dom($this->expand(), $className);
        }

        if (is_string($className) && !($this->simpleXML instanceof $className)) {
            $this->simpleXML = simplexml_import_dom(dom_import_simplexml($this->simpleXML), $className);
        }

        return $this->simpleXML;
    }

    /**
     * @deprecated since v0.1.4, use {@see getSimpleXMLElement()} instead
     * @return null|SimpleXMLElement
     */
    public function asSimpleXML()
    {
        trigger_error('Deprecated ' . __METHOD__ . '() - use getSimpleXMLElement() in the future', E_USER_NOTICE);

        return $this->getSimpleXMLElement();
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
     * @return XMLChildIterator|XMLReaderNode[]
     */
    public function getChildren()
    {
        return new XMLChildIterator($this->reader);
    }

    /**
     * @return string name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string local name
     */
    public function getLocalName()
    {
        return $this->localName;
    }

    public function getReader()
    {
        return $this->reader;
    }

    /**
     * Decorated method
     *
     * @throws BadMethodCallException in case XMLReader can not expand the node
     * @return string
     */
    public function readOuterXml()
    {
        // Compatibility libxml 20620 (2.6.20) or later - LIBXML_VERSION  / LIBXML_DOTTED_VERSION
        if (method_exists($this->reader, 'readOuterXml')) {
            return $this->reader->readOuterXml();
        }

        if (0 === $this->reader->nodeType) {
            return '';
        }

        $doc = new DOMDocument();

        $doc->preserveWhiteSpace = false;
        $doc->formatOutput       = true;

        $node = $this->expand($doc);

        return $doc->saveXML($node);
    }

    /**
     * XMLReader expand node and import it into a DOMNode with a DOMDocument
     *
     * This is for example useful for DOMDocument::saveXML() {@see readOuterXml}
     * or getting a SimpleXMLElement out of it {@see getSimpleXMLElement}
     *
     * @param DOMNode $baseNode
     * @throws BadMethodCallException
     * @return DOMNode
     */
    public function expand(DOMNode $baseNode = null)
    {
        if (null === $baseNode) {
            $baseNode = new DomDocument();
        }

        if ($baseNode instanceof DOMDocument) {
            $doc = $baseNode;
        } else {
            $doc = $baseNode->ownerDocument;
            if (null === $doc) {
                throw new InvalidArgumentException('BaseNode has no OwnerDocument.');
            }
        }

        if (false === $node = $this->reader->expand($baseNode)) {
            throw new BadMethodCallException('Unable to expand node.');
        }

        if ($node->ownerDocument !== $doc) {
            $node = $doc->importNode($node, true);
        }

        return $node;
    }

    /**
     * Decorated method
     *
     * @throws BadMethodCallException
     * @return string
     */
    public function readString()
    {
        // Compatibility libxml 20620 (2.6.20) or later - LIBXML_VERSION  / LIBXML_DOTTED_VERSION
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
     * Return node-type as human-readable string (constant name)
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
            XMLReader::ENTITY_REF             => 'ENTITY_REF',
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
     *
     * @param string $name
     * @param array $args
     *
     * @return mixed
     */
    public function __call($name, $args)
    {
        return call_user_func_array(array($this->reader, $name), $args);
    }

    /**
     * decorate property get
     *
     * @param string $name
     *
     * @return string
     */
    public function __get($name)
    {
        return $this->reader->$name;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set($name, $value)
    {
        throw new BadMethodCallException('XMLReader properties are read-only: ' . $name);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->reader->$name);
    }

    /**
     * debug utility method
     *
     * @param XMLReader $reader
     * @param bool $return (optional) prints by default but can return string
     * @return string|void
     */
    public static function dump(XMLReader $reader, $return = FALSE)
    {
        $node = new self($reader);

        $nodeType = $reader->nodeType;
        $nodeName = $node->getNodeTypeName();

        $extra = '';

        if ($reader->nodeType === XMLReader::ELEMENT) {
            $extra = ' <' . $reader->name . '> ';
            $extra .= sprintf("(isEmptyElement: %s)", $reader->isEmptyElement ? 'Yes' : 'No');
        }

        if ($reader->nodeType === XMLReader::END_ELEMENT) {
            $extra = ' </' . $reader->name . '>';
        }

        if ($reader->nodeType === XMLReader::ATTRIBUTE) {
            $extra = sprintf(' %s = %s', $reader->name, XMLBuild::dumpString($reader->value));
        }


        if ($reader->nodeType === XMLReader::CDATA
            || $reader->nodeType === XMLReader::TEXT
            || $reader->nodeType === XMLReader::WHITESPACE
            || $reader->nodeType === XMLReader::SIGNIFICANT_WHITESPACE
        ) {
            $extra = sprintf( ' %s', XMLBuild::dumpString($reader->value));
        }

        $label = sprintf("(#%d) %s%s", $nodeType, $nodeName, $extra);

        if ($return) {
            return $label;
        }

        printf("%s%s\n", str_repeat('  ', $reader->depth), $label);
    }
}
