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
 * Class XMLBuildTest
 */
class XMLBuildTest extends PHPUnit_Framework_TestCase
{
    public function provideDisplayStringExpectations()
    {
        return array(
            array('hello', 'hello', null),
            array('hello', 'hello', 5),
            array('h...', 'hello', 4),
            array('\000\000\000\000\000', "\0\0\0\0\0", 5),
            array('\000...', "\0\0\0\0\0", 4),
            array('hello\\\\world', 'hello\world', null),
        );
    }

    /**
     * @dataProvider provideDisplayStringExpectations
     */
    public function testDisplayString($expected, $str, $maxLen)
    {
        $actual = XMLBuild::displayString($str, $maxLen);
        $this->assertSame($expected, $actual);
    }

    public function provideDumpStringExpectations()
    {
        return array(
            array('(5) "hello"', 'hello', null),
            array('(5) "hello"', 'hello', 5),
            array('(5) "h..."', 'hello', 4),
        );
    }

    /**
     * @dataProvider provideDumpStringExpectations
     */
    public function testDumpString($expected, $str, $maxLen)
    {
        $actual = XMLBuild::dumpString($str, $maxLen);
        $this->assertSame($expected, $actual);
    }
}
