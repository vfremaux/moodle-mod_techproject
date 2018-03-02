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
 * @package mod_techproject
 * @category mod
 * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
 * @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

class Domain_Form extends moodleform {

    protected $domainvalue;

    protected $domain;

    public function __construct($domain, $domainvalue, $action = '') {
        $this->domain = $domain;
        $this->domainvalue = $domainvalue;
        parent::__construct($action);
    }

    public function definition() {
        global $CFG, $OUTPUT;

        // Setting variables.
        $mform =& $this->_form;

        $mform->addElement('hidden', 'view', 'domains_'.$this->domain);
        $mform->setType('view', PARAM_TEXT);

        if (isset($this->domainvalue->id)) {
            $mform->addElement('hidden', 'domainid', $this->domainvalue->id);
            $mform->setType('domainid', PARAM_INT);
        }

        // Adding title and description.
        $html = $OUTPUT->heading(get_string('newvalueformfor', 'techproject', get_string($this->domain, 'techproject')));
        $mform->addElement('html', $html);

        // Adding fieldset.
        $codeattributes = 'size="10" maxlength="10"';
        $attributes = 'size="70" maxlength="128"';
        $areaattributes = 'cols="30" rows="5"';

        $mform->addElement('text', 'code', get_string('code', 'techproject'), $codeattributes);
        $mform->setType('code', PARAM_TEXT);
        $mform->setDefault('code', $this->domainvalue->code);

        $mform->addElement('text', 'label', get_string('label', 'techproject'), $attributes);
        $mform->setType('label', PARAM_CLEANHTML);
        $mform->setDefault('label', $this->domainvalue->label);

        $mform->addElement('textarea', 'description', get_string('description'), $areaattributes);
        $mform->setDefault('description', $this->domainvalue->description);
        $mform->addRule('code', null, 'required');
        $mform->addRule('label', null, 'required');

        // Adding submit and reset button.
        $this->add_action_buttons(true);
    }
}
