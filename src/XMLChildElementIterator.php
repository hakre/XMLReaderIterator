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
 * Class XMLChildElementIterator
 *
 * Iterate over child element nodes of the current XMLReader node
 */
class XMLChildElementIterator extends XMLElementIterator
{
    /**
     * @var null|int
     */
    private $stopDepth;

    /**
     * @var bool
     */
    private $descendTree;

    /**
     * @var bool
     */
    private $didRewind;

    /**
     * @var int
     */
    private $index;

    /**
     * @var string|null
     */
    private $name;

    /**
     * @inheritdoc
     *
     * @param bool $descendantAxis traverse children of children
     */
    public function __construct(XMLReader $reader, $name = null, $descendantAxis = false)
    {
        parent::__construct($reader);
        $this->name = $name;
        $this->descendTree = $descendantAxis;
    }

    /**
     * @throws UnexpectedValueException
     * @return void
     */
    public function rewind()
    {
        // this iterator can not really rewind. instead it places itself onto the
        // first child element - if any.
        if ($this->didRewind) {
            return;
        }

        if ($this->reader->nodeType === XMLReader::NONE) {
            !$this->moveToNextByNodeType(XMLReader::ELEMENT);
        }

        if ($this->stopDepth === null) {
            $this->stopDepth = $this->reader->depth;
        }

        // move to first child element - if any
        $result = $this->nextChildElementByName($this->name);

        $this->index = $result ? 0 : null;
        $this->didRewind = true;
    }

    public function next()
    {
        if (!$this->valid()) {
            return;
        }

        $this->index++;
        $this->nextChildElementByName($this->name);
    }

    public function valid()
    {
        if (!$this->didRewind) {
            return false;
        }

        $depth = $this->reader->depth;
        if ($depth <= $this->stopDepth) {
            return false;
        }
        if (!$this->descendTree && $depth !== $this->stopDepth + 1) {
            return false;
        }
        if ($this->name === null || $this->reader->name === $this->name) {
            return $this->reader->nodeType === XMLReader::ELEMENT; // always true here if reader in sync with $this
        }

        return false;
    }

    /**
     * @return XMLReaderNode|null
     */
    public function current()
    {
        $this->didRewind || $this->rewind();
        return $this->valid() ? parent::current() : null;
    }

    /**
     * @return int
     */
    public function key()
    {
        return $this->index;
    }

    /**
     * move to next child element by name
     *
     * @param string|null $name
     * @return bool
     */
    private function nextChildElementByName($name = null)
    {
        while ($next = $this->nextElement()) {
            $depth = $this->reader->depth;
            if ($depth <= $this->stopDepth) {
                return false;
            }
            if (!$this->descendTree && $depth !== $this->stopDepth + 1) {
                continue;
            }
            if ($name === null || $this->reader->name === $name) {
                break;
            }
        }

        return (bool)$next;
    }

    /**
     * @return bool
     */
    private function nextElement()
    {
        while ($this->reader->read()) {
            if (XMLReader::ELEMENT !== $this->reader->nodeType) {
                continue;
            }
            $this->touchElementStack();
            return true;
        }
        return false;
    }
}
