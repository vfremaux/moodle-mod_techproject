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

defined('MOODLE_INTERNAL') || die();

/**
 * This screen is a parametric generic object viewer. It displays the full content of
 * an entity entry and related links allowing to browse the dependency network. Standard
 * movements are : 
 *
 * If objectClass is entity-tree : up next previous down
 * If objectClass is entity-list : next previous
 *
 * If objectClass is requirement :
 * linkedspecs[]
 *
 * If objectClass is specification :
 * linkedrequs[], linkedtasks[]
 *
 * If objectClass is task : 
 * linkedspecs[], linkeddelivs[], linkedmasters[], linkedslaves[]
 *
 * If objectClass is milestone
 * assignedtasks[], assigneddeliv[]
 *
 * If objectClass is deliverables
 * linkedtasks[]
 *
 * @package mod_techproject
 * @category mod
 * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
 * @contributors So Gerard
 * @date 2008/03/03
 * @version phase 1
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

echo $pagebuffer;

$renderer = $PAGE->get_renderer('mod_techproject');

/// get some session toggles

if (array_key_exists('objectClass', $_GET) && !empty($_GET['objectClass'])) {
    $_SESSION['objectClass'] = $_GET['objectClass'];
} else {
    if (!array_key_exists('objectClass', $_SESSION)) {
        $_SESSION['objectClass'] = 'requirement';
    }
}
if (array_key_exists('objectId', $_GET) && !empty($_GET['objectId'])) {
    $_SESSION['objectId'] = $_GET['objectId'];
} else {
    if (!array_key_exists('objectId', $_SESSION)) {
        echo '<center>';
        echo $OUTPUT->box(format_text(get_string('selectanobjectfirst', 'techproject'), FORMAT_HTML), 'center', '70%');
        echo '</center>';
        return;
    }
}
$objectClass = $_SESSION['objectClass'];
$objectId = $_SESSION['objectId'];

/// making viewer
if (!$object = $DB->get_record('techproject_' . $objectClass, array('id' => $objectId, 'projectid' => $project->id, 'groupid' => $currentgroupid))) {
    echo '<center>';
    echo $OUTPUT->box(format_text(get_string('selectanobjectfirst', 'techproject'), FORMAT_HTML), 'center', '70%');
    echo '</center>';
    return;
}
$previousordering = $object->ordering - 1;
$nextordering = $object->ordering + 1;
$params = array('projectid' => $project->id, 'groupid' => $currentgroupid, 'fatherid' => $object->fatherid, 'ordering' => $previousordering);
$previousobject = $DB->get_record("techproject_{$objectClass}", $params);
$params = array('projectid' => $project->id, 'groupid' => $currentgroupid, 'fatherid' => $object->fatherid, 'ordering' => $nextordering);
$nextobject = $DB->get_record("techproject_{$objectClass}", $params);
$linkTable = array();
$linkTable[0] = array();
$linkTable[1] = array();
$linkTable[2] = array();
$linkTable[3] = array();

if ($object) {
    switch ($objectClass) {

        case 'requirement':
            $linkTableTitle[0] = get_string('sublinks', 'techproject');
            $linkTable[0] = techproject_detail_make_sub_table($objectClass, $object, $cm->id);
            // getting related specifications
            $linkTableTitle[1] = '<img src="'.$OUTPUT->pix_url('p/spec', 'techproject')  .'" /> '. get_string('speclinks', 'techproject');
            $query = "
               SELECT
                  s.*
               FROM
                  {techproject_specification} as s,
                  {techproject_spec_to_req} as str
               WHERE
                  s.id = str.specid AND
                  str.reqid = {$object->id}
            ";
            $specifications = $DB->get_records_sql($query);
            if ($specifications) {
                foreach ($specifications as $spec) {
                    $numspec = implode('.', techproject_tree_get_upper_branch('techproject_specification', $spec->id, true, true));
                    $params = array('id' => $cm->id, 'objectId' => $spec->id, 'objectClass' => 'specification');
                    $browselink = new moodle_url('/mod/techproject/view.php', $params);
                    $linkTable[1][] = '<a class="browselink" href="'.$browselink.'">'.$numspec.' '.$spec->abstract.'</a>';
                }
            } else {
                $linkTable[1][] = get_string('nospecassigned', 'techproject');
            }
            break;

        case 'specification':
            $linkTableTitle[0] = get_string('sublinks', 'techproject');
            $linkTable[0] = techproject_detail_make_sub_table($objectClass, $object, $cm->id);
            // getting related requirements
            $linkTableTitle[2] = '<img src="'.$OUTPUT->pix_url('p/req', 'techproject').'" /> '. get_string('requlinks', 'techproject');
            $query = "
               SELECT
                  r.*
               FROM
                  {techproject_requirement} as r,
                  {techproject_spec_to_req} as str
               WHERE
                  r.id = str.reqid AND
                  str.specid = {$object->id}
            ";
            $requirements = $DB->get_records_sql($query);
            if ($requirements) {
                foreach ($requirements as $requ) {
                    $numrequ = implode('.', techproject_tree_get_upper_branch('techproject_requirement', $requ->id, true, true));
                    $params = array('id' => $cm->id, 'objectId' => $spec->id, 'objectClass' => 'requirement');
                    $browselink = new moodle_url('/mod/techproject/view.php', $params);
                    $linkTable[2][] = '<a class="browselink" href="'.$browselink.'">'.$numrequ.' '.$req->abstract.'</a>';
                }
            } else {
                $linkTable[2][] = get_string('norequassigned', 'techproject');
            }
            // Getting related tasks.
            $linkTableTitle[1] = '<img src="'.$OUTPUT->pix_url('p/task', 'techproject').'" /> '. get_string('tasklinks', 'techproject');
            $query = "
               SELECT
                  t.*
               FROM
                  {techproject_task} as t,
                  {techproject_task_to_spec} as stt
               WHERE
                  t.id = stt.taskid AND
                  stt.specid = {$object->id}
            ";
            $tasks = $DB->get_records_sql($query);
            if ($res) {
                foreach ($tasks as $task) {
                    $numtask = implode('.', techproject_tree_get_upper_branch('techproject_task', $task->id, true, true));
                    $params = array('id' => $cm->id, 'objectId' => $task->id, 'objectClass' => 'task');
                    $taskurl = new moodle_url('/mod/techproject/view.php', $params);
                    $linkTable[1][] = '<a class="browselink" href="'.$taskurl.'">'.$numtask.' '.$task->abstract.'</a>';
                }
            } else {
                $linkTable[1][] = get_string('notaskassigned', 'techproject');
            }
            break;

        case 'task':
            $linkTableTitle[0] = get_string('sublinks', 'techproject');
            $linkTable[0] = techproject_detail_make_sub_table($objectClass, $object, $cm->id);

            // Getting related specifications.
            $linkTableTitle[2] = '<img src="'.$OUTPUT->pix_url('p/spec', 'techproject').'" /> '. get_string('speclinks', 'techproject');
            $query = "
               SELECT
                  s.*
               FROM
                  {techproject_specification} as s,
                  {techproject_task_to_spec} as stt
               WHERE
                  s.id = stt.specid AND
                  stt.taskid = {$object->id}
            ";
            $specifications = $DB->get_records_sql($query);
            if ($specifications) {
                foreach ($specifications as $spec) {
                    $numspec = implode('.', techproject_tree_get_upper_branch('techproject_specification', $spec->id, true, true));
                    $params = array('id' => $cm->id, 'objectId' => $spec->id, 'objectClass' => 'specification');
                    $specifurl = new moodle_url('/mod/techproject/view.php', $params);
                    $linkTable[2][] = '<a class="browselink" href="'.$specifurl.'">'.$numspec.' '.$spec->abstract.'</a>';
                }
            } else {
                $linkTable[2][] = get_string('nospecassigned', 'techproject');
            }

            // Getting related deliverables.
            $linkTableTitle[3] = '<img src="'.$OUTPUT->pix_url('p/deliv', 'techproject').'" /> '. get_string('delivlinks', 'techproject');
            $query = "
               SELECT
                  d.id,
                  d.abstract
               FROM
                  {techproject_deliverable} as d,
                  {techproject_task_to_deliv} as std
               WHERE
                  d.id = std.delivid AND
                  std.taskid = {$object->id}
            ";
            $deliverables = $DB->get_records_sql($query);
            if ($deliverables) {
                foreach ($deliverables as $deliv) {
                    $numdeliv = implode('.', techproject_tree_get_upper_branch('techproject_deliverable', $deliv->id, true, true));
                    $params = array('id' => $cm->id, 'objectId' => $deliv->id, 'objectClass' => 'deliverable');
                    $delivurl = new moodle_url('/mod/techproject/view.php', $params);
                    $linkTable[3][] = '<a class="browselink" href="'.$delivurl.'">'.$numdeliv.' '.$deliv->abstract.'</a>';
                }
            } else {
                $linkTable[3][] = get_string('nodelivassigned', 'techproject');
            }
            break;

        case 'milestone':
        case 'deliverable':
            $linkTableTitle[0] = get_string('sublinks', 'techproject');
            $linkTable[0] = techproject_detail_make_sub_table($objectClass, $object, $cm->id);

            // Getting related tasks.
            $linkTableTitle[2] = '<img src="'.$OUTPUT->pix_url('p/task', 'techproject').'" /> '. get_string('tasklinks', 'techproject');
            $query = "
               SELECT
                  t.id,
                  t.abstract
               FROM
                  {techproject_task} as t,
                  {techproject_task_to_deliv} as std
               WHERE
                  std.id = std.taskid AND
                  std.delivid = {$object->id}
            ";
            $tasks = $DB->get_records_sql($query);
            if ($tasks) {
                foreach ($tasks as $task) {
                    $numtask = implode('.', techproject_tree_get_upper_branch('techproject_task', $task->id, true, true));
                    $params = array('id' => $cm->id, 'objectId' => $task->id, 'objectClass' => 'task');
                    $taskurl = new moodle_url('/mod/techproject/view.php', $params);
                    $linkTable[3][] = '<a class="browselink" href="'.$taskurl.'">'.$numtask.' '.$task->abstract.'</a>';
                }
            } else {
                $linkTable[3][] = get_string('notaskassigned', 'techproject');
            }
            break;
    }
} else {
    echo $OUTPUT->box(get_string('invalidobject','techproject'), 'center', '80%');
    return;
}
?>
<!-- main layout for the detail view -->
<div class="row-fluid techproject-entity-detail">
<div class="span3">
        <?php
        if ($previousobject) {
            $params = array('id' => $cm->id, 'objectId' => $previousobject->id, 'objectClass' => $objectClass);
            $prevurl = new moodle_url('/mod/techproject/view.php', $params);
            echo '<a class="browselink" href="'.$prevurl.'">'.get_string('previous', 'techproject').'</a>';
        }  else {
            echo '<span class="disabled">'.get_string('previous', 'techproject').'</span>';
        }
        ?>
        <br/>
        <br/>
        <?php if (count(@$linkTable[0])) echo $renderer->block(@$linkTableTitle[0], @$linkTable[0]); ?>
        <br/>
        <?php if (count(@$linkTable[2])) echo $renderer->block(@$linkTableTitle[2], @$linkTable[2]); ?>
</div>
<div class="span6">
<?php
if ($object->fatherid != 0) {
    $params = array('id' => $cm->id, 'objectId' => $object->fatherid, 'objectClass' => $objectClass);
    $parenturl = new moodle_url('/mod/techproject/view.php', $params);
    echo '<a class="browselink" href="'.$parenturl.'">'.get_string('parent', 'techproject').'</a>';
}
$printfunction = "techproject_print_single_{$objectClass}";
$printfunction($object, $project, $currentgroupid, $cm->id, 0, $fullsingle = true);
?>
</div>
<div class="span3">
        <?php
        if ($nextobject) {
            $params = array('id' => $cm->id, 'objectId' => $nextobject->id, 'objectClass' => $objectClass);
            $nexturl = new moodle_url('/mod/techproject/view.php', $params);
            echo '<a class="browselink" href="'.$nexturl.'">'.get_string('next', 'techproject').'</a>';
        } else {
            echo '<span class="disabled">'.get_string('next', 'techproject').'</span>';
        }
        ?>
        <br/>
        <br/>
        <?php 
            if (count(@$linkTable[1])) {
                echo $renderer->block(@$linkTableTitle[1], @$linkTable[1]);
            }
        ?>
        <br/>
        <?php
            if (count(@$linkTable[3])) {
                echo $renderer->block(@$linkTableTitle[3], @$linkTable[3]);
            }
        ?>
</div>
</div>