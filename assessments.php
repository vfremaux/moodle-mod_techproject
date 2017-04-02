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
 * This is a screen for assessing students
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

/**
 * A small utility function for making scale menus
 *
 * @param object $project
 * @param int $id
 * @param string $selected
 * @param boolean $return if return is true, returns the HTML flow as a string, prints it otherwise
 */
function make_grading_menu($project, $id, $selected = '', $return = false) {
    if ($project->grade > 0) {
        for ($i = 0; $i <= $project->grade; $i++) {
            $scalegrades[$i] = $i;
        }
    } else {
        $scaleid = -($project->grade);
        if ($scale = $DB->get_record('scale', array('id' => $scaleid))) {
            $scalegrades = make_menu_from_list($scale->scale);
        }
    }
    $str = html_writer::select($scalegrades, $id, $selected, '');
    if ($return) {
        return $str;
    }
    echo $str;
}


if (!has_capability('mod/techproject:gradeproject', $context)) {
    print_error(get_string('notateacher', 'techproject'));
    return;
}

echo $pagebuffer;

echo $OUTPUT->heading(get_string('assessment'));

// Checks if assessments can occur.
if (!groups_get_activity_groupmode($cm, $project->course)) {
    $fields = 'u.id,'.get_all_user_name_fields(true, 'u').',email,'.user_picture::fields();
    $groupstudents = get_users_by_capability($context, 'mod/techproject:canbeevaluated', $fields, 'u.lastname');
} else {
    $groupmembers = groups_get_members($currentgroupid);
    foreach ($groupmembers as $amember) {
        if (!has_capability('mod/techproject:canbeevaluated', $context, $amember->id)) {
            continue;
        }
        $groupstudents[] = clone($amember);
    }
}

if (!isset($groupstudents) || count($groupstudents) == 0 || empty($groupstudents)) {
    echo $OUTPUT->box(get_string('noonetoassess', 'techproject'), 'center', '70%');
    return;
} else {
    $studentlistarr = array();
    foreach ($groupstudents as $astudent) {
        $studentlistarr[] = $astudent->lastname.' '.$astudent->firstname.' '.$OUTPUT->user_picture($astudent);
    }
    echo $OUTPUT->box_start('center', '80%');
    echo '<center><i>'.get_string('evaluatingforusers', 'techproject').' : </i> '.implode(',', $studentlistarr).'</center><br/>';
}

if ($work == 'regrade') {
    $autograde = techproject_autograde($project, $currentgroupid);
    if ($project->grade > 0) {
        unset($assessment);
        $assessment->id = 0;
        $assessment->projectid = $project->id;
        $assessment->groupid = $currentgroupid;
        $assessment->userid = $astudent->id;
        $assessment->itemid = 0;
        $assessment->itemclass = 'auto';
        $assessment->criterion = 0;
        $assessment->grade = round($autograde * $project->grade);
        $select = "projectid = ? AND userid = ? AND itemid = 0 AND itemclass='auto'";
        if ($oldrecord = $DB->get_record_select('techproject_assessment', $select, array($project->id, $astudent->id))) {
            $assessment->id = $oldrecord->id;
            $DB->update_record('techproject_assessment', $assessment);
        } else {
            $DB->insert_record('techproject_assessment', $assessment);
        }
    }
}

echo $OUTPUT->box_end();

// Do what needed.

if ($work == 'dosave') {
    // Getting candidate keys for grading.
    $parmkeys = array_keys($_POST);

    function filter_teachergrade_keys($var) {
        return preg_match("/teachergrade_/", $var);
    }

    function filter_freegrade_keys($var) {
        return preg_match("/free_/", $var);
    }

    $teachergrades = array_filter($parmkeys, 'filter_teachergrade_keys');
    $freegrades = array_filter($parmkeys, 'filter_freegrade_keys');
    foreach ($groupstudents as $astudent) {
        // Dispatch autograde for all students.
        if ($project->autogradingenabled) {
            unset($assessment);
            $assessment->id = 0;
            $assessment->projectid = $project->id;
            $assessment->groupid = $currentgroupid;
            $assessment->userid = $astudent->id;
            $assessment->itemid = 0;
            $assessment->itemclass = 'auto';
            $assessment->criterion = 0;
            $grade = optional_param('autograde', '', PARAM_INT);
            if (!empty($grade)) {
                $assessment->grade = $grade;
            }
            $select = "
                projectid = ? AND
                userid = ? AND
                itemid = ? AND
                itemclass = ?
            ";
            $sqlparams = array($project->id, $astudent->id, $assessment->itemid, $assessment->itemclass);
            if ($oldrecord = $DB->get_record_select('techproject_assessment', $select, $sqlparams)) {
                $assessment->id = $oldrecord->id;
                $DB->update_record('techproject_assessment', $assessment);
                $event = \mod_techproject\event\grade_updated::create_from_assessment($techproject, $context, $assessment, $astudent->id);
                $event->trigger();
            } else {
                $DB->insert_record('techproject_assessment', $assessment);
            }
        }

        // Dispatch teachergrades for all students.
        foreach ($teachergrades as $agradekey) {
            preg_match('/teachergrade_([^_]*?)_([^_]*?)(?:_(.*?))?$/', $agradekey, $matches);
            unset($assessment);
            $assessment->id = 0;
            $assessment->projectid = $project->id;
            $assessment->groupid = $currentgroupid;
            $assessment->userid = $astudent->id;
            $assessment->itemid = $matches[2];
            $assessment->itemclass = $matches[1];

            $criterionclause = '';
            if (isset($matches[3])) {
                 $assessment->criterion = $matches[3];
                 $criterionclause = "AND criterion='{$assessment->criterion}'";
            }
            $grade = optional_param($agradekey, '', PARAM_INT);
            if (!empty($grade)) {
                $assessment->grade = $grade;
            }
            $select = "
                projectid = ? AND
                userid = ? AND
                itemid = ? AND
                itemclass = ?
                {$criterionclause}
            ";
            $sqlparams = array($project->id, $astudent->id, $assessment->itemid, $assessment->itemclass);
            if ($oldrecord = $DB->get_record_select('techproject_assessment', $select, $sqlparams)) {
                $assessment->id = $oldrecord->id;
                $DB->update_record('techproject_assessment', $assessment);
            } else {
                $DB->insert_record('techproject_assessment', $assessment);
            }
        }
        // Dispatch freegrades.
        foreach ($freegrades as $agradekey) {
            preg_match('/free_([^_]*?)$/', $agradekey, $matches);
            unset($assessment);
            $assessment->id = 0;
            $assessment->projectid = $project->id;
            $assessment->groupid = $currentgroupid;
            $assessment->userid = $astudent->id;
            $assessment->itemclass = 'free';
            $assessment->itemid = 0;
            $assessment->criterion = $matches[1];
            $grade = optional_param($agradekey, '', PARAM_INT);
            if (!empty($grade)) {
                $assessment->grade = $grade;
            }
            $select = "
                projectid = ? AND
                userid = ? AND
                itemclass = 'free' AND
                itemid = 0 AND
                criterion = ?
            ";
            $sqlparams = array($project->id, $astudent->id, $assessment->criterion);
            if ($oldrecord = $DB->get_record_select('techproject_assessment', $select, $sqlparams)) {
                $assessment->id = $oldrecord->id;
                $DB->update_record('techproject_assessment', $assessment);
            } else {
                $DB->insert_record('techproject_assessment', $assessment);
            }
        }
        $event = \mod_techproject\event\grade_updated::create_from_assessment($techproject, $context, $assessment, $astudent->id);
        $event->trigger();
    }
} else if ($work == 'doerase') {
    foreach ($groupstudents as $astudent) {
        $DB->delete_records('techproject_assessment', array('projectid' => $project->id, 'userid' => $astudent->id));
        $event = \mod_techproject\event\grade_erased::create_from_assessment($techproject, $context, $assessment, $astudent->id);
        $event->trigger();
    }
}
if ($project->teacherusescriteria) {
    $freecriteria = $DB->get_records_select('techproject_criterion', "projectid = ? AND isfree = 1", array($project->id));
    $criteria = $DB->get_records_select('techproject_criterion', "projectid = ? AND isfree = 0", array($project->id));
    if (!$criteria && !$freecriteria) {
        echo $OUTPUT->box(format_text(get_string('cannotevaluatenocriteria', 'techproject'), FORMAT_HTML), 'center');
        return;
    }
}
$cangrade = false;
?>
<center>
<script type="text/javascript">
//<![CDATA[
function senddata(cmd){
    document.forms['assessform'].work.value="do" + cmd;
    document.forms['assessform'].submit();
}
function cancel(){
    document.forms['assessform'].submit();
}
//]]>
</script>
<form name="assessform" method="post" action="view.php">
<input type="hidden" name="id" value="<?php p($cm->id) ?>"/>
<input type="hidden" name="work" value=""/>
<table width="80%">
<?php
$sqlparams = array($project->id, $currentgroupid);
$milestones = $DB->get_records_select('techproject_milestone', "projectid = ? AND groupid = ?", $sqlparams);
$select = "
    projectid = ? AND
    groupid = ?
    GROUP BY itemid, criterion, itemclass
";
$grades = $DB->get_records_select('techproject_assessment', $select, $sqlparams);
$gradesbyclass = array();

// If there are any grades yet, compile them by categories.
if ($grades) {
    $grades = array_values($grades);
    for ($i = 0; $i < count($grades); $i++) {
        $gradesbyclass[$grades[$i]->itemclass][$grades[$i]->itemid][$grades[$i]->criterion] = $grades[$i]->grade;
    }
}
if ($milestones && (!$project->teacherusescriteria || $criteria)) {
    $cangrade = true;
    echo '<tr><td colspan="2" align="center">';
    echo $OUTPUT->heading(get_string('itemevaluators', 'techproject'));
    echo '</td></tr>';
    foreach ($milestones as $amilestone) {
        echo '<tr valign="top"><td align="left"><b>';
        echo get_string('evaluatingfor', 'techproject')." M.{$amilestone->ordering} {$amilestone->abstract}</b>";
        echo "</td></tr><tr><td>";
        if (!$project->teacherusescriteria) {
            $teachergrade = @$gradesbyclass['milestone'][$amilestone->id][0];
            echo get_string('teachergrade', 'techproject').' ';
            make_grading_menu($project, "teachergrade_milestone_{$amilestone->id}", $teachergrade);
        } else {
            foreach ($criteria as $acriterion) {
                $criteriongrade = @$gradesbyclass['milestone'][$amilestone->id][$acriterion->id];
                echo $acriterion->label.' : ';
                make_grading_menu($project, "teachergrade_milestone_{$amilestone->id}_{$acriterion->id}", $criteriongrade);
                echo ' * ' . $acriterion->weight . '<br/>';
            }
        }
        echo '</td></tr>';
    }
}

// Additional free criteria for grading (including autograde).
if ($project->autogradingenabled || $project->teacherusescriteria) {
    echo '<tr><td colspan="2">';
    echo $OUTPUT->heading(get_string('globalevaluators', 'techproject'));
    echo "</td></tr>";
    $cangrade = true;
}
if ($project->autogradingenabled) {
    $autograde = @$gradesbyclass['auto'][0][0];
    echo '<tr><td align="left">'.get_string('autograde', 'techproject').'</td><td align="left">';
    echo make_grading_menu($project, 'autograde', $autograde, true);
    echo " <a href=\"?work=regrade&amp;id={$cm->id}\">".get_string('calculate', 'techproject').'</a></td></tr>';
}
if ($project->teacherusescriteria) {
    if (@$freecriteria) {
        foreach ($freecriteria as $afreecriterion) {
            $freegrade = @$gradesbyclass['free'][0][$afreecriterion->id];
            echo "<tr><td align=\"left\">{$afreecriterion->label}</td><td align=\"left\">";
            make_grading_menu($project, "free_{$afreecriterion->id}", $freegrade);
            echo " x {$afreecriterion->weight}</td></tr>";
        }
    }
}
if (!$cangrade) {
    echo $OUTPUT->box(get_string('cannotevaluate', 'techproject'), 'center', '70%');
}
?>
</table>

<br/>
<br/>
<input type="button" name="go_btn" value="<?php print_string('updategrades', 'techproject') ?>" onclick="senddata('save')" />
<input type="button" name="erase_btn" value="<?php print_string('cleargrades', 'techproject') ?>" onclick="senddata('erase')" />
</form>
</center>