<?php
/*
 * This file is part of the XMLReaderIterator package.
 *
 * Copyright (C) 2014 hakre <http://hakre.wordpress.com>
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
 * Externalized XMLReader XML Traversal
 *
 * Class XMLReaderNavigator
 */
class XMLReaderNavigator implements XMLReaderAggregate
{
    const PROP_NODE_TYPE  = 'nodeType';
    const PROP_LOCAL_NAME = 'localName';
    const PROP_NAME       = 'name';
    const PROP_PREFIX     = 'prefix';

    /**
     * @var XMLReader
     */
    private $reader;

    function __construct(XMLReader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * @return XMLReader
     */
    public function getReader()
    {
        return $this->reader;
    }

    /**
     * move to next element node
     *
     * @return bool success
     */
    public function nextElement()
    {
        return $this->nextPropSame(self::PROP_NODE_TYPE, XMLReader::ELEMENT);
    }

    /**
     * move to next element node by name
     *
     * @param string $name
     *
     * @return bool success
     */
    public function nextElementByName($name)
    {
        return $this->nextTypePropSame(XMLReader::ELEMENT, self::PROP_NAME, $name);
    }

    /**
     * move to next element node by localName
     *
     * @param string $localName
     *
     * @return bool success
     */
    public function nextElementByLocalName($localName)
    {
        return $this->nextTypePropSame(XMLReader::ELEMENT, self::PROP_LOCAL_NAME, $localName);
    }

    /**
     * @param int $type - one of the XMLReader::ELEMENT and alike
     * @param string $property of XMLReader to compare
     * @param mixed $value which must be same compared to $property
     *
     * @return bool
     */
    private function nextTypePropSame($type, $property, $value)
    {
        $reader = $this->reader;

        while (
            $result = $reader->read()
            and (
                $reader->nodeType !== $type
                or $reader->$property !== $value
            )
        ) ;

        return $result;
    }

    /**
     * @param string $property of XMLReader to compare
     * @param mixed $value which must be same compared to $property
     *
     * @return bool
     */
    private function nextPropSame($property, $value)
    {
        $reader = $this->reader;
        while (
            $result = $reader->read()
            and $reader->$property !== $value
        ) ;

        return $result;
    }
}
