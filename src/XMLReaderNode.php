<?php
/*
 * XMLReader Iterators
 * Copyright (C) 2012, 2013  hakre
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
 */

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

}
