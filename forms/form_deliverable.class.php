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

class Deliverable_Form extends moodleform {

    protected $mode;

    protected $project;

    protected $current;

    public $editoroptions;

    public $attachmentoptions;

    public function __construct($action, $mode, &$project, $delivid) {
        global $DB;

        $this->mode = $mode;
        $this->project = $project;
        if ($delivid) {
            $this->current = $DB->get_record('techproject_deliverable', array('id' => $delivid));
        } else {
            $this->current = new StdClass;
            $this->current->id = 0;
        }
        parent::__construct($action);
    }

    public function definition() {
        global $COURSE, $DB;

        $mform = $this->_form;

        $modcontext = context_module::instance($this->project->cmid);

        $maxfiles = 99;                // TODO: add some setting.
        $maxbytes = $COURSE->maxbytes; // TODO: add some setting.
        $this->editoroptions = array('trusttext' => true,
                                          'subdirs' => false,
                                          'maxfiles' => $maxfiles,
                                          'maxbytes' => $maxbytes,
                                          'context' => $modcontext);
        $this->attachmentoptions = array('subdirs' => false, 'maxfiles' => $maxfiles, 'maxbytes' => $maxbytes);

        $currentgroup = 0 + groups_get_course_group($COURSE);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'fatherid');
        $mform->setType('fatherid', PARAM_INT);

        $mform->addElement('hidden', 'delivid');
        $mform->setType('delivid', PARAM_INT);

        $mform->addElement('hidden', 'work');
        $mform->setType('work', PARAM_TEXT);
        $mform->setDefault('work', $this->mode);

        $mform->addElement('text', 'abstract', get_string('delivtitle', 'techproject'), array('size' => "100%"));
        $mform->setType('abstract', PARAM_CLEANHTML);

        $statusses = techproject_get_options('delivstatus', $this->project->id);
        $deliverystatusses = array();
        foreach ($statusses as $astatus) {
            $deliverystatusses[$astatus->code] = '['. $astatus->code . '] ' . $astatus->label;
        }
        $mform->addElement('select', 'status', get_string('status', 'techproject'), $deliverystatusses);
        $mform->addHelpButton('status', 'deliv_status', 'techproject');

        if ($this->mode == 'update') {

            $sql = "
               SELECT
                  id,
                  abstract,
                  ordering
               FROM
                  {techproject_milestone}
               WHERE
                  projectid = {$this->project->id} AND
                  groupid = {$currentgroup}
               ORDER BY
                  ordering
            ";
            $milestones = $DB->get_records_sql($sql);
            $milestonesoptions = array();
            foreach ($milestones as $amilestone) {
                $milestonesoptions[$amilestone->id] = format_string($amilestone->abstract);
            }
            $mform->addElement('select', 'milestoneid', get_string('milestone', 'techproject'), $milestonesoptions);
        }

        $label = get_string('description', 'techproject');
        $mform->addElement('editor', 'description_editor', $label, null, $this->editoroptions);
        $mform->setType('decription_editor', PARAM_RAW);

        $mform->addElement('header', 'headerupload', get_string('delivered', 'techproject'));

        if ($this->mode == 'update') {
            if (!empty($this->current->url)) {
                $label = get_string('deliverable', 'techproject');
                $static = '<a href="'.$deliverable->url.'" target="_blank">'.$deliverable->url.'</a>';
                $mform->addElement('static', 'uploaded', $label, $static);
            } else if ($this->current->localfile) {
                // TODO : using file API give access to locally stored file
                assert(1);
            } else {
                $mform->addElement('static', 'uploaded', get_string('notsubmittedyet', 'techproject'));
            }
        }

        $mform->addElement('text', 'url', get_string('url', 'techproject'));
        $mform->setType('url', PARAM_URL);
        $mform->addElement('static', 'or', '', get_string('oruploadfile', 'techproject'));
        $label = get_string('uploadfile', 'techproject');
        $mform->addElement('filemanager', 'localfile_filemanager', $label, null, $this->attachmentoptions);

        $tasks = techproject_get_tree_options('techproject_task', $this->project->id, $currentgroup);
        $select = "delivid = ? ";
        $params = array($this->current->id);
        $selection = $DB->get_records_select_menu('techproject_task_to_deliv', $select, $params, 'taskid, delivid');
        $tks = array();
        if (!empty($tasks)) {
            foreach ($tasks as $atask) {
                $tks[$atask->id] = $atask->ordering.' - '.shorten_text(format_string($atask->abstract), 90);
            }
        }
        $label = get_string('tasktodeliv', 'techproject');
        $select = &$mform->addElement('select', 'tasktodeliv', $label, $tks, array('size' => 8));
        $select->setMultiple(true);
        $mform->addHelpButton('tasktodeliv', 'task_to_deliv', 'techproject');

        $this->add_action_buttons(true);
    }

    public function set_data($defaults) {

        $context = context_module::instance($this->project->cmid);

        $draftideditor = file_get_submitted_draft_itemid('description_editor');
        $currenttext = file_prepare_draft_area($draftideditor, $context->id, 'mod_techproject', 'description_editor',
                                               $defaults->id, array('subdirs' => true), $defaults->description);
        $defaults = file_prepare_standard_editor($defaults, 'description', $this->editoroptions, $context, 'mod_techproject',
                                                 'deliverabledescription', $defaults->id);
        $defaults = file_prepare_standard_filemanager($defaults, 'localfile', $this->attachmentoptions, $context,
                                                      'mod_techproject', 'deliverablelocalfile', $defaults->id);
        $defaults->description = array('text' => $currenttext,
                                       'format' => $defaults->descriptionformat,
                                       'itemid' => $draftideditor);

        parent::set_data($defaults);
    }
}
