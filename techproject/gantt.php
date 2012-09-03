<?php // $Id: gantt.php,v 1.1.1.1 2012-08-01 10:16:10 vf Exp $

    /**
    * Project : Technical Project Manager (IEEE like)
    *
    * Gant chart for the project.
    *
    * @package mod-techproject
    * @category mod
    * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
    * @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
    * @date 2008/03/03
    * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
    */

    $scale = optional_param('scale', 1.0, PARAM_NUMBER);
    $timeXWidth = 600 * $scale ;
    $labelWidth = 200 ;
    $timeFactor = ($project->projectend - $project->projectstart) / $timeXWidth; // seconds per pixel
    $tasks = $DB->get_records_select('techproject_task', "projectid = {$project->id} AND groupid = {$currentGroupId}", "assignee,taskstart");

	echo $pagebuffer;

    echo $OUTPUT->heading(get_string('ganttchart', 'techproject'));

    $sortedTasks = array();
    $unscheduledTasks = array();
    $assignees = array();
    if (!empty($tasks)){
        foreach($tasks as $aTask){
            if (!$aTask->taskstartenable && !$aTask->taskendenable){
              	$unscheduledTasks[] = $aTask;
	          	if ($aTask->assignee == 0){
	              	$unassignedfake = new StdClass;
	              	$unassignedfake->firstname = get_string('unassigned', 'techproject');
	              	$unassignedfake->lastname = get_string('tasks', 'techproject');
	              	$unassignedfake->id = 0;
	              	$assignees[0] = $unassignedfake;
	          	} else {
	              	$assignees[$aTask->assignee] = $DB->get_record('user', array('id' => $aTask->assignee));
	          	}
              	continue;
            }
            // fixes actual bounds
            if (!$aTask->taskstartenable && $aTask->taskendenable){
                $aTask->taskstart = $project->projectstart;
            }
            if ($aTask->taskstartenable && !$aTask->taskendenable){
                $aTask->taskend = $project->projectend;
            }

            // calculates graphic bounds 
            $aTask->left = round(($aTask->taskstart - $project->projectstart) / $timeFactor);
            $pixWidth = round(($aTask->taskend - $aTask->taskstart) / $timeFactor);
            $aTask->width = max(4, $pixWidth);
            $aTask->donewidth = $aTask->width * $aTask->done / 100;
            $aTask->undonewidth = $aTask->width - $aTask->donewidth;
            $aTask->undoneleft = $aTask->left;
            $aTask->height = 5;
            if ($aTask->assignee == 0){
              	$sortedTasks[0][] = $aTask;
              	$unassignedfake = new StdClass;
              	$unassignedfake->firstname = get_string('unassigned', 'techproject');
              	$unassignedfake->lastname = get_string('tasks', 'techproject');
              	$unassignedfake->id = 0;
              	$assignees[0] = $unassignedfake;
            } else {
              	$sortedTasks[$aTask->assignee][] = $aTask;
              	$assignees[$aTask->assignee] = $DB->get_record('user', array('id' => $aTask->assignee));
            }
        }
    } else {
       	echo '<center>';
      	echo $OUTPUT->box(get_string('notasks', 'techproject'), 'center', '70%');
       	echo '</center>';
       	return;
    }
    ?>
    <center>
    <table>
        <tr>
            <td align="left">
                <form name="scaleform" action="#" method="GET">
                    <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
                    <?php 
                    print_string('horizontalscale', 'techproject');
                    echo ': ';
                    $scaleopts = array("0.5" => "x 0.5", "0.8" => "x 0.8", "1.0" => "x 1", "1.5" => "x 1.5", "2.0" => "x 2", "3.0" => "x 3", "4.0" => "x 4");
                    echo html_writer::select($scaleopts, 'scale', $scale, '', 'document.forms[\'scaleform\'].submit();'); 
                    ?>
                </form>
            </td>
            <td align="right">
            </td>
        </tr>
    </table>
    <table width="<?php echo $labelWidth + $timeXWidth ?>">
    <?php
    $style =  "style=\"background-image : url('{$CFG->wwwroot}/mod/techproject/gdgenerators/ganttgrid.php?projectid={$project->id}&w={$timeXWidth}&s=0&z=1&id={$cm->id}') ; background-repeat : y-repeat\"";
    $headingstyle =  "style=\"background-image : url('{$CFG->wwwroot}/mod/techproject/gdgenerators/ganttgrid.php?projectid={$project->id}&w={$timeXWidth}&s=0&z=1&outputType=HEADING&id={$cm->id}&lang={$USER->lang}') ; background-repeat : no-repeat\"";
    if (!empty($assignees)){
        foreach(array_values($assignees) as $anAssignee){
            echo "<tr><td class=\"ganttheading\" colspan=\"2\" align=\"left\">{$anAssignee->lastname} {$anAssignee->firstname}</td></tr>";
            if (!isset($sortedTasks[$anAssignee->id])){
                echo '<tr><td colspan="2">';
                print_string('assigneeunloaded', 'techproject');
                echo '</td></tr>';
            } else {
                echo "<tr height=\"22\"><td class=\"gantttasktitle\" width=\"{$labelWidth}\" align=\"left\"></td>";
                echo "<td width=\"{$timeXWidth}\" {$headingstyle} align=\"left\"></td></tr>";
                foreach($sortedTasks[$anAssignee->id] as $aTask){
                    // calculates possible lateness
                    $hurryup = 0;
                    if ($aTask->planned && ($aTask->spent <= $aTask->planned)){
                        $hurryup = $aTask->done - round(($aTask->spent / $aTask->planned) * 100);
                    }
                    $undonecolor = ($hurryup >= 0) ? 'blue' : 'red';
                    $numtask = implode('.', techproject_tree_get_upper_branch('techproject_task', $aTask->id, true, true));
                    echo "<tr><td class=\"gantttasktitle\" width=\"{$labelWidth}\" align=\"left\">".$numtask.' '.shorten_text($aTask->abstract, 25)."</td>";
                    echo "<td width=\"{$timeXWidth}\" {$style} align=\"left\"><a href=\"view.php?id={$cm->id}&amp;view=view_detail&amp;objectClass=task&amp;objectId={$aTask->id}\"><img src=\"{$CFG->wwwroot}/mod/techproject/pix/p/greenpixel.gif\" style=\"position : relative ; left : {$aTask->left}px\" width=\"{$aTask->donewidth}\" height=\"{$aTask->height}\" title=\"{$aTask->abstract}\" border=\"0\" /><img src=\"{$CFG->wwwroot}/mod/techproject/pix/p/{$undonecolor}pixel.gif\" style=\"position : relative ; left : {$aTask->undoneleft}px\" width=\"{$aTask->undonewidth}\" height=\"{$aTask->height}\" title=\"{$aTask->abstract}\" border=\"0\" /></a></td></tr>";
                }
            }
        }
    ?>
    </table>
    <br/>
    <table width="<?php echo $labelWidth + $timeXWidth ?>">
    <?php
        if ($unscheduledTasks){
            echo $OUTPUT->heading(get_string('unscheduledtasks','techproject'));
            echo $OUTPUT->box_start('center', $labelWidth + $timeXWidth);
            foreach($unscheduledTasks as $aTask){
                techproject_print_single_task($aTask, $project, $currentGroupId, $cm->id, count($unscheduledTasks), false, $style='NOEDIT');
            }
            echo $OUTPUT->box_end();
        }
    } else {
       echo $OUTPUT->box(get_string('noassignee','techproject'), 'center', '100%');
    }
    ?>
    </table>
    </center>
