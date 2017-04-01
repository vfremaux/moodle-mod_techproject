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

class Specification_Form extends moodleform {

    protected $mode;
    protected $project;
    protected $current;
    protected $descriptionoptions;

    public function __construct($action, &$project, $mode, $specid) {
        global $DB;

        $this->project = $project;
        $this->mode = $mode;
        if ($specid) {
            $this->current = $DB->get_record('techproject_specification', array('id' => $specid));
        } else {
            $this->current = new StdClass;
            $this->current->id = 0;
        }
        parent::__construct($action);
    }

    public function definition() {
        global $COURSE, $DB;

        $mform = $this->_form;

        $currentgroup = 0 + groups_get_course_group($COURSE);

        $modcontext = context_module::instance($this->project->cmid);

        $maxfiles = 99;                // TODO: add some setting.
        $maxbytes = $COURSE->maxbytes; // TODO: add some setting.
        $this->descriptionoptions = array('trusttext' => true,
                                          'subdirs' => false,
                                          'maxfiles' => $maxfiles,
                                          'maxbytes' => $maxbytes,
                                          'context' => $modcontext);        

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'fatherid');
        $mform->setType('fatherid', PARAM_INT);
        $mform->addElement('hidden', 'specid');
        $mform->setType('specid', PARAM_INT);
        $mform->addElement('hidden', 'work');
        $mform->setType('work', PARAM_TEXT);
        $mform->setDefault('work', $this->mode);

        $mform->addElement('text', 'abstract', get_string('spectitle', 'techproject'), array('size' => "100%"));
        $mform->setType('abstract', PARAM_CLEANHTML);

        $severities = techproject_get_options('severity', $this->project->id);
        $severityoptions = array();
        foreach ($severities as $aseverity) {
            $severityoptions[$aseverity->code] = '['. $aseverity->code . '] '.$aseverity->label;
        }
        $mform->addElement('select', 'severity', get_string('severity', 'techproject'), $severityoptions);
        $mform->addHelpButton('severity', 'severity', 'techproject');

        $priorities = techproject_get_options('priority', $this->project->id);
        $priorityoptions = array();
        foreach ($priorities as $apriority) {
            $priorityoptions[$apriority->code] = '['. $apriority->code . '] ' . $apriority->label;
        }
        $mform->addElement('select', 'priority', get_string('priority', 'techproject'), $priorityoptions);
        $mform->addHelpButton('priority', 'priority', 'techproject');

        $complexities = techproject_get_options('complexity', $this->project->id);
        $complexityoptions = array();
        foreach ($complexities as $acomplexity) {
            $complexityoptions[$acomplexity->code] = '['. $acomplexity->code . '] ' . $acomplexity->label;
        }
        $mform->addElement('select', 'complexity', get_string('complexity', 'techproject'), $complexityoptions);
        $mform->addHelpButton('complexity', 'complexity', 'techproject');

        $label = get_string('description', 'techproject');
        $mform->addElement('editor', 'description_editor', $label, null, $this->descriptionoptions);

        if ($this->project->projectusesrequs && $this->mode == 'update') {
            $requirements = techproject_get_tree_options('techproject_requirement', $this->project->id, $currentgroup);
            $select = "specid = ?";
            $params = array($this->current->id);
            $selection = $DB->get_records_select_menu('techproject_spec_to_req', $select, $params, 'reqid, specid');
            $reqs = array();
            if (!empty($requirements)) {
                foreach ($requirements as $arequirement) {
                    $shortabstract = shorten_text(format_string($arequirement->abstract), 90);
                    $reqs[$arequirement->id] = $arequirement->ordering .' - '.$shortabstract;
                }
            }
            $select = &$mform->addElement('select', 'spectoreq', get_string('spectoreq', 'techproject'), $reqs, array('size' => 8));
            $select->setMultiple(true);
            $mform->addHelpButton('spectoreq', 'spec_to_reqs', 'techproject');
        }

        $this->add_action_buttons(true);
    }

    public function set_data($defaults) {

        $context = context_module::instance($this->project->cmid);

        $draftideditor = file_get_submitted_draft_itemid('description_editor');
        $currenttext = file_prepare_draft_area($draftideditor, $context->id, 'mod_techproject', 'description_editor', $defaults->id,
                                               array('subdirs' => true), $defaults->description);
        $defaults = file_prepare_standard_editor($defaults, 'description', $this->descriptionoptions, $context, 'mod_techproject',
                                                 'specificationdescription', $defaults->id);
        $defaults->description = array('text' => $currenttext,
                                       'format' => $defaults->descriptionformat,
                                       'itemid' => $draftideditor);

        parent::set_data($defaults);
    }
}
