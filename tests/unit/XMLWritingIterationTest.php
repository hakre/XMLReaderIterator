<?php
/*
 * This file is part of the XMLReaderIterator package.
 *
 * Copyright (C) 2012, 2013, 2015, 2022 hakre <http://hakre.wordpress.com>
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

class XMLWritingIterationTest extends XMLReaderTestCase
{
    public function testUnsupportedDocumentNodeEmitsError()
    {
        $reader = new \XMLReader();
        $writer = new \XMLWriter();
        $iteration = new \XMLWritingIteration($writer, $reader);
        $reader->open(__DIR__ . '/../../examples/data/movies.xml');
        $this->assertSame(XMLReader::NONE, $reader->nodeType);
        $previous = error_get_last();
        @$iteration->write();
        $error = error_get_last();
        $this->assertNotSame($previous, $error);
        $this->assertSame(E_USER_WARNING, $error['type']);
        $this->assertSame('XMLWritingIteration::write(): Node-type not implemented: (#0) NONE', $error['message']);
    }
}
