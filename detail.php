<?php

    /**
    * Project : Technical Project Manager (IEEE like)
    *
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
    * @package mod-techproject
    * @category mod
    * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
    * @contributors So Gerard
    * @date 2008/03/03
    * @version phase 1
    * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
    */

	if (!defined('MOODLE_INTERNAL'))  die('You cannot use this script that way');

	echo $pagebuffer;

/// get some session toggles

    if (array_key_exists('objectClass', $_GET) && !empty($_GET['objectClass'])){
    	$_SESSION['objectClass'] = $_GET['objectClass'];
    }
    else{
        if (!array_key_exists('objectClass', $_SESSION))
            $_SESSION['objectClass'] = 'requirement';
    }
    if (array_key_exists('objectId', $_GET) && !empty($_GET['objectId'])){
    	$_SESSION['objectId'] = $_GET['objectId'];
    }
    else{
        if (!array_key_exists('objectId', $_SESSION)){
            echo '<center>';
            echo $OUTPUT->box(format_text(get_string('selectanobjectfirst', 'techproject'), FORMAT_HTML), 'center', '70%');
            echo '</center>';
            return;
        }
    }
    $objectClass = $_SESSION['objectClass'];
    $objectId = $_SESSION['objectId'];

/// making viewer
    if (!$object = $DB->get_record('techproject_' . $objectClass, array('id' => $objectId, 'projectid' => $project->id, 'groupid' => $currentgroupid))){
        echo '<center>';
        echo $OUTPUT->box(format_text(get_string('selectanobjectfirst', 'techproject'), FORMAT_HTML), 'center', '70%');
        echo '</center>';
        return;
    }
    $previousordering = $object->ordering - 1;
    $nextordering = $object->ordering + 1;
    $query = "
       SELECT 
          *
       FROM
          {techproject_{$objectClass}}
       WHERE
          projectid = {$project->id} AND
          groupid = {$currentgroupid} AND
          fatherid = {$object->fatherid} AND
          ordering = {$previousordering}
    ";
    $previousobject = $DB->get_record_sql($query);
    $query = "
       SELECT 
          *
       FROM
          {techproject_{$objectClass}}
       WHERE
          projectid = {$project->id} AND
          groupid = {$currentgroupid} AND
          fatherid = {$object->fatherid} AND
          ordering = {$nextordering}
    ";
    $nextobject = $DB->get_record_sql($query);
    $linkTable = array();
    $linkTable[0] = array();
    $linkTable[1] = array();
    $linkTable[2] = array();
    $linkTable[3] = array();

    function makeSubTable($objectClass, $object, $cmid){
    	global $DB;
        // make link tables
        $res = $DB->get_records("techproject_{$objectClass}", array('fatherid' => $object->id));
        $linkTable = array();
        if ($res){
            foreach($res as $aNode){
                $numrequ = implode('.', techproject_tree_get_upper_branch("techproject_{$objectClass}", $aNode->id, true, true));
                $linkTable[] = "<a class=\"browselink\" href=\"view.php?id={$cmid}&amp;objectId={$aNode->id}&amp;objectClass={$objectClass}\">{$numrequ} {$aNode->abstract}</a>";
            }
        }
        return $linkTable;
    }

    if ($object){
        switch($objectClass){
            case 'requirement' : {
                $linkTableTitle[0] = get_string('sublinks', 'techproject');
                $linkTable[0] = makeSubTable($objectClass, $object, $cm->id);
                // getting related specifications
                $linkTableTitle[1] = "<img src=\"{$CFG->wwwroot}/mod/techproject/pix/p/spec.gif\" /> ". get_string('speclinks', 'techproject');
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
                $res = $DB->get_records_sql($query);
                if ($res){
                    foreach($res as $aSpecification){
                        $numrequ = implode('.', techproject_tree_get_upper_branch('techproject_specification', $aSpecification->id, true, true));
                        $linkTable[1][] = "<a class=\"browselink\" href=\"view.php?id={$cm->id}&amp;objectId={$aSpecification->id}&amp;objectClass=specification\">{$numrequ} {$aSpecification->abstract}</a>";
                    }
                }
                else{
                    $linkTable[1][] = get_string('nospecassigned', 'techproject');
                }
            }
            break;
            case 'specification' : {
                $linkTableTitle[0] = get_string('sublinks', 'techproject');
                $linkTable[0] = makeSubTable($objectClass, $object, $cm->id);
                // getting related requirements
                $linkTableTitle[2] = "<img src=\"{$CFG->wwwroot}/mod/techproject/pix/p/req.gif\" /> ". get_string('requlinks', 'techproject');
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
                $res = $DB->get_records_sql($query);
                if ($res){
                    foreach($res as $aRequirement){
                        $numrequ = implode('.', techproject_tree_get_upper_branch('techproject_requirement', $aRequirement->id, true, true));
                        $linkTable[2][] = "<a class=\"browselink\" href=\"view.php?id={$cm->id}&amp;objectId={$aRequirement->id}&amp;objectClass=requirement\">{$numrequ} {$aRequirement->abstract}</a>";
                    }
                }
                else{
                    $linkTable[2][] = get_string('norequassigned', 'techproject');
                }
                // getting related tasks
                $linkTableTitle[1] = "<img src=\"{$CFG->wwwroot}/mod/techproject/pix/p/task.gif\" /> ". get_string('tasklinks', 'techproject');
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
                $res = $DB->get_records_sql($query);
                if ($res){
                    foreach($res as $aTask){
                        $numrequ = implode('.', techproject_tree_get_upper_branch('techproject_task', $aTask->id, true, true));
                        $linkTable[1][] = "<a class=\"browselink\" href=\"view.php?id={$cm->id}&amp;objectId={$aTask->id}&amp;objectClass=task\">{$numrequ} {$aTask->abstract}</a>";
                    }
                }
                else{
                    $linkTable[1][] = get_string('notaskassigned', 'techproject');
                }
            }
            break;
            case 'task' : {
                $linkTableTitle[0] = get_string('sublinks', 'techproject');
                $linkTable[0] = makeSubTable($objectClass, $object, $cm->id);
                // getting related specifications
                $linkTableTitle[2] = "<img src=\"{$CFG->wwwroot}/mod/techproject/pix/p/spec.gif\" /> ". get_string('speclinks', 'techproject');
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
                $res = $DB->get_records_sql($query);
                if ($res){
                    foreach($res as $aSpecification){
                        $numrequ = implode('.', techproject_tree_get_upper_branch('techproject_specification', $aSpecification->id, true, true));
                        $linkTable[2][] = "<a class=\"browselink\" href=\"view.php?id={$cm->id}&amp;objectId={$aSpecification->id}&amp;objectClass=specification\">{$numrequ} {$aSpecification->abstract}</a>";
                    }
                }
                else{
                    $linkTable[2][] = get_string('nospecassigned', 'techproject');
                }
                // getting related deliverable
                $linkTableTitle[3] = "<img src=\"{$CFG->wwwroot}/mod/techproject/pix/p/deliv.gif\" /> ". get_string('delivlinks', 'techproject');
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
                $res = $DB->get_records_sql($query);
                if ($res){
                    foreach($res as $aDeliverable){
                        $numrequ = implode('.', techproject_tree_get_upper_branch('techproject_deliverable', $aDeliverable->id, true, true));
                        $linkTable[3][] = "<a class=\"browselink\" href=\"view.php?id={$cm->id}&amp;objectId={$aDeliverable->id}&amp;objectClass=deliverable\">{$numrequ} {$aDeliverable->abstract}</a>";
                    }
                }
                else{
                    $linkTable[3][] = get_string('nodelivassigned', 'techproject');
                }
            }
            break;
            case 'milestone' : {
            }
            case 'deliverable' : {
                $linkTableTitle[0] = get_string('sublinks', 'techproject');
                $linkTable[0] = makeSubTable($objectClass, $object, $cm->id);
                // getting related tasks
                $linkTableTitle[2] = "<img src=\"{$CFG->wwwroot}/mod/techproject/pix/p/task.gif\" /> ". get_string('tasklinks', 'techproject');
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
                $res = $DB->get_records_sql($query);
                if ($res){
                    foreach($res as $aTask){
                        $numtask = implode('.', techproject_tree_get_upper_branch('techproject_task', $aTask->id, true, true));
                        $linkTable[3][] = "<a class=\"browselink\" href=\"view.php?id={$cm->id}&amp;objectId={$aTask->id}&amp;objectClass=task\">{$numtask} {$aTask->abstract}</a>";
                    }
                }
                else{
                    $linkTable[3][] = get_string('notaskassigned', 'techproject');
                }
            }
            break;
        }
    }
    else{
        echo $OUTPUT->box(get_string('invalidobject','techproject'), 'center', '80%');
        return;
    }
    ?>
    <!-- main layout for the detail view -->
    <table cellspacing="5" cellpadding="5" width="100%">
    <tr height="15">
        <td valign="top" width="20%">
            <?php
            if ($previousobject){
                echo "<a class=\"browselink\" href=\"view.php?id={$cm->id}&amp;objectId={$previousobject->id}&amp;objectClass={$objectClass}\">".get_string('previous', 'techproject')."</a>";
            } 
            else{
                echo "<span class=\"disabled\">".get_string('previous', 'techproject')."</span>";
            }
            ?>
            <br/>
            <br/>
            <?php if (count(@$linkTable[0])) print_side_block(@$linkTableTitle[0], '', @$linkTable[0]); ?>
            <br/>
            <?php if (count(@$linkTable[2]))  print_side_block(@$linkTableTitle[2], '', @$linkTable[2]); ?>
        </td>
        <td rowspan="4" align="center" valign="top">
    <?php
    if ($object->fatherid != 0){
        echo "<a class=\"browselink\" href=\"view.php?id={$cm->id}&amp;objectId={$object->fatherid}&amp;objectClass={$objectClass}\">".get_string('parent', 'techproject')."</a>";
    }
    $printfunction = "techproject_print_single_{$objectClass}";
    $printfunction($object, $project, $currentgroupid, $cm->id, 0, $fullsingle = true);
    ?>
        </td>
        <td valign="top" width="20%">
            <?php
            if ($nextobject){
                echo "<a class=\"browselink\" href=\"view.php?id={$cm->id}&amp;objectId={$nextobject->id}&amp;objectClass={$objectClass}\">".get_string('next', 'techproject')."</a>";
            } 
            else{
                echo "<span class=\"disabled\">".get_string('next', 'techproject')."</span>";
            }
            ?>
            <br/>
            <br/>
            <?php if (count(@$linkTable[1]))  print_side_block(@$linkTableTitle[1], '', @$linkTable[1]); ?>
            <br/>
            <?php if (count(@$linkTable[3]))  print_side_block(@$linkTableTitle[3], '', @$linkTable[3]); ?>
        </td>
    </tr>
    <tr height="50">
        <td valign="top">
        </td>
        <td valign="top">
        </td>
    </tr>
    <tr height="50">
        <td valign="top">
        </td>
        <td valign="top">
        </td>
    </tr>
    </table>