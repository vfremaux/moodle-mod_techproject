<?php

    /**
    * Project : Technical Project Manager (IEEE like)
    *
    * This screen show tasks plan by assignee. Unassigned tasks are shown 
    * below assigned tasks
    *
    * @package mod-techproject
    * @category mod
    * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
    * @date 2008/03/03
    * @version phase1
    * @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
    * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
    */

	if (!defined('MOODLE_INTERNAL'))  die('You cannot use this script that way');

	echo $pagebuffer;

    $TIMEUNITS = array(get_string('unset','techproject'),get_string('hours','techproject'),get_string('halfdays','techproject'),get_string('days','techproject'));
    $haveAssignedTasks = false;
    if (!groups_get_activity_groupmode($cm, $project->course)){
        $groupusers = get_users_by_capability($context, 'mod/techproject:beassignedtasks', 'u.id, u.firstname, u.lastname, u.email, u.picture', 'u.lastname');
    } else {
        if ($currentgroupid) {
            $groupusers = groups_get_members($currentgroupid);
        } else {
            // we could not rely on the legacy function
            $groupusers = techproject_get_users_not_in_group($project->course);
        }
    }
    if (!isset($groupusers) || count($groupusers) == 0 || empty($groupusers)){
        echo $OUTPUT->box(get_string('noassignee','techproject'), 'center');
    } else {
        echo $OUTPUT->heading(get_string('assignedtasks','techproject'));
        echo '<br/>';
        echo $OUTPUT->box_start('center', '100%');
        foreach($groupusers as $aUser){
        	techproject_complete_user($aUser);
    ?>
    <table width="100%">
        <tr>
            <td class="byassigneeheading level1">
    <?php 
			$hidesub = "<a href=\"javascript:toggle('{$aUser->id}','sub{$aUser->id}', false, '{$CFG->wwwroot}');\"><img name=\"img{$aUser->id}\" src=\"{$CFG->wwwroot}/mod/techproject/pix/p/switch_minus.gif\" alt=\"collapse\" /></a>";
            echo $hidesub.' '.get_string('assignedto','techproject').' '.fullname($aUser).' '.$OUTPUT->user_picture($USER);
    ?>
            </td>
            <td class="byassigneeheading level1" align="right">
    <?php
            $query = "
               SELECT 
                  SUM(planned) as planned,
                  SUM(done) as done,
                  SUM(spent) as spent,
                  COUNT(*) as count
               FROM
                  {techproject_task} as t
               WHERE
                  t.projectid = ? AND
                  t.groupid = ? AND
                  t.assignee = ?
               GROUP BY
                  t.assignee
            ";
            $res = $DB->get_record_sql($query, array($project->id, $currentgroupid, $aUser->id));
            if ($res){
                $over = ($res->planned) ? round((($res->spent - $res->planned) / $res->planned) * 100) : 0 ;
                // calculates a local alarm for lateness
                $hurryup = '';
                if ($res->planned && ($res->spent <= $res->planned)){
                    $hurryup = (round(($res->spent / $res->planned) * 100) > ($res->done / $res->count)) ? "<img src=\"{$CFG->wwwroot}/mod/techproject/pix/p/late.gif\" title=\"".mb_convert_encoding(get_string('hurryup','techproject'), 'UTF8', 'ISO-8859-1')."\" />" : '' ;
                }
                $lateclass = ($over > 0) ? 'toolate' : 'intime';
                $workplan = get_string('assignedwork','techproject').' '.(0 + $res->planned).' '.$TIMEUNITS[$project->timeunit];
                $realwork = get_string('realwork','techproject')." <span class=\"{$lateclass}\">".(0 + $res->spent).' '.$TIMEUNITS[$project->timeunit].'</span>';
        	    $completion = ($res->count != 0) ? techproject_bar_graph_over($res->done / $res->count, $over, 100, 10) : techproject_bar_graph_over(-1, 0);
                echo "{$workplan} - {$realwork} {$completion} {$hurryup}";
    	    }
    ?>
            </td>
        </tr>
    </table>
    <table id="<?php echo "sub{$aUser->id}" ?>" width="100%">
    <?php
            // get assigned tasks
            $query = "
               SELECT
                  t.*,
                  qu.label as statuslabel,
                  COUNT(tts.specid) as specs
               FROM
                  {techproject_qualifier} as qu,
                  {techproject_task} as t
               LEFT JOIN
                  {techproject_task_to_spec} as tts
               ON
                  tts.taskid = t.id
               WHERE
                  t.projectid = ? AND
                  t.groupid = ? AND
                  qu.domain = 'taskstatus' AND
                  qu.code = t.status AND
                  t.assignee = ?
               GROUP BY
                  t.id
            ";
            $tasks = $DB->get_records_sql($query, array($project->id, $currentgroupid, $aUser->id));
            if (!isset($tasks) || count($tasks) == 0 || empty($tasks)){
    ?>
        <tr>
            <td>
                <?php print_string('notaskassigned', 'techproject') ?>
            </td>
        </tr>
    <?php        
            } else {
                foreach($tasks as $aTask){
                    $haveAssignedTasks = true;
                    // feed milestone titles for popup display
                    if ($milestone = $DB->get_record('techproject_milestone', array('id' => $aTask->milestoneid))){
                        $aTask->milestoneabstract = $milestone->abstract;
                    }
    ?>
        <tr>
            <td class="level2">
            <?php techproject_print_single_task($aTask, $project, $currentgroupid, $cm->id, count($tasks), true, 'SHORT_WITHOUT_ASSIGNEE_NOEDIT'); ?>
            </td>
        </tr>
    <?php
                }
            }
    ?>
    </table>
    <?php
        }
        echo $OUTPUT->box_end();
    }
    // get unassigned tasks
    $query = "
       SELECT
          *
       FROM
          {techproject_task}
       WHERE
          projectid = ? AND
          groupid = ? AND
          assignee = 0
    ";
    $unassignedtasks = $DB->get_records_sql($query, array($project->id, $currentgroupid));
    echo $OUTPUT->heading(get_string('unassignedtasks','techproject'));
    ?>
    <br/>
    <?php
    echo $OUTPUT->box_start('center', '100%');
    ?>
    <center>
    <table width="100%">
    <?php
    if (!isset($unassignedtasks) || count($unassignedtasks) == 0 || empty($unassignedtasks)){
    ?>
        <tr>
            <td>
                <?php print_string('notaskunassigned', 'techproject') ?>
            </td>
        </tr>
    <?php        
    } else {
        foreach($unassignedtasks as $aTask){
    ?>
        <tr>
            <td class="level2">
                <?php
                $branch = techproject_tree_get_upper_branch('techproject_task', $aTask->id, true, true);
                echo 'T'.implode('.', $branch) . '. ' . $aTask->abstract ;
                echo "&nbsp;<a href=\"view.php?id={$cm->id}&amp;view=view_detail&amp;objectClass=task&amp;objectId=$aTask->id\"><img src=\"{$CFG->wwwroot}/mod/techproject/pix/p/hide.gif\" title=\"".get_string('detail','techproject')."\" /></a>";
                ?>
            </td>
            <td>
            </td>
        </tr>
    <?php
        }
    }
    ?>
    </table>
    </center>
<?php 
    echo $OUTPUT->box_end();
?>