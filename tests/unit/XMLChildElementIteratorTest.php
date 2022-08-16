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
 * @license AGPL-3.0-or-later <https://spdx.org/licenses/AGPL-3.0-or-later>
 */

/**
 * Class XMLChildElementIteratorTest
 */
class XMLChildElementIteratorTest extends PHPUnit_Framework_TestCase
{
    public function testIteration()
    {
        $reader = new XMLReaderStub('<!-- comment --><root><child></child></root>');

        $it = new XMLChildElementIterator($reader);

        $this->assertEquals(false, $it->valid());
        $this->assertSame(false, $it->valid());

        $it->rewind();
        $this->assertEquals(true, $it->valid());
        $this->assertEquals('child', $it->current()->getName());

        $it->next();
        $this->assertEquals(false, $it->valid());

        $reader = new XMLReaderStub('<root><none></none><one><child></child></one><none></none></root>');
        $base = new XMLElementIterator($reader);
        $base->rewind();
        $root = $base->current();
        $this->assertEquals('root', $root->getName());
        $children = $root->getChildElements();
        $this->assertEquals('root', $reader->name);
        $children->rewind();
        $this->assertEquals('none', $reader->name);
        $children->next();
        $this->assertEquals('one', $reader->name);
        $childChildren = new XMLChildElementIterator($reader);
        $this->assertEquals('child', $childChildren->current()->getName());
        $childChildren->next();
        $this->assertEquals(false, $childChildren->valid());
        $this->assertEquals('none', $reader->name);
        $childChildren->next();
        $this->assertEquals('none', $reader->name);

        $this->assertEquals(true, $children->valid());
        $children->next();
        $this->assertEquals(false, $children->valid());


        // children w/o descendants
        $reader->rewind();
        $expected = array('none', 'one', 'none');
        $root = $base->current();
        $this->assertEquals('root', $root->getName());

        $count = 0;
        foreach($root->getChildElements() as $index => $child) {
            $this->assertSame($count++, $index);
            $this->assertEquals($expected[$index], $reader->name);
        }
        $this->assertEquals(count($expected), $count);

        // children w/ descendants
        $reader->rewind();
        $expected = array('none', 'one', 'child', 'none');
        $root = $base->current();
        $this->assertEquals('root', $root->getName());

        $count = 0;
        foreach($root->getChildElements(null, true) as $index => $child) {
            $this->assertSame($count++, $index);
            $this->assertEquals($expected[$index], $reader->name);
        }
        $this->assertEquals(count($expected), $count);
    }

    /**
     * The XMLChildElementIterator must not leave the parent element when iterating over named children
     *
     * There is a flaw that named children did indeed traverse (so are effectively also grand-children which could be
     * considered a feature and not a bug) however also go over all siblings and so on the parent element level.
     *
     * Test at least that going into siblings is prevented. The cause was using the parent next() method while giving
     * as well the parent XMLElementIterator the name and only in the XMLChildElementIterator do the check.
     */
    public function testInnerNamedChildren()
    {
        // first the good test, this must not break
        $reader = new XMLReader();
        $this->assertTrue($reader->open(__DIR__ . '/../../examples/data/posts.xml'), 'fixture document can be opened successfully');
        // move to first _parent_ element
        $iter = new XMLReaderIterator($reader);
        $this->assertNotSame(false, $node = $iter->moveToNextElementByName('user'));
        $children = $node->getChildElements();
        $children->rewind();
        $this->assertTrue($children->valid());
        $array = iterator_to_array($children, false);
        $this->assertCount(1, $array, 'count is correctly 1 here as there is one children');

        // now the regression test which we're trying to break (so it's a fix)
        $reader = new XMLReader();
        $this->assertTrue($reader->open(__DIR__ . '/../../examples/data/posts.xml'), 'fixture document can be opened successfully');
        $users = new XMLElementIterator($reader, 'user');
        $users->rewind();
        $user = $users->current();
        $posts = $user->getChildElements('post');
        $array = iterator_to_array($posts, false);
        $this->assertCount(1, $array, 'this is the regression, count should be 1 but it is 16');
    }

    public function testDescendantChildren()
    {
        $reader = new XMLReader();
        $this->assertTrue($reader->open(__DIR__ . '/../../examples/data/movies.xml'), 'fixture document can be opened successfully');
        $children = new XMLChildElementIterator($reader, null, true);
        $basePath = '/movies/movie';
        foreach ($children as $index => $child) {
            $nodePath = $children->getNodePath();
            $this->assertStringStartsWith("$basePath/", "$nodePath/", "base-path check on child #$index");
        }
        $this->assertSame(13, $index, 'movies.xml document element has 14 child elements');
        $array = iterator_to_array($children, false);
        $this->assertSame(array(), $array, 'all children have been consumed by foreach');
    }

    /**
     * Test child-iterator without calling 'skipNextRead' method
     */
    public function testIterationOnChildrenStopBeforeReadingNextElement()
    {
        $reader = XMLReader::open(__DIR__ . '/../../examples/data/sample-rss-091.xml');
        $this->assertTrue(!!$reader, 'fixture document can be opened successfully');
        $items = new XMLElementIterator($reader, 'item');
        foreach ($items as $idx => $item) {
            $children = $items->getChildElements();
            $children->rewind();
            foreach (array('title', 'link', 'description') as $tagName) {
                $this->assertTrue($children->valid());
                $this->assertSame($tagName, $children->name);
                $children->next();
            }
            $this->assertFalse($children->valid());
        }
        $this->assertSame(6, $idx, 'sample-rss-091.xml element has 7 item elements');
        $this->assertEmpty(\iterator_to_array($items), 'all children have been consumed by foreach');
    }
}
