<?php
/*
 * This file is part of the XMLReaderIterator package.
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
 * @license AGPL-3.0-or-later <https://spdx.org/licenses/AGPL-3.0-or-later>
 */

/**
 * Class XMLReaderIterator
 *
 * Iterate over all nodes of a reader
 */
class XMLReaderIterator implements Iterator, XMLReaderAggregate
{
    /**
     * @var XMLReader
     */
    protected $reader;

    /**
     * @var int
     */
    private $index;

    /**
     * stores the result of the last XMLReader::read() operation.
     *
     * additionally, it's set to true if not initialized (null) on @see XMLReaderIterator::rewind()
     *
     * @var bool
     */
    private $lastRead;

    /**
     * @var bool
     */
    private $skipNextRead;

    /**
     * @var array
     */
    private $elementStack;

    public function __construct(XMLReader $reader)
    {
        $this->reader = $reader;
    }

    public function getReader()
    {
        return $this->reader;
    }

    /**
     * skip the next read on next "next()"
     *
     * compare {@see XMLReaderIteration::skipNextRead()}
     *
     * @see XMLReaderIterator::next()
     *
     */
    public function skipNextRead()
    {
        $this->skipNextRead = true;
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
        } elseif (self::valid()) {
            self::next();
        }

        while (self::valid()) {
            if ($this->reader->nodeType === $nodeType) {
                break;
            }
            self::next();
        }

        return self::valid() ? self::current() : false;
    }

    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->skipNextRead = false;

        // this iterator can not really rewind
        if ($this->reader->nodeType === XMLREADER::NONE) {
            self::next();
        } elseif ($this->lastRead === null) {
            $this->lastRead = true;
        }
        $this->index = 0;
    }

    /**
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function valid()
    {
        return $this->lastRead;
    }

    /**
     * @return XMLReaderNode
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->lastRead ? new XMLReaderNode($this->reader) : null;
    }

    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->index;
    }

    #[\ReturnTypeWillChange]
    public function next()
    {
        $this->index ++;

        if ($this->skipNextRead) {
            $this->skipNextRead = false;
            $this->lastRead = $this->reader->nodeType !== XMLReader::NONE;
        } elseif ($this->lastRead = $this->reader->read() and $this->reader->nodeType === XMLReader::ELEMENT) {
            $this->touchElementStack();
        }
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

    /**
     * touch the internal element-stack
     *
     * update the element-stack for the current reader node - which must be
     * of type XMLReader::ELEMENT otherwise undefined.
     *
     * @return void
     */
    protected function touchElementStack()
    {
        $depth = $this->reader->depth;
        $this->elementStack[$depth] = new XMLReaderElement($this->reader);
        if (count($this->elementStack) !== $depth + 1) {
            $this->elementStack = array_slice($this->elementStack, 0, $depth + 1);
        }
    }
}
