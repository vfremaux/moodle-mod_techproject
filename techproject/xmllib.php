<?php

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

if (!function_exists('recordstoxml')){
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
    function recordstoxml(&$array, $baseelement, $subrecords = '', $withheader=true, $translate=false, $stylesheet = ''){
        global $strings;
        
        $baseelement = strtolower($baseelement); // calibrates the base name
        $xml = ($withheader) ? "<?xml version=\"1.0\"  encoding=\"UTF-8\" ?>\n{$stylesheet}\n<rootnode>\n" : '' ;
        $xml .= "<{$baseelement}s>\n";
    
        $ix = 1;
        if (!empty($array)){
            foreach($array as $key => $element){
                if (is_object($element)){
                    $fields = get_object_vars($element);
                    $xml .= "\t<{$baseelement} id=\"$key\">\n";
                            $xml .= "\t\t<ix>$ix</ix>\n";
                    foreach($fields as $fieldname => $fieldvalue){
                    	$translation = $fieldvalue;
                    	if (is_string($fieldvalue)){
                        	// $translation = get_string($fieldvalue, $translate);
                        }
                        if ($translate && !preg_match('/\[\['.preg_quote($fieldvalue).'\]\]/', $translation)){
                            $fieldvalue = $translation;
                        }
                        if (preg_match("/<|>/", $fieldvalue)){
                            $xml .= "\t\t<{$fieldname}><![CDATA[ ".str_replace("& ", "&amp; ", $fieldvalue)." ]]></{$fieldname}>\n";
                        }
                        else{
                            $xml .= "\t\t<{$fieldname}>".str_replace("& ", "&amp; ", $fieldvalue)."</{$fieldname}>\n";
                        }
                    }
                    $xml .= "\t</{$baseelement}>\n";
                }
                $ix++;
            }
            $subrecords = "\t" . str_replace("\n", "\n\t", $subrecords); // give one indent more
            $subrecords = substr($subrecords, 0, strlen($subrecords) -1); // chops last \t 
            $xml .= $subrecords;
        }
        $xml .= "</{$baseelement}s>\n";
        $xml .= ($withheader) ? "</rootnode>\n" : '' ;
        return $xml;
    }
}

?>