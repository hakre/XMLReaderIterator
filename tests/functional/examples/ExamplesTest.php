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

class ExamplesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param $file
     * @dataProvider exampleFiles
     * @test
     */
    public function runPhpFile($file) {
        $name = basename($file, '.php');

        try {
            $this->addToAssertionCount(1);
            ob_start();
            {
                $cwd = getcwd();
                chdir(dirname($file));
                {
                    $this->saveInclude($file);
                }
                chdir($cwd);
                unset($cwd);
            }
            $buffer = ob_get_clean();
        } catch(Exception $e) {
            $this->fail(sprintf('Example %s did throw an exception %s with message %s.', $name, get_class($e), $e->getMessage()));
        }

        $expectedFile = $this->getExpectedFile($file);
        $this->assertStringEqualsFile($expectedFile, $buffer);
    }

    private function saveInclude() {
        include func_get_arg(0);
    }

    private function getExpectedFile($forFile) {
        $name = basename($forFile);
        $name = strtr($name, '.', '_');
        $file = __DIR__ . '/Expectations/' . $name . '.out';
        return $file;
    }

    // TODO remove not needed any longer.
    private function getExpectedBuffer($forFile)
    {
        $file = $this->getExpectedFile($forFile);
        if (!is_readable($file)) {
            throw new RuntimeException(sprintf('Not a readable file for %s (%s)', $name, $forFile));
        }

        $buffer  = file_get_contents($file);
        if ($buffer === false) {
            throw new RuntimeException(sprintf('Failed to aquire expected buffer for %s (%s)', $name, $forFile));
        }
        return $buffer;
    }


    public function exampleFiles() {
        $parameters = array();

        $examplePath = $this->getExamplesPath();
        $dir = new DirectoryIterator($examplePath);
        foreach($dir as $file) {
            /* @var $file DirectoryIterator */
            if (!$file->isFile()) continue;
            if (!preg_match('~^(?!xmlreader-iterators)[^.]+\.php$~', $file->getBasename())) continue;
            $parameters[] = array($file->getRealPath());
        }

        return $parameters;
    }

    private function getExamplesPath() {
        return __DIR__ . '/../../../examples';
    }
}
