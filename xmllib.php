<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * A general library for XML related things.
 *
 * @package extralibs
 * @category third-party libs
 * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
 * @date 2008/03/03
 * @version phase1
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
defined('MOODLE_INTERNAL') || die();

if (!function_exists('recordstoxml')) {
    /**
     * recordstoxml : takes an array of records and transforms it to xml structure.
     * keys are converted. When an associative array is found, keys are used as element id.
     * Note that any HTML tagged content makes the value being escaped in a <![CDATA[ ]]> section
     * for XSLT escaping.
     *
     * When an object is found, properties of the object are converted into inner elements
     * @param array $array
     * @param string $baseelement the base element
     * @param string $subrecords if there are subrecords elements to include as child tree, they are available as an XML formated string
     * @param boolean $withheader if true, adds a standard XML document header
     * @param boolean $translate if true, all fields value are passed thru the get_string() translating call
     * @param string $stylesheet may mention a stylesheet call to the document header, if header is enabled
     * @return a string XML formatted, with or without XML heading entity
     */
    function recordstoxml(&$array, $baseelement, $subrecords = '', $withheader = true, $translate = false, $stylesheet = '') {
        global $strings;

        $baseelement = strtolower($baseelement); // Calibrates the base name.
        $xml = ($withheader) ? "<?xml version=\"1.0\"  encoding=\"UTF-8\" ?>\n{$stylesheet}\n<rootnode>\n" : '';
        $xml .= "<{$baseelement}s>\n";

        $ix = 1;
        if (!empty($array)) {
            foreach ($array as $key => $element) {
                if (is_object($element)) {
                    $fields = get_object_vars($element);
                    $xml .= "\t<{$baseelement} id=\"$key\">\n";
                            $xml .= "\t\t<ix>$ix</ix>\n";
                    foreach ($fields as $fieldname => $fieldvalue) {
                        $translation = $fieldvalue;
                        if ($translate && !preg_match('/\[\['.preg_quote($fieldvalue).'\]\]/', $translation)) {
                            $fieldvalue = $translation;
                        }
                        if (preg_match("/<|>/", $fieldvalue)) {
                            $xml .= "\t\t<{$fieldname}><![CDATA[ ".str_replace("& ", "&amp; ", $fieldvalue)." ]]></{$fieldname}>\n";
                        } else {
                            $xml .= "\t\t<{$fieldname}>".str_replace("& ", "&amp; ", $fieldvalue)."</{$fieldname}>\n";
                        }
                    }
                    $xml .= "\t</{$baseelement}>\n";
                }
                $ix++;
            }
            $subrecords = "\t" . str_replace("\n", "\n\t", $subrecords); // Give one indent more.
            $subrecords = substr($subrecords, 0, strlen($subrecords) - 1); // Chops last \t.
            $xml .= $subrecords;
        }
        $xml .= "</{$baseelement}s>\n";
        $xml .= ($withheader) ? "</rootnode>\n" : '';
        return $xml;
    }
}
