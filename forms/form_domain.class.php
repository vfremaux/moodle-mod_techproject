<?php

    /**
    * Moodle - Modular Object-Oriented Dynamic Learning Environment
    *          http://moodle.org
    * Copyright (C) 1999 onwards Martin Dougiamas  http://dougiamas.com
    *
    * This program is free software: you can redistribute it and/or modify
    * it under the terms of the GNU General Public License as published by
    * the Free Software Foundation, either version 2 of the License, or
    * (at your option) any later version.
    *
    * This program is distributed in the hope that it will be useful,
    * but WITHOUT ANY WARRANTY; without even the implied warranty of
    * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    * GNU General Public License for more details.
    *
    * You should have received a copy of the GNU General Public License
    * along with this program.  If not, see <http://www.gnu.org/licenses/>.
    *
    * Defines form to add a new project
    *
    * @package    block-prf-catalogue
    * @subpackage classes
    * @author     Emeline Daude <daude.emeline@gmail.com>
    * @reviewer   Valery Fremaux <valery.fremaux@club-internet.fr>
    * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
    * @copyright  (C) 1999 onwards Martin Dougiamas  http://dougiamas.com
    *
    */

    require_once($CFG->libdir.'/formslib.php');

    class Domain_Form extends moodleform {
    	private $domainvalue;
    	private $domain;
    	
    	function __construct($domain, $domainvalue, $action = ''){
    		$this->domain = $domain;
    		$this->domainvalue = $domainvalue;
        	parent::__construct($action);
        }
    	
    	function definition() {
    		global $CFG;
    		
    		// Setting variables
    		$mform =& $this->_form;

    		$mform->addElement('hidden', 'view', 'domains_'.$this->domain);
    		if (isset($this->domainvalue->id)){
        		$mform->addElement('hidden', 'domainid', $this->domainvalue->id);
        	}
    		
    		// Adding title and description
        	$mform->addElement('html', print_heading(get_string('newvalueformfor', 'techproject', get_string($this->domain, 'techproject'))));
    		
    		// Adding fieldset
    		$codeattributes = 'size="10" maxlength="10"';
    		$attributes = 'size="70" maxlength="128"';
    		
    		$areaattributes = 'cols="30" rows="5"';

    		$mform->addElement('text', 'code', get_string('code', 'techproject'), $codeattributes);
    		$mform->setDefault('code', $this->domainvalue->code);
    		
    		$mform->addElement('text', 'label', get_string('label', 'techproject'), $attributes);
    		$mform->setDefault('label', $this->domainvalue->label);

    		$mform->addElement('textarea', 'description', get_string('description'), $areaattributes);
    		$mform->setDefault('description', $this->domainvalue->description);
    				
    		$mform->addRule('code', null, 'required');
    		$mform->addRule('label', null, 'required');
    		
    		// Adding submit and reset button
            $buttonarray = array();
        	$buttonarray[] = &$mform->createElement('submit', 'go_submit', get_string('update'));
        	$buttonarray[] = &$mform->createElement('cancel', 'go_cancel', get_string('cancel'));
            
            $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
            $mform->closeHeaderBefore('buttonar');		
    	}
    }
?>