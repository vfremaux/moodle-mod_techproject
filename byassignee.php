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


    $TIMEUNITS = array(get_string('unset','techproject'),get_string('hours','techproject'),get_string('halfdays','techproject'),get_string('days','techproject'));
    $haveAssignedTasks = false;
    
    if (!groups_get_activity_groupmode($cm, $project->course)){
        $groupusers = get_users_by_capability($context, 'mod/techproject:beassignedtasks', 'u.id, u.firstname, u.lastname, u.email, u.picture', 'u.lastname');
    } else {
        if ($currentGroupId){
            $groupusers = groups_get_members($currentGroupId);
        } else {
            // we could not rely on the legacy function
            $groupusers = techproject_get_users_not_in_group($project->course);
        }
    }
    
    if (!isset($groupusers) || count($groupusers) == 0 || empty($groupusers)){
        print_simple_box(get_string('noassignee','techproject'), 'center');
    } else {
        print_heading_block(get_string('assignedtasks','techproject'));
        echo '<br/>';
        print_simple_box_start('center', '100%');
        foreach($groupusers as $aUser){
    ?>
    <table width="100%">
        <tr>
            <td class="byassigneeheading level1">
    <?php 
        	$hidesub = "<a href=\"javascript:toggle('{$aUser->id}','sub{$aUser->id}');\"><img name=\"img{$aUser->id}\" src=\"{$CFG->wwwroot}/mod/techproject/pix/p/switch_minus.gif\" alt=\"collapse\" style=\"background-color : #E0E0E0\" /></a>";
            echo $hidesub.' '.get_string('assignedto','techproject').' '.fullname($aUser).' '.print_user_picture ($aUser->id, $project->course, !empty($aUser->image), 0, true, true ); 
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
                  {$CFG->prefix}techproject_task as t
               WHERE
                  t.projectid = {$project->id} AND
                  t.groupid = {$currentGroupId} AND
                  t.assignee = {$aUser->id}
               GROUP BY
                  t.assignee
            ";
            $res = get_record_sql($query);
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
                  {$CFG->prefix}techproject_qualifier as qu,
                  {$CFG->prefix}techproject_task as t
               LEFT JOIN
                  {$CFG->prefix}techproject_task_to_spec as tts
               ON
                  tts.taskid = t.id
               WHERE
                  t.projectid = {$project->id} AND
                  t.groupid = {$currentGroupId} AND
                  qu.domain = 'taskstatus' AND
                  qu.code = t.status AND
                  t.assignee = {$aUser->id}
               GROUP BY
                  t.id
            ";
            $tasks = get_records_sql($query);
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
                    if ($milestone = get_record('techproject_milestone', 'id', $aTask->milestoneid)){
                        $aTask->milestoneabstract = $milestone->abstract;
                    }
    ?>
        <tr>
            <td class="level2">
            <?php techproject_print_single_task($aTask, $project, $currentGroupId, $cm->id, count($tasks), true, 'SHORT_WITHOUT_ASSIGNEE_NOEDIT'); ?>
            </td>
        </tr>
    <?php
                }
            }
    ?>
    </table>
    <?php
        }
        print_simple_box_end();
    }
    
    // get unassigned tasks
    $query = "
       SELECT
          *
       FROM
          {$CFG->prefix}techproject_task
       WHERE
          projectid = {$project->id} AND
          groupid = {$currentGroupId} AND
          assignee = 0
    ";
    $unassignedtasks = get_records_sql($query);
    print_heading_block(get_string('unassignedtasks','techproject'));
    ?>
    <br/>
    <?php
    print_simple_box_start('center', '100%');
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
    print_simple_box_end();
?>