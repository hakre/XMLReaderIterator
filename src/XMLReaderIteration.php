<?php
/*
 * This file is part of the XMLReaderIterator package.
 *
 * Copyright (C) 2014, 2015 hakre <http://hakre.wordpress.com>
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
 * Class XMLReaderIteration
 *
 * Very basic XMLReader iteration
 *
 * @since 0.1.0
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

    /**
     * @var bool
     */
    private $skipNextRead;

    public function __construct(XMLReader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * skip the next read on next "next()"
     *
     * this is useful of the reader has moved to the next node already inside a
     * foreach iteration and the next "next()" would move the reader one too
     * far.
     *
     * @see next
     */
    public function skipNextRead()
    {
        $this->skipNextRead = true;
    }

    /**
     * @return XMLReader
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->reader;
    }

    #[\ReturnTypeWillChange]
    public function next()
    {
        $this->index++;

        if ($this->skipNextRead) {
            $this->skipNextRead = false;
            $this->valid        = $this->reader->nodeType;
        } else {
            $this->valid = $this->reader->read();
        }
    }

    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->index;
    }

    #[\ReturnTypeWillChange]
    public function valid()
    {
        return $this->valid;
    }

    #[\ReturnTypeWillChange]
    public function rewind()
    {
        if ($this->reader->nodeType !== XMLReader::NONE) {
            throw new BadMethodCallException('Reader can not be rewound');
        }

        $this->index = 0;
        $this->valid = $this->reader->read();
    }
}
