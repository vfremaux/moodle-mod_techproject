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
 * Project : Technical Project Manager (IEEE like)
 *
 * Task operations
 *
 * @package mod-techproject
 * @category mod
 * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
 * @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
defined('MOODLE_INTERNAL') || die();

$pagebuffer .= '<script type="text/javascript" src="'.$CFG->wwwroot.'/mod/techproject/js/groupform.js"></script>';

if ($work == 'add' || $work == 'update') {
    include($CFG->dirroot.'/mod/techproject/edit_task.php');

} else if ($work == 'groupcmd') {
    // Group operation form *********************************************************.
    echo $pagebuffer;
    $ids = optional_param_array('ids', array(), PARAM_INT);
    $cmd = required_param('cmd', PARAM_ALPHA);

    echo '<center>';
    echo $OUTPUT->heading(get_string('groupoperations', 'techproject'));
    echo $OUTPUT->heading(get_string("group$cmd", 'techproject'), 3);

    if ($cmd == 'copy' || $cmd == 'move') {
        echo $OUTPUT->box(get_string('groupcopymovewarning', 'techproject'), 'center', '70%');
    }

    echo '<form name="groupopform" method="post" action="view.php">';
    echo '<input type="hidden" name="id" value="'.$cm->id.'" />';
    echo '<input type="hidden" name="work" value="" />';

    foreach ($ids as $anid) {
        echo '<input type="hidden" name="ids[]" value="'.$anid.'" />';
    }

    // Special command post options.
    if (($cmd == 'move') || ($cmd == 'copy')) {
        echo get_string('to', 'techproject');
        if (@$project->projectusesrequs) {
             $options['requs'] = get_string('requirements', 'techproject');
        }
        if (@$project->projectusesspecs) {
            $options['specs'] = get_string('specifications', 'techproject');
        }
        if (@$project->projectusesspecs) {
            $options['specswb'] = get_string('specificationswithbindings', 'techproject');
        }
        if (@$project->projectusesdelivs) {
            $options['deliv'] = get_string('deliverables', 'techproject');
        }
        if (@$project->projectusesdelivs) {
            $options['delivwb'] = get_string('deliverableswithbindings', 'techproject');
        }
        echo html_writer::select($options, 'to', '', array('choose'));
    }
    if ($cmd == 'applytemplate') {
        echo '<input type="checkbox" name="applyroot" value="1" /> '.get_string('alsoapplyroot', 'techproject');
        echo '<br/>';
    }
    echo '<br/>';

    echo '<input type="button" name="go_btn" value="'.get_string('continue').'" onclick="senddata(\''.$cmd.'\')" />';
    echo '<input type="button" name="cancel_btn" value="'.get_string('cancel').'" onclick="cancel()" />';
    echo '</form>';
    echo '</center>';

} else {
    if ($work) {
        include($CFG->dirroot.'/mod/techproject/tasks.controller.php');
    }
    echo $pagebuffer;

    echo '<form name="groupopform" method="post" action="view.php">';
    echo '<input type="hidden" name="id" value="'.$cm->id.'" />';
    echo '<input type="hidden" name="work" value="groupcmd" />';
    if ($USER->editmode == 'on' && has_capability('mod/techproject:changetasks', $context)) {
        $viewurl = new moodle_url('/mod/techproject/view.php', array('id' => $cm->id, 'work' => 'add', 'fatherid' => 0));
        echo '<br/><a href="'.$viewurl.'">'.get_string('addroottask', 'techproject').'</a> ';
    }
    techproject_print_tasks($project, $currentgroupid, 0, $cm->id);
    if ($USER->editmode == 'on' && has_capability('mod/techproject:changetasks', $context)) {
        $jshandler = 'javascript:selectall(document.forms[\"groupopform\"])';
        echo '<br/><a href="'.$jshandler.'">'.get_string('selectall', 'techproject').'</a>&nbsp;';

        $jshandler = 'javascript:unselectall(document.forms[\"groupopform\"])';
        echo '<a href="'.$jshandler.'">'.get_string('unselectall', 'techproject').'</a>&nbsp;';

        $linkurl = new moodle_url('/mod/techproject/view.php', array('id' => $cm->id, 'work' => 'add', 'fatherid' => 0));
        echo '<a href="'.$linkurl.'">'.get_string('addroottask', 'techproject').'</a> ';
        if (@$SESSION->techproject->tasktemplateid) {
            techproject_print_group_commands(array('markasdone', 'fullfill', 'applytemplate'));
        } else {
            techproject_print_group_commands(array('markasdone', 'fullfill'));
        }
        $linkurl = new moodle_url('/mod/techproject/view.php', array('id' => $cm->id, 'work' => 'recalc'));
        echo '<br/><a href="'.$linkurl.'">'.get_string('recalculate', 'techproject')."</a> ";
    }

    echo '</form>';

}
