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
* this screen submarizes activity in the project for the current group
* 
* @package mod_techproject
* @category mod
* @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
* @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
*/

require_once($CFG->dirroot.'/mod/techproject/classes/output/mod_techproject_summary_renderer.php');

// Counting leaf requests.

$summaryrenderer = $PAGE->get_renderer('techproject', 'summary');

$params = array('projectid' => $project->id, 'groupid' => $currentgroupid);
$projectheading = $DB->get_record('techproject_heading', $params);

if (has_capability('mod/techproject:viewpreproductionentities', $context)) {
    $select = "
        projectid = ? AND
        groupid = ? AND
        fatherid = 0
    ";

    $params = array($project->id, $currentgroupid);
    $requirements = $DB->get_records_select('techproject_requirement', $select, $params, '', 'id,abstract');
    $leafrequs = array();
    $leafrequlist = '';
    if ($requirements) {
        foreach ($requirements as $aRequirement) {
            $leafrequs = array_merge($leafrequs, techproject_count_leaves('techproject_requirement', $aRequirement->id, true));
        }
        $leafrequlist = implode("','", $leafrequs);
    }
    $projectheading->countrequ = count($leafrequs);

    // Counting specifications.

    $select = "
        projectid = ? AND
        groupid = ? AND
        fatherid = 0
    ";
    $params = array($project->id, $currentgroupid);
    $specifications = $DB->get_records_select('techproject_specification', $select, $params, '', 'id,abstract');
    $leafspecs = array();
    $leafspeclist = '';
    if ($specifications) {
        foreach ($specifications as $aspec) {
            $leafspecs = array_merge($leafspecs, techproject_count_leaves('techproject_specification', $aspec->id, true)) ;
        }
        $leafspeclist = implode("','", $leafspecs);
    }
    $projectheading->countspec = count($leafspecs);
}

// Counting tasks.

$select = "
    projectid = ? AND
    groupid = ? AND
    fatherid = 0
";
$tasks = $DB->get_records_select('techproject_task', $select, array($project->id, $currentgroupid), '', 'id,abstract');
$leaftasks = array();
$leaftasklist = '';
if ($tasks) {
    foreach ($tasks as $atask) {
        $leaftasks = array_merge($leaftasks, techproject_count_leaves('techproject_task', $atask->id, true));
    }
    $leaftasklist = implode("','", $leaftasks);
}
$projectheading->counttask = count($leaftasks);

// Counting deliverables.

$select = "
    projectid = ? AND
    groupid = ? AND
    fatherid = 0
";
$deliverables = $DB->get_records_select('techproject_deliverable', $select, array($project->id, $currentgroupid), '', 'id,abstract');
$leafdelivs = array();
$leafdelivlist = '';
if ($deliverables) {
    foreach ($deliverables as $adeliv) {
        $leafdelivs = array_merge($leafdelivs, techproject_count_leaves('techproject_deliverable', $adeliv->id, true));
    }
    $leafdelivlist = implode("','", $leafdelivs);
}
$projectheading->countdeliv = count($leafdelivs);

// Counting requirements uncovered.

if (has_capability('mod/techproject:viewpreproductionentities', $context)) {

    if (isset($leafrequlist)) {

        list($insql, $inparams) = $DB->get_in_or_equal($leafrequlist);

        $sql = "
           SELECT
             COUNT(IF(str.specid IS NULL, 1, NULL)) as uncovered
           FROM
             {techproject_requirement} as r
           LEFT JOIN
             {techproject_spec_to_req} as str
           ON
             r.id = str.reqid
           WHERE
             r.id $insql AND
             r.projectid = ? AND
             r.groupid = ?
        ";

        $inparams[] = $project->id;
        $inparams[] = $currentgroupid;

        $res = $DB->get_records_sql($sql, $inparams);
        $res = array_values($res);
        $res = $res[0];
        $projectheading->uncoveredrequ = $res->uncovered;
        $projectheading->coveredrequ = $projectheading->countrequ - $res->uncovered;
    }

    // Counting specs uncovered.

    if (isset($leafspeclist)) {

        list($insql, $inparams) = $DB->get_in_or_equal($leafspeclist);

        $query = "
           SELECT
             COUNT(IF(stt.taskid IS NULL, 1, NULL)) as uncovered
           FROM
             {techproject_specification} as s
           LEFT JOIN
             {techproject_task_to_spec} as stt
           ON
             s.id = stt.specid
           WHERE
             s.id $insql AND
             s.projectid = ? AND
             s.groupid = ?
        ";

        $inparams[] = $project->id;
        $inparams[] = $currentgroupid;

        $res = $DB->get_records_sql($query, $inparams);
        $res = array_values($res);
        $res = $res[0];
        $projectheading->uncoveredspec = $res->uncovered;
        $projectheading->coveredspec = $projectheading->countspec - $res->uncovered;
    }
}

// If missing create one.

if (!$projectheading) {
    $projectheading->id = 0;
    $projectheading->projectid = $project->id;
    $projectheading->groupid = $currentgroupid;
    $projectheading->title = '';
    $projectheading->abstract = '';
    $projectheading->rationale = '';
    $projectheading->environment = '';
    $projectheading->organisation = '';
    $projectheading->department = '';
    $DB->insert_record('techproject_heading', $projectheading);
}

// Start producing summary ***************************.

echo $pagebuffer;

echo '<center>';
echo $OUTPUT->box_start('center', '100%');

echo $summaryrenderer->summary($projectheading, $context);

echo '<br/>';

if ($project->teacherusescriteria) {
    $table = new html_table();
    $table->head = array(get_string('criteria', 'techproject'), '');
    $table->align = array('left', 'left');
    $table->size = array('40%', '60%');
    $table->wrap = array('', '');
    $table->data = array();
    $select = "projectid = ? AND isfree = 1";
    $freecriteria = $DB->get_records_select('techproject_criterion', $select, array($project->id));

    $select = "projectid = ? AND isfree = 0";
    $criteria = $DB->get_records_select('techproject_criterion', $select, array($project->id));
    $str = '';

    if ($criteria) {
        foreach ($criteria as $acriteria) {
            $str .= $acriteria->label . ' (x' . $acriteria->weight . ')<br/>';
        }
        $table->data[] = array(get_string('itemcriteriaset', 'techproject'), $str);
    }
    $str = '';
    if ($freecriteria) {
        foreach ($freecriteria as $acriteria) {
            $str .= $acriteria->label . ' (x' . $acriteria->weight . ')<br/>';
        }
        $table->data[] = array(get_string('freecriteriaset', 'techproject'), $str);
    }
    echo html_writer::table($table);
    unset($table);
}

$table = new html_table();
$table->head = array(get_string('assessments', 'techproject'), '');
$table->align = array('left', 'left');
$table->size = array('40%', '60%');
$table->wrap = array('','');

if (time() < $project->assessmentstart) {
    $table->data[] = array(get_string('notavailable'), '');
} else {
    // Getting all grades.
    $select = "projectid = ? AND groupid = ? ";
    $milestones = $DB->get_records_select('techproject_milestone', $select, array($project->id, $currentgroupid));

    $select = "projectid = ? AND groupid = ? GROUP BY itemid, criterion, itemclass";
    $grades = $DB->get_records_select('techproject_assessment', $select, array($project->id, $currentgroupid));

    $select = "projectid = ? AND isfree = 1";
    $freecriteria =  $DB->get_records_select('techproject_criterion', $select, array($project->id));
    
    $select = "projectid = ? AND isfree = 0";
    $criteria = $DB->get_records_select('techproject_criterion', $select, array($project->id));
    $gradesbyclass = array();

    // If there are any grades yet, compile them by categories.

    if ($grades) {
        $grades = array_values($grades);
        for ($i = 0; $i < count($grades); $i++) {
            $gradesbyclass[$grades[$i]->itemclass][$grades[$i]->itemid][$grades[$i]->criterion] = $grades[$i]->grade;
        }
        if ($milestones && (!$project->teacherusescriteria || $criteria)) {
            foreach ($milestones as $aMilestone) {
                $table->data[] = array(get_string('evaluatingfor','techproject'), $aMilestone->abstract);
                if ($project->autogradingenabled){
                    $autograde = @$gradesbyclass['auto_milestone'][$aMilestone->id][0];
                    if (empty($autograde)) {
                        $table->data[] = array(get_string('autograde','techproject'), get_string('notevaluated','techproject'));
                    } else {
                        $table->data[] = array(get_string('autograde','techproject'), $autograde);
                    }
                }
                if (!$project->teacherusescriteria) {
                    $teachergrade = @$gradesbyclass['milestone'][$aMilestone->id][0];
                    if (empty($teachergrade)) {
                        $table->data[] = array(get_string('teachergrade','techproject'), get_string('notevaluated','techproject'));
                    } else {
                        $table->data[] = array(get_string('teachergrade','techproject'), $teachergrade);
                    }
                } else {
                    foreach ($criteria as $aCriterion) {
                        $criteriongrade = @$gradesbyclass['milestone'][$aMilestone->id][$aCriterion->id];
                        if (empty($criteriongrade)) {
                            $table->data[] = array($aCriterion->label, get_string('notevaluated','techproject'));
                        } else {
                            $table->data[] = array($aCriterion->label, $criteriongrade . ' (x' . $aCriterion->weight . ')');
                        }
                    }
                }
            }
        }
    }

    // Additional free criteria for grading.
    if ($freecriteria) {
        foreach ($freecriteria as $aFreeCriterion) {
            $freegrade = @$gradesbyclass['free'][0][$aFreeCriterion->id];
            if (empty($freegrade)) {
                $table->data[] = array($aFreeCriterion->label, get_string('notevaluated','techproject'));
            } else {
                $table->data[] = array($aFreeCriterion->label, $freegrade.' (x'.$aFreeCriterion->weight.')');
            }
        }
    }
}
echo html_writer::table($table);
unset($table);
echo $OUTPUT->box_end();
echo '</center>';
