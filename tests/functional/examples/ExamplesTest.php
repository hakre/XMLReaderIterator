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

class ExamplesTest extends XMLReaderTestCase
{
    private $cwd;

    protected function setUp()
    {
        $this->cwd = getcwd();
        parent::setUp();
    }

    protected function tearDown()
    {
        chdir($this->cwd);
        parent::tearDown();
    }


    /**
     * @param $file
     *
     * @throws Exception
     * @throws PHPUnit_Framework_SkippedTest
     *
     * @dataProvider provideExampleFiles
     */
    public function testRunPhpFile($file) {
        $name = basename($file, '.php');

        $buffer = null;
        try {
            $this->addToAssertionCount(1);
            ob_start();
            {
                chdir(dirname($file));
                $this->saveInclude($file);
            }
            $buffer = ob_get_clean();
        } catch(PHPUnit_Framework_SkippedTest $e) {
            null === $buffer && ob_end_clean();
            throw $e;
        } catch (PHPUnit_Framework_Error_Warning $e) {
            null === $buffer && ob_end_clean();
            $message = $e->getMessage();
            if ($message !== 'fopen(): Unable to find the wrapper "compress.bzip2" - did you forget to enable it when you configured PHP?') {
                throw $e;
            }
            $this->markTestSkipped('Wrapper "compress.bzip2" not found.');
        } catch(Exception $e) {
            null === $buffer && ob_end_clean();
            $this->fail(sprintf("Example %s did throw an exception %s with message \"%s\".\n\n%s", $name, get_class($e), $e->getMessage(), $e->getTraceAsString()));
        }

        $expectedFile = $this->getExpectedFile($file);
        if (!file_exists($expectedFile)) {
            file_put_contents($expectedFile, array("# GENERATED ON ASSUMED FIRST RUN\n", $buffer));
        }
        $expected = file_get_contents($expectedFile);
        if ($expected[0] === '~') {
            $this->assertNotSame(false, preg_match($expected, ""), 'validate the regex pattern for validity first');
            $this->assertRegExp($expected, $buffer, $name);
        } else {
            $this->assertEquals($expected, $buffer, $name);
        }
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

    /**
     * @return array
     *
     * @see runPhpFile
     */
    public function provideExampleFiles()
    {
        $path = __DIR__ . '/../../../examples';

        return $this->addFiles(array(), $path, '~^(?!xmlreader-iterators)[^.]+\.php$~');
    }
}
