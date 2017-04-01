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
 * @version Moodle 2.x
 * @date 2012/09/01
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/techproject/importlib.php');
require_once($CFG->dirroot.'/mod/techproject/forms/form_import.class.php');

if (!has_capability('mod/techproject:viewprojectcontrols', $context)
        && !has_capability('mod/techproject:manage', $context)) {
    print_error(get_string('notateacher', 'techproject'));
    return;
}

// Perform local use cases.

// exports as XML a full project description **************************.

echo $pagebuffer;

$mform = new Import_Form($url, array('context' => $context));

if ($data = $mform->get_data()) {

    $options = array('subdirs' => false, 'maxfiles' => 1);

    if (!empty($data->doexportall)) {
        $xml = techproject_get_full_xml($project, $currentgroupid);
        echo $OUTPUT->heading(get_string('xmlexport', 'techproject'));
        $xml = str_replace('<', '&lt;', $xml);
        $xml = str_replace('>', '&gt;', $xml);
        echo $OUTPUT->box("<pre>$xml</pre>");
        echo $OUTPUT->continue_button("view.php?id={$cm->id}");
        return;
    }

    $fs = get_file_storage();

    if (!empty($data->clearcssfile)) {
        $fs->delete_area_files($context->id, 'mod_techproject', 'css', $project->id);
    }

    if (!empty($data->clearxslfile)) {
        $fs->delete_area_files($context->id, 'mod_techproject', 'xls', $project->id);
    }

    if ($data->xslfile) {
        file_save_draft_area_files($data->xslfile, $context->id, 'mod_techproject', 'xsl', $data->id, $options);
    }

    if ($data->cssfile) {
        file_save_draft_area_files($data->cssfile, $context->id, 'mod_techproject', 'css', $data->id, $options);
    }

    if ($data->entityfile) {
        $usercontext = context_user::instance($USER->id);
        $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $data->entityfile, 'itemid, filepath, filename', false);
        if (!empty($files)) {
            $file = array_pop($files);

            techproject_import_entity($project->id, $cm->id, $file->get_content(), $data->entitytype, $currentgroupid);
            // Purge area after processing.
            $fs->delete_area_files($usercontext->id, 'user', 'draft', $data->entityfile);
        }
    }
}

$formdata = new StdClass();
$formdata->groupid = $currentgroupid;
$formdata->id = $cm->id;

$mform->set_data($formdata);
echo $mform->display();

// Write output view.
echo $OUTPUT->box_start();

$docurl = new moodle_url('/mod/techproject/xmlview.php', array('id' => $cm->id));
echo '<a class="btn" href="'.$docurl.'" target="_blank">'.get_string('makedocument', 'techproject').'</a>';

echo $OUTPUT->box_end();
