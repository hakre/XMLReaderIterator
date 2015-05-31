<?php
/*
 * This file is part of the XMLReaderIterator package.
 *
 * Copyright (C) 2015 hakre <http://hakre.wordpress.com>
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
 * Class XMLReaderTestCase
 *
 * Default testcase to extend from
 */
class XMLReaderTestCase extends PHPUnit_Framework_TestCase
{
    /**
     * helper method to create data-providers
     *
     * @param array $result
     * @param       $path
     *
     * @return array of arrays with one entry of each filename as string
     */
    protected function addXmlFiles(array $result, $path)
    {
        return $this->addFiles($result, $path, '~\.xml$~');
    }

    /**
     * helper method to create data-providers
     *
     * @param array $result
     * @param       $path
     *
     * @return array of arrays with one entry of each filename as string
     */
    protected function addFiles(array $result, $path, $pattern)
    {
        /** @var FilesystemIterator|SplFileInfo[] $dir */
        $dir = new FilesystemIterator($path);
        foreach ($dir as $file) {
            if (!$file->isFile()) {
                continue;
            }
            if (!preg_match($pattern, $file->getBasename())) {
                continue;
            }

            $result[] = array((string) $file);
        }

        return $result;
    }
}
