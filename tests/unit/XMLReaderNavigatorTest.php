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

class XMLReaderNavigatorTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var XMLReaderNavigator
     */
    private $navigator;

    protected function setUp()
    {
        $this->navigator
            = new XMLReaderNavigator(new XMLReaderStub('<root xmlns:FOO="ns:FOO"><element/><FOO:bar/></root>'));
    }


    /**
     * @test
     */
    public function creation()
    {
        $navigator = new XMLReaderNavigator(new XMLReaderStub('<root/>'));
        $this->assertInstanceOf('XMLReaderNavigator', $navigator);
    }

    /**
     *
     */
    public function getReader()
    {
        $this->assertInstanceOf('XMLReader', $this->navigator->getReader());
    }

    /**
     * @test
     */
    public function nextElement()
    {
        $navigator = $this->navigator;
        $reader    = $navigator->getReader();

        $result = $navigator->nextElement();
        $this->assertSame(true, $result);
        $this->assertSame(XMLReader::ELEMENT, $reader->nodeType);
        $this->assertSame('root', $reader->name);

        $navigator->nextElement();
        $this->assertSame(XMLReader::ELEMENT, $reader->nodeType);
        $this->assertSame('element', $reader->name);
    }

    /**
     * @test
     */
    public function nextElementByName()
    {
        $navigator = $this->navigator;

        $name   = 'element';
        $result = $navigator->nextElementByName($name);
        $this->assertSame(true, $result, 'read result');
        $this->assertSame($name, $navigator->getReader()->name, 'name (on aggregated reader)');
    }

    /**
     * @test
     */
    public function nextElementByLocalName()
    {
        $navigator = $this->navigator;

        $name   = 'bar';
        $result = $navigator->nextElementByLocalName($name);
        $this->assertSame(true, $result);
        $this->assertSame($name, $navigator->getReader()->localName);
    }

}
