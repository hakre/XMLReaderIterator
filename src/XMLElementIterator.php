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
     * @param XMLReader $reader
     * @param null|string $name element name, leave empty or use '*' for all elements
     */
    public function __construct(XMLReader $reader, $name = null)
    {
        parent::__construct($reader);
        $this->name = '*' === $name ? null : $name;
    }

    /**
     * @return XMLReaderNode
     */
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
        if (parent::valid()) {
            $this->index++;
        }
        parent::next();
        parent::moveToNextElementByName($this->name);
    }

    public function rewind()
    {
        parent::rewind();
        parent::moveToNextElementByName($this->name);
        $this->didRewind = true;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $array = array();
        /* @var $element XMLReaderNode */
        foreach ($this as $element) {
            if ($this->name) {
                $array[] = $element->readString();
            } else {
                $array[$element->name] = $element->readString();
            }
        }

        return $array;
    }

    /**
     * read string from the first element (if not yet rewinded), otherwise from the current element as
     * long as valid. null if not valid.
     *
     * @return null|string
     */
    public function readString()
    {
        if (!$this->didRewind) {
            $this->rewind();
        }
        if (!$this->valid()) {
            return null;
        }

        return $this->current()->readString();
    }

    public function __toString()
    {
        //TODO readString() compatibility
        $string = $this->readString();
        return $string;
    }
}
