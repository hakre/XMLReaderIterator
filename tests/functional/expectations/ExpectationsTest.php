<?php
/*
 * This file is part of the XMLReaderIterator package.
 *
 * Copyright (C) 2012, 2015 hakre <http://hakre.wordpress.com>
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
 * all these tests are the expected behavior of the original XMLReader as it ships with PHP
 *
 * Class ExpectationsTest
 */
class ExpectationsTest extends \PHPUnit_Framework_TestCase
{
    public function provideLocalNameAndName()
    {
        $reader = new XMLReaderStub('<!-- -->
            <root xmlns:name="ns-uri:name">
                <!-- default namespace -->
                <bar id="1"/>

                <!-- namespace by prefix -->
                <name:bar id="1"/>

                <!-- namespace on it\'s own -->
                <bar id="1" xmlns="ns-uri:xmlns:bar" />
            </root>
        ');

        return [
            [$reader, true, 'bar', 'bar', '', ''],
            [$reader, true, 'bar', 'name:bar', 'name', 'ns-uri:name'],
            [$reader, true, 'bar', 'bar', '', 'ns-uri:xmlns:bar'],
        ];
    }

    /**
     * @test
     * @dataProvider provideLocalNameAndName
     * @param XMLReader $reader
     * @param bool $didRead
     * @param string $localName
     * @param string $name
     * @param string $prefix
     * @param string $namespaceUri
     */
    public function localNameAndName(XMLReader $reader, $didRead, $localName, $name, $prefix, $namespaceUri)
    {
        $read     = $this->_readerNextNonDocumentElement($reader);
        $superset = [$read, $reader->localName, $reader->name, $reader->prefix, $reader->namespaceURI];
        // TODO $this->_xPrintArray($superset);

        $this->assertEquals($didRead, $read);
        $this->assertEquals($localName, $reader->localName, 'local-name');
        $this->assertEquals($name, $reader->name, 'name (with prefix)');
        $this->assertEquals($prefix, $reader->prefix, 'prefix');
        $this->assertEquals($namespaceUri, $reader->namespaceURI, 'namespace-uri');

    }

    /**
     * move to next non-document-element element
     *
     * @param XMLReader $reader
     * @return bool
     */
    private function _readerNextNonDocumentElement(XMLReader $reader)
    {
        while ($read = $reader->read())
        {
            if (
                $reader->nodeType !== 1
                || $reader->depth === 0
            )
            {
                continue;
            }
            break;
        }

        return $read;
    }

    /**
     * @param array $array
     */
    private function _xPrintArray(array $array)
    {
        echo "[\$reader";
        foreach ($array as $index => $value)
        {
            printf(", %s", var_export($value, true));
        }
        echo "],\n";
    }
}
