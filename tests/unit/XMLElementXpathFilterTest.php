<?php
/*
 * This file is part of the XMLReaderIterator package.
 *
 * Copyright (C) 2012, 2022 hakre <http://hakre.wordpress.com>
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
 * Class XMLElementIteratorTest
 */
class XMLElementXpathFilterTest extends PHPUnit_Framework_TestCase
{
    public function testReproduceOriginalReport12()
    {
        $reader = new XMLReader();
        $this->assertTrue($reader->open(__DIR__ . '/../fixtures/report-12-default.xml'), 'fixture document can be opened successfully');
        $iterator = new XMLElementIterator($reader);
        $list = new XMLElementXpathFilter($iterator, '/response/result/log/logs/entry');
        $count = 0;
        foreach ($list as $entry) {
            $simplexml = $entry->getSimpleXMLElement();
            $this->assertNotNull($simplexml, 'reported as null (we could never reproduce, but changes since the report)');
            $this->assertInstanceOf('SimpleXMLElement', $simplexml);
            $this->assertLessThan(3, $count++, 'no more than three xpath expression result elements');
        }
        $this->assertSame(3, $count, 'xpath expression gives exactly three elements');
    }

    public function testRegressionNestedReport12()
    {
        $reader = new XMLReader();
        $this->assertTrue($reader->open(__DIR__ . '/../fixtures/report-12-nested.xml'), 'fixture document can be opened successfully');
        $iterator = new XMLElementIterator($reader);
        $list = new XMLElementXpathFilter($iterator, '//entry');

        $list->rewind();

        $this->assertTrue($list->valid());
        $item = $list->current();
        $this->assertSame('entry', $item->getName(), $list->key());
        $this->assertNull($item->getAttribute('id'));

        $list->next();

        $this->assertTrue($list->valid(), 'xpath filter exits prematurely (report 12 regression)');
        $this->assertSame('entry', $item->getName(), $list->key());
        $this->assertSame('1', $item->getAttribute('id'));

        $list->next();

        $this->assertTrue($list->valid(), $list->key());
        $this->assertSame('entry', $item->getName(), $list->key());
        $this->assertSame('2', $item->getAttribute('id'), $list->key());

        $list->next();

        $this->assertTrue($list->valid(), $list->key());
        $this->assertSame('entry', $item->getName(), $list->key());
        $this->assertSame('3', $item->getAttribute('id'), $list->key());

        $list->next();

        $this->assertFalse($list->valid(), 'this is the end');
        $this->assertNull($list->key(), 'key() null identity at end');
        $this->assertNull($list->current(), 'current() null identity at end');
    }
}
