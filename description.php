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
 * Prints a desciption of the project (heading).
 *
 * @package mod_techproject
 * @category mod
 * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
 * @date 2008/03/03
 * @version phase1
 * @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
defined('MOODLE_INTERNAL') || die();

require_once('forms/form_description.class.php');

$mform = new Description_Form($url, $project, $work);

if ($work == 'doexport') {
    $heading = $DB->get_record('techproject_heading', array('projectid' => $project->id, 'groupid' => $currentgroupid));
    $projects[$heading->projectid] = $heading;
    include_once($CFG->dirroot.'/mod/techproject/xmllib.php');
    $xml = recordstoxml($projects, 'project', '', true, null);
    $escaped = str_replace('<', '&lt;', $xml);
    $escaped = str_replace('>', '&gt;', $escaped);
    echo $OUTPUT->heading(get_string('xmlexport', 'techproject'));
    echo $OUTPUT->box("<pre>$escaped</pre>");
    echo $OUTPUT->continue_button(new moodle_url('/mod/techproject/view.php', array('view' => 'description', 'id' => $cm->id)));
    return;
}

// Header editing form ********************************************************.

if ($work == 'edit') {

    if ($mform->is_cancelled()) {
        redirect($url);
    }

    if ($heading = $mform->get_data()) {

        $abstractdraftideditor = file_get_submitted_draft_itemid('abstract_editor');
        $heading->abstract_editor['text'] = file_save_draft_area_files($abstractdraftideditor, $context->id, 'mod_techproject',
                                                                       'abstract', $heading->id, array('subdirs' => true),
                                                                       $heading->abstract_editor['text']);

        $rationaledraftideditor = file_get_submitted_draft_itemid('rationale_editor');
        $heading->rationale_editor['text'] = file_save_draft_area_files($rationaledraftideditor, $context->id, 'mod_techproject',
                                                                        'rationale', $heading->id, array('subdirs' => true),
                                                                        $heading->rationale_editor['text']);

        $environmentdraftideditor = file_get_submitted_draft_itemid('environment_editor');
        $heading->environment_editor['text'] = file_save_draft_area_files($environmentdraftideditor, $context->id,
                                                                          'mod_techproject', 'environment', $heading->id,
                                                                          array('subdirs' => true),
                                                                          $heading->environment_editor['text']);

        $heading->id = $heading->headingid;
        $heading->projectid = $project->id;
        $heading->groupid = $currentgroupid;
        $heading->title = $heading->title;
        $heading->abstract = $heading->abstract_editor['text'];
        $heading->rationale = $heading->rationale_editor['text'];
        $heading->environment = $heading->environment_editor['text'];
        $heading->organisation = $heading->organisation;
        $heading->department = $heading->department;

        $heading = file_postupdate_standard_editor($heading, 'abstract', $mform->editoroptions, $context, 'mod_techproject',
                                                   'abstract', $heading->id);
        $heading = file_postupdate_standard_editor($heading, 'rationale', $mform->editoroptions, $context, 'mod_techproject',
                                                   'rationale', $heading->id);
        $heading = file_postupdate_standard_editor($heading, 'environment', $mform->editoroptions, $context, 'mod_techproject',
                                                   'environment', $heading->id);

        $DB->update_record('techproject_heading', $heading);
        redirect($url);
    }

    $projectheading = $DB->get_record('techproject_heading', array('projectid' => $project->id, 'groupid' => $currentgroupid));

    // Start ouptuting here.

    echo $pagebuffer;

    echo $OUTPUT->heading(get_string('editheading', 'techproject'));
    $projectheading->headingid = $projectheading->id;
    $projectheading->id = $cm->id;
    $projectheading->format = FORMAT_HTML;
    $projectheading->projectid = $project->id;

    $mform->set_data($projectheading);
    $mform->display();

} else {
    // Start ouptuting here.
    echo $pagebuffer;
    techproject_print_heading($project, $currentgroupid);
    echo "<center>";
    if ($USER->editmode == 'on') {
        $linkurl = new moodle_url('/mod/techproject/view.php', array('work' => 'edit', 'id' => $cm->id));
        echo '<br/><a href="'.$linkurl.'" >'.get_string('editheading', 'techproject').'</a>';
        $linkurl = new moodle_url('/mod/techproject/view.php', array('work' => 'doexport', 'id' => $cm->id));
        echo ' - <a href="'.$linkurl.'" >'.get_string('exportheadingtoxml', 'techproject').'</a>';
    }
    $linkurl = new moodle_url('/mod/techproject/xmlview.php', array('id' => $cm->id));
    echo '<br/><a href="'.$linkurl.'" target="_blank">'.get_string('gettheprojectfulldocument', 'techproject').'</a>';
    if (!empty($project->accesskey)) {
        $encodedkey = urlencode($project->accesskey);
        $docurl = new moodle_url('/mod/techproject/xmlview.php', array('accesskey' => $encodedkey, 'id' => $cm->id));
        echo '<br/>'.get_string('sharethisdocument', 'techproject', $docurl);
    }
    echo '</center>';
}