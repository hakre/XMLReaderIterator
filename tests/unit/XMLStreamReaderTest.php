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
 * Class XMLReaderTest
 */
class XMLStreamReaderTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @dataProvider provideFile
     */
    function readStreamTest($xmlFile)
    {
        stream_wrapper_register('xmlseq', 'XMLSequenceStream');
        $path = "xmlseq://" . $xmlFile;

        $this->xmlFileContents = array();
        while (XMLSequenceStream::notAtEndOfSequence($path)) {
            $reader = new XMLReader();
            $reader->open($path, 'UTF-8', LIBXML_COMPACT | LIBXML_PARSEHUGE);
            /** @var XMLElementIterator|XMLReaderNode $elements */
            $elements = new XMLElementIterator($reader);
            $this->xmlFileContents[] = new SimpleXMLElement(
                $elements->current()->readOuterXml()
            );
        }

        XMLSequenceStream::clean();
        stream_wrapper_unregister('xmlseq');
    }

    public function provideFile()
    {
        return array(
            array(
                __DIR__ . '/../fixtures/stream-xml-with-encoding.xml'
            ),
            array(
                __DIR__ . '/../fixtures/stream-xml-without-encoding.xml'
            )
        );
    }
}
