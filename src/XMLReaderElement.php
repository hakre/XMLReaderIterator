<?php
/*
 * XMLReader Iterators
 * Copyright (C) 2012, 2013  hakre
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
 * Class XMLReaderElement
 *
 * This node is used in the elementStack
 *
 * @since 0.0.19
 */
class XMLReaderElement extends XMLReaderNode
{
    private $name_;
    private $attributes_;

    public function __construct(XMLReader $reader)
    {
        parent::__construct($reader);
        $this->initializeFrom($reader);
    }

    public function getXMLElementOpen($selfClose = false)
    {
        $buffer = '<' . $this->name_;

        foreach ($this->attributes_ as $name => $value) {
            // REC-xml/#AVNormalize - preserve
            // REC-xml/#sec-line-ends - preserve
            $value = preg_replace_callback('~\r\n|\r(?!\n)|\t~', array($this, 'numericEntitiesSingleByte'), $value);

            $buffer .= ' ' . $name . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8', false) . '"';
        }

        return $buffer . ($selfClose ? '/>' : '>');
    }

    private function numericEntitiesSingleByte($matches) {
        $buffer = str_split($matches[0]);
        foreach($buffer as &$char)
            $char = sprintf('&#%d;', ord($char));
        return implode('', $buffer);
    }

    public function getXMLElementClose()
    {
        return '</' . $this->name . '>';
    }

    public function getXMLElementAround($innerXML = '')
    {
        if (strlen($innerXML)) {
            $buffer = $this->getXMLElementOpen() . "\n";
            foreach (explode("\n", $innerXML) as $line) {
                $buffer .= '  ' . $line . "\n";
            }
            $buffer .= $this->getXMLElementClose();

            return $buffer;
        } else {
            return $this->getXMLElementOpen(true);
        }
    }

    public function getAttributes()
    {
        return $this->attributes_;
    }

    public function getAttribute($name, $default = null)
    {
        return isset($this->attributes_[$name])
            ? $this->attributes_[$name] : $default;
    }

    public function __toString()
    {
        return $this->name_;
    }

    private function initializeFrom(XMLReader $reader)
    {
        if ($reader->nodeType !== XMLReader::ELEMENT) {
            $node = new XMLReaderNode($reader);
            throw new RuntimeException(sprintf(
                'Reader must be at an XMLReader::ELEMENT, is XMLReader::%s given.',
                $node->getNodeTypeString()
            ));
        }
        $this->name_       = $reader->name;
        $this->attributes_ = parent::getAttributes()->getArrayCopy();
    }
}
