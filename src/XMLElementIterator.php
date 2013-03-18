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
