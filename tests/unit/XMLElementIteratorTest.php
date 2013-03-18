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

class XMLElementIteratorTest extends PHPUnit_Framework_TestCase
{
    /** @test */
    public function constructAndIterate()
    {
        $reader = $this->createReader();

        $it = new XMLElementIterator($reader);

        $this->assertSame('xml', $it->current()->getName());
        $it->next();
        $this->assertSame('node1', $it->current()->getName());
        $it->next();
        $this->assertSame('info1', $it->current()->getName());
    }

    /** @test */
    public function getChildren()
    {
        $reader = $this->createReader();

        $it = new XMLElementIterator($reader);

        $xml = $it->current();
        $this->assertSame('xml', $xml->name); // ensure this is the root node
        $it->next();

        $array = $it->toArray();
        $this->assertSame(7, count($array));
        $this->assertSame("\n                test\n            ", $array['node4']);
    }

    private function createReader()
    {
        return new XMLReaderStub('<!-- -->
        <xml>
            <node1>
                <info1/>
            </node1>
            <node2 id="0">
                <info2>
                    <pool2/>
                </info2>
            </node2>
            <node3/>
            <node4>
                test
            </node4>
        </xml>');
    }
}
