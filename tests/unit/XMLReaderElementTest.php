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

class XMLReaderElementTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var XMLReader
     */
    protected $reader;

    protected function setUp()
    {
        $reader = new XMLReader();
        $reader->open('data://text/plain,' .urlencode('<root><child pos="first">node value</child><child pos="first"/></root>'));
        $this->reader = $reader;
    }

    /** @test */
    public function elementCreation() {
        $reader = $this->reader;
        $reader->next();
        $element = new XMLReaderElement($reader);
        $this->assertSame($element->getNodeTypeString(), $element->getNodeTypeString(XMLReader::ELEMENT));
        $this->assertSame($element->name, 'root');
    }

    /** @test */
    public function readerAttributeHandling() {
        $reader = new XMLReader();
        $reader->open('data://text/plain,' .urlencode("<root pos=\"first\" plue=\"a&#13;&#10;b&#32;  c\t&#9;d\">node value</root>"));
        $reader->next();
        $this->assertSame("first", $reader->getAttribute('pos'));
        $this->assertSame("a\r\nb   c \td", $reader->getAttribute('plue'), 'entity handling');
        $element = new XMLReaderElement($reader);
        $xml = $element->getXMLElementOpen();
        $this->assertSame("<root pos=\"first\" plue=\"a&#13;&#10;b   c &#9;d\">", $xml, 'XML generation');
    }
}
