<?php

    /**
    * Project : Technical Project Manager (IEEE like)
    *
    * Task operations
    *
    * @package mod-techproject
    * @category mod
    * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
    * @date 2008/03/03
    * @version phase1
    * @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
    * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
    */

    $defaultformat = FORMAT_MOODLE;

    /**
    * a form constraint checking function
    * @param object $project the surrounding project cntext
    * @param object $task form object to be checked
    * @return a control hash array telling error statuses
    */
    function checkConstraints($project, $task){
        $control = NULL;
    
        switch($project->timeunit){
            case HOURS : $plannedtime = 3600 ; break ;
            case HALFDAY : $plannedtime = 3600 * 12 ; break ;
            case DAY : $plannedtime = 3600 * 24 ; break ;
            default : $plannedtime = 0;
        }
        // checking too soon task
        if ($task->taskstartenable && $task->taskstart < $project->projectstart){
            $control['taskstartdate'] = get_string('tasktoosoon','techproject') . '<br/>' . userdate($project->projectstart);
        }
        // task too late (planned to milestone)
        if($task->taskstartenable && $task->milestoneid){
            $milestone = get_record('techproject_milestone', 'projectid', $project->id, 'id', $task->milestoneid);
            if ($milestone->deadlineenable && ($task->taskstart + $plannedtime > $milestone->deadline)){
                $control['taskstartdate'] = get_string('taskstartsaftermilestone','techproject') . '<br/>' . userdate($milestone->deadline);
            }
        }
        // task too late (absolute)
        elseif($task->taskstartenable && ($task->taskstart + $plannedtime > $project->projectend)){
            $control['taskstartdate'] = get_string('tasktoolate','techproject') . '<br/>' . userdate($project->projectend);
        }
        // checking too late end
        elseif($task->taskendenable && $task->milestoneid){
            $milestone = get_record('techproject_milestone', 'projectid', $project->id, 'id', $task->milestoneid);
            if ($milestone->deadlineenable && ($task->taskend > $milestone->deadline)){
                $control['taskenddate'] = get_string('taskfinishesaftermilestone','techproject') . '<br/>' . userdate($milestone->deadline);
            }
        }
        // checking too late end
        elseif($task->taskendenable && $task->taskend > $project->projectend){
            $control['taskenddate'] = get_string('taskfinishestoolate','techproject') . '<br/>' . userdate($project->projectend);
        }
        // checking switched end and start
        elseif($task->taskendenable && $task->taskstartenable && $task->taskend <= $task->taskstart){
            $control['taskenddate'] = get_string('taskfinishesbeforeitstarts','techproject');
        }
        // checking unfeseabletask
        elseif($task->taskendenable && $task->taskstartenable && $task->taskend < $task->taskstart + $plannedtime){
            $control['taskenddate'] = get_string('tasktooshort','techproject') . '<br/> >> ' . userdate($task->taskstart + $plannedtime);
        }
        
        return $control;
    }

    $usehtmleditor = can_use_html_editor();
    $TIMEUNITS = array(get_string('unset','techproject'),get_string('hours','techproject'),get_string('halfdays','techproject'),get_string('days','techproject'));

/// Controller
    
	if ($work) include 'task.controller.php';    
    
/// Add task form *********************************************************

   	if ($work == 'add'){
   	    $fatherid = required_param('fatherid', PARAM_INT);
   	    $tasktitle = ($fatherid) ? 'addsubtask' : 'addtask';
    ?>
    
    <center>
    <?php print_heading(get_string($tasktitle, 'techproject'), 'center'); ?>
    <script type="text/javascript">
    //<![CDATA[
    function senddata(){
        document.forms['addtaskform'].work.value='new';
        <?php if ($usehtmleditor && $CFG->defaulthtmleditor == 'htmlarea') echo "document.forms['addtaskform'].onsubmit();\n"; ?>
        document.forms['addtaskform'].submit();
    }
    
    function cancel(){
        <?php if ($usehtmleditor && $CFG->defaulthtmleditor == 'htmlarea') echo "document.forms['addtaskform'].onsubmit();\n"; ?>
        document.forms['addtaskform'].submit();
    }
    
    var taskstartitems = ['taskstartday','taskstartmonth','taskstartyear','taskstarthour', 'taskstartminute'];
    var taskenditems = ['taskendday','taskendmonth','taskendyear','taskendhour', 'taskendminute'];

    function update(elementname){
        if (elementname == 'quoted'){
            spanelm = document.getElementById('quoted')
            spanelm.innerHTML = document.forms['addtaskform'].costrate.value * document.forms['addtaskform'].planned.value;
        }
        if (elementname == 'spent'){
            spanelm = document.getElementById('spent')
            spanelm.innerHTML = document.forms['addtaskform'].costrate.value * document.forms['addtaskform'].used.value;
        }
    };
    //]]>
    </script>
    <form name="addtaskform" method="post" action="view.php#tsk<?php p($fatherid) ?>">
    <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
    <input type="hidden" name="fatherid" value="<?php echo $fatherid; ?>" />
    <input type="hidden" name="work" value="" />
    <table>
    <tr valign="top">
    	<td align="right"><b><?php print_string('tasktitle', 'techproject') ?>:</b></td>
        <td align="left">
            <input type="text" name="abstract" size="100%" value="<?php p(@$task->abstract) ?>" alt="<?php  print_string('tasktitle', 'techproject') ?>" />
        </td>
    </tr>
    <tr valign="top">
    	<td align="right"><b><?php print_string('owner', 'techproject') ?>:</b></td>
        <td align="left">
            <?php
                echo $USER->lastname . " " . $USER->firstname . " ";
                print_user_picture($USER->id, $course->id, !empty($USER->picture)); 
            ?>
            <input type="hidden" name="owner" value="<?php echo p($USER->id); ?>" />
        </td>
    </tr>
    <tr valign="top">
    	<td align="right"><b><?php print_string('assignee', 'techproject') ?>:</b></td>
        <td align="left">
    <?php 
            $assignees = techproject_get_group_users($project->course, $cm, $currentGroupId);
            if($assignees){
                $assignoptions = array();
                foreach($assignees as $anAssignee){
                    $assignoptions[$anAssignee->id] = $anAssignee->lastname . ' ' . $anAssignee->firstname;
                }
                choose_from_menu($assignoptions, 'assignee', 0, get_string('unassigned','techproject'));
            }
            else{
               print_string('noassignee', 'techproject');
            }
     ?>
        </td>
    </tr>
    
    <tr valign="top">
        <td align="right"><b><?php print_string('taskstartdate', 'techproject') ?>:
                <?php if (isset($controls['taskstartdate'])) echo "<br/><span class=\"inconsistency\">{$controls['taskstartdate']}</span>" ?></b></td>
        <td align="left">
                <input name="taskstartenable" type="checkbox" value="1" alt="<?php print_string('taskstartenable', 'techproject') ?>" onclick="return lockoptions('addtaskform', 'taskstartenable', taskstartitems)" />
            <?php
                print_date_selector('taskstartday', 'taskstartmonth', 'taskstartyear', time());
                echo "&nbsp;-&nbsp;";
                print_time_selector('taskstarthour', 'taskstartminute', time());
                helpbutton('taskstartdate', get_string('taskstartdate', 'techproject'), 'techproject', true, false);
        ?>
            <input type="hidden" name="htaskstartday"    value="0" />
            <input type="hidden" name="htaskstartmonth"  value="0" />
            <input type="hidden" name="htaskstartyear"   value="0" />
            <input type="hidden" name="htaskstarthour"   value="0" />
            <input type="hidden" name="htaskstartminute" value="0" />
        </td>
    </tr>
    
    <tr valign="top">
        <td align="right"><b><?php print_string('taskenddate', 'techproject') ?>:
                <?php if (isset($controls['taskenddate'])) echo "<br/><span class=\"inconsistency\">{$controls['taskenddate']}</span>" ?></b></td>
        <td align="left">
                <input name="taskendenable" type="checkbox" value="1" alt="<?php print_string('taskendenable', 'techproject') ?>" onclick="return lockoptions('addtaskform', 'taskendenable', taskenditems)" />
            <?php
                print_date_selector('taskendday', 'taskendmonth', 'taskendyear', time());
                echo "&nbsp;-&nbsp;";
                print_time_selector('taskendhour', 'taskendminute', time());
                helpbutton('taskenddate', get_string('taskenddate', 'techproject'), 'techproject', true, false);
        ?>
            <input type="hidden" name="htaskendday"    value="0" />
            <input type="hidden" name="htaskendmonth"  value="0" />
            <input type="hidden" name="htaskendyear"   value="0" />
            <input type="hidden" name="htaskendhour"   value="0" />
            <input type="hidden" name="htaskendminute" value="0" />
        </td>
    </tr>
    
    <tr valign="top">
    	<td align="right"><b><?php print_string('worktype', 'techproject') ?>:</b>
    	</td>
        <td align="left">
    <?php
            $worktypes = techproject_get_options('worktype', $project->id);
            $worktypeoptions = array();
            foreach($worktypes as $aWorktype){
                $worktypeoptions[$aWorktype->code] = '['. $aWorktype->code . '] ' . $aWorktype->label;
            }
            choose_from_menu ($worktypeoptions, 'worktype', @$task->worktype, 'choose', '', '---');
            helpbutton('worktype', get_string('worktype', 'techproject'), 'techproject', true, false);
    ?>
        </td>
    </tr>
    <tr valign="top">
    	<td align="right"><b><?php  print_string('status', 'techproject') ?>:</b>
    	</td>
        <td align="left">
    <?php
            $statusses = techproject_get_options('taskstatus', $project->id);
            $statussesoptions = array();
            foreach($statusses as $aStatus){
                $statussesoptions[$aStatus->code] = '['. $aStatus->code . '] ' . $aStatus->label;
            }
            choose_from_menu ($statussesoptions, 'status', @$task->status, 'choose', '', 'PLANNED');
            helpbutton('status', get_string('status', 'techproject'), 'techproject', true, false);
    ?>
        </td>
    </tr>
    
    <tr valign="top">
    	<td align="right"><b><?php print_string('costrate', 'techproject') ?>:</b></td>
        <td align="left">
            <input type="text" name="costrate" size="8" value="" alt="<?php print_string('costrate', 'techproject') ?>"  onchange="update('quoted');update('spent')" />
        </td>
    </tr>
    <tr valign="top">
    	<td align="right"><b><?php print_string('planned', 'techproject') ?>:</b></td>
        <td align="left">
            <input type="text" name="planned" size="3" value="<?php p(@$task->planned) ?>" alt="<?php print_string('expected', 'techproject') ?>" onchange="update('quoted');" /><?php echo $TIMEUNITS[$project->timeunit] ?>
            <?php helpbutton('costplanned', get_string('planned', 'techproject'), 'techproject', true, false); ?>
        </td>
    </tr>
    <tr valign="top">
    	<td align="right"><b><?php print_string('quoted', 'techproject') ?>:</b></td>
        <td align="left">
            <?php 
                echo "<span id=\"quoted\">".@$task->quoted."</span> ".$project->costunit;
                helpbutton('quoted', get_string('quoted', 'techproject'), 'techproject', true, false); 
            ?>
        </td>
    </tr>
    <tr valign="top">
    	<td align="right"><b><?php  print_string('risk', 'techproject') ?>:</b>
    	</td>
        <td align="left">
    <?php
            $risks = techproject_get_options('risk', $project->id);
            $risksesoptions = array();
            foreach($risks as $aRisk){
                $risksoptions[$aRisk->code] = '['. $aRisk->code . '] ' . $aRisk->label;
            }
            choose_from_menu ($risksoptions, 'risk', @$task->risk, 'choose', '', '0');
            helpbutton('risk', get_string('risk', 'techproject'), 'techproject', true, false);
    ?>
        </td>
    </tr>
    <tr valign="top">
    	<td align="right"><b><?php  print_string('done', 'techproject') ?>:</b></td>
        <td align="left">
            <input type="text" name="done" size="3" value="<?php p(@$task->done) ?>" alt="<?php print_string('done', 'techproject') ?>" /> %
            <?php helpbutton('done', get_string('done', 'techproject'), 'techproject', true, false); ?>
        </td>
    </tr>
    <tr valign="top">
    	<td align="right"><b><?php  print_string('used', 'techproject') ?>:</b></td>
        <td align="left">
            <input type="text" name="used" size="10" value="<?php p(@$task->used) ?>" alt="<?php print_string('used', 'techproject') ?>" onchange="update('spent');" /><?php echo $TIMEUNITS[$project->timeunit] ?>
        </td>
    </tr>
    <tr valign="top">
    	<td align="right"><b><?php  print_string('spent', 'techproject') ?>:</b></td>
        <td align="left">
            <?php 
                echo "<span id=\"spent\">".@$task->spent."</span> ".$project->costunit;
                helpbutton('spent', get_string('spent', 'techproject'), 'techproject', true, false); 
            ?>
        </td>
    </tr>
    <tr valign="top">
        <td align="right"><b><?php print_string('description', 'techproject') ?>:</b><br />
        <font size="1">
        <?php
                helpbutton('writing', get_string('helpwriting'), 'moodle', true, true);
                echo "<br />";
                if ($usehtmleditor) {
                   helpbutton('richtext', get_string('helprichtext'), 'moodle', true, true);
                } else {
                   helpbutton('text', get_string('helptext'), 'moodle', true, true);
                   echo "<br />";
                   emoticonhelpbutton('addtaskform', 'description', 'moodle', true, true);
                   echo "<br />";
                }
        ?>
          <br />
        </font>
        </td>
        <td align="right">
    <?php
           print_textarea($usehtmleditor, 20, 60, 595, 400, 'description', @$task->description);
    
           if ($usehtmleditor) {
               echo '<input type="hidden" name="format" value="'.FORMAT_HTML.'" />';
               $nohtmleditorneeded = false;
           } else {
               echo '<p align="right">';
               helpbutton('textformat', get_string('formattexttype'));
               print_string('formattexttype');
               echo ':&nbsp;';
               choose_from_menu(format_text_menu(), 'format', $defaultformat, '');
               echo '</p>';
           }
    ?>
        </td>
    </tr>
    <tr valign="top">
    	<td align="right"><b><?php print_string('milestone', 'techproject') ?>:</b>
    	</td>
        <td align="left">
    <?php
        $milestones = get_records_select('techproject_milestone', "projectid = {$project->id} AND groupid = {$currentGroupId}", 'ordering ASC', 'id, abstract, ordering');
        $milestonesoptions = array();
        if ($milestones){
            foreach($milestones as $aMilestone){
                $milestonesoptions[$aMilestone->id] = $aMilestone->abstract;
            }
            choose_from_menu ($milestonesoptions, 'milestoneid', 0, get_string('unassigned', 'techproject') );
        }
        else{
            print_string('nomilestones', 'techproject');
        }
        helpbutton('tasktomilestone', get_string('milestone', 'techproject'), 'techproject', true, false);
    ?>
        </td>
    </tr>
    </table>
    <input type="button" name="go_btn" value="<?php print_string('savechanges'); ?>" onclick="senddata()" />
    <input type="button" name="cancel_btn" value="<?php print_string('cancel'); ?>" onclick="cancel()" />
    </form>
    <script type="text/javascript">
    //<![CDATA[
    lockoptions('addtaskform', 'taskstartenable', taskstartitems);
    lockoptions('addtaskform', 'taskendenable', taskenditems);
    //]]>
    </script>
    </center>
    
    <?php
    	}
    
/// Update task form *********************************************************

    	elseif ($work == 'update') {
    	    $taskid = required_param('taskid', PARAM_INT);
    
            // if not a bounce from doupdate get the old record
            if (!isset($task)) $task = get_record('techproject_task', 'id', $taskid);
    		if(!$task){
    			error(print_string('errortask','techproject'));
    		}
    		else {
    ?>
    
    <center>
    <?php print_heading(get_string('updatetask', 'techproject'), 'center'); ?>
    <script type="text/javascript">
    //<![CDATA[
    function senddata(){
        document.forms['updatetaskform'].work.value='doupdate';
        <?php if ($usehtmleditor && $CFG->defaulthtmleditor == 'htmlarea') echo "document.forms['updatetaskform'].onsubmit();\n"; ?>
        document.forms['updatetaskform'].submit();
    }
    
    function cancel(){
        <?php if ($usehtmleditor && $CFG->defaulthtmleditor == 'htmlarea') echo "document.forms['updatetaskform'].onsubmit();\n"; ?>
        document.forms['updatetaskform'].submit();
    }
    
    var taskstartitems = ['taskstartday','taskstartmonth','taskstartyear','taskstarthour', 'taskstartminute'];
    var taskenditems = ['taskendday','taskendmonth','taskendyear','taskendhour', 'taskendminute'];

    function update(elementname){
        if (elementname == 'quoted'){
            spanelm = document.getElementById('quoted');
            spanelm.innerHTML = document.forms['updatetaskform'].costrate.value * document.forms['updatetaskform'].planned.value;
        }
        if (elementname == 'spent'){
            spanelm = document.getElementById('spent');
            spanelm.innerHTML = document.forms['updatetaskform'].costrate.value * document.forms['updatetaskform'].used.value;
        }
    };
    //]]>
    </script>
    <form name="updatetaskform" method="post" action="view.php#tsk<?php p($task->fatherid) ?>">
    <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
    <input type="hidden" name="fatherid" value="<?php echo $task->fatherid; ?>" />
    <input type="hidden" name="work" value="" />
    <input type="hidden" name="taskid" value="<?php p($task->id) ?>" />
    <table>
    <tr valign="top">
    	<td align="right"><b><?php print_string('tasktitle', 'techproject') ?>:</b></td>
        <td align="left">
            <input type="text" name="abstract" size="100%" value="<?php p($task->abstract) ?>" alt="<?php print_string('tasktitle', 'techproject') ?>" />
        </td>
    </tr>
    <tr valign="top">
    	<td align="right"><b><?php print_string('owner', 'techproject') ?>:</b></td>
        <td align="left">
    <?php 
            $groupusers = techproject_get_group_users($project->course, $cm, $currentGroupId);
            $owneroptions = array();
            foreach($groupusers as $anOwner){
                $owneroptions[$anOwner->id] = fullname($anOwner);
            }
            choose_from_menu ($owneroptions, 'owner', $task->owner, get_string('unassigned', 'techproject'));
     ?>
        </td>
    </tr>
    <tr valign="top">
    	<td align="right"><b><?php print_string('assignee', 'techproject') ?>:</b></td>
        <td align="left">
    <?php 
            if($groupusers){
                $assignoptions = array();
                foreach($groupusers as $anAssignee){
                    $assignoptions[$anAssignee->id] = fullname($anAssignee);
                }
                choose_from_menu ($assignoptions, 'assignee', $task->assignee, get_string('unassigned','techproject'));
            }
            else{
               print_string('noassignee', 'techproject');
            }
     ?>
            <input type="hidden" name="oldassignee" value="$task->assignee" />
        </td>
    </tr>
    
    <tr valign="top">
        <td align="right" class="<?php if (isset($controls['taskstartdate'])) echo 'inconsistency'; ?>"><b><?php print_string('taskstartdate', 'techproject') ?>:
                <?php if (isset($controls['taskstartdate'])) echo "<br/><span class=\"inconsistency\">{$controls['taskstartdate']}</span>" ?></b></td>
        <td align="left">
                <input name="taskstartenable" type="checkbox" value="1" alt="<?php print_string('taskstartenable', 'techproject') ?>" onclick="return lockoptions('updatetaskform', 'taskstartenable', taskstartitems)" <?php if ($task->taskstartenable) echo 'checked="checked"' ?> />
            <?php
                if ($task->taskstart == -1) $task->taskstart = time();
                print_date_selector('taskstartday', 'taskstartmonth', 'taskstartyear', $task->taskstart);
                echo "&nbsp;-&nbsp;";
                print_time_selector('taskstarthour', 'taskstartminute', $task->taskstart);
                helpbutton('taskstartdate', get_string('taskstartdate', 'techproject'), 'techproject', true, false);
        ?>
            <input type="hidden" name="htaskstartday"    value="0" />
            <input type="hidden" name="htaskstartmonth"  value="0" />
            <input type="hidden" name="htaskstartyear"   value="0" />
            <input type="hidden" name="htaskstarthour"   value="0" />
            <input type="hidden" name="htaskstartminute" value="0" />
        </td>
    </tr>
    
    <tr valign="top">
        <td align="right" class="<?php if (isset($controls['taskenddate'])) echo 'inconsistency'; ?>"><b><?php print_string('taskenddate', 'techproject') ?>:
                <?php if (isset($controls['taskenddate'])) echo "<br/><span class=\"inconsistency\">{$controls['taskenddate']}</span>" ?></b></td>
        <td align="left">
                <input name="taskendenable" type="checkbox" value="1" alt="<?php print_string('taskendenable', 'techproject') ?>" onclick="return lockoptions('updatetaskform', 'taskendenable', taskenditems)" <?php if ($task->taskendenable) echo 'checked="checked"' ?> />
            <?php
                // fix invalid or undefined dates
                if ($task->taskend == -1){
                    if ($task->milestoneid){
                        $milestone = get_record('techproject_milestone', 'id', $task->milestoneid);
                        if ($milestone->deadline > 0) $task->taskend = $milestone->deadline;
                    }
                }
                if ($task->taskend == -1) $task->taskend = $project->projectend;
    
                print_date_selector('taskendday', 'taskendmonth', 'taskendyear', $task->taskend);
                echo "&nbsp;-&nbsp;";
                print_time_selector('taskendhour', 'taskendminute', $task->taskend);
                helpbutton('taskenddate', get_string('taskenddate', 'techproject'), 'techproject', true, false);
        ?>
            <input type="hidden" name="htaskendday"    value="0" />
            <input type="hidden" name="htaskendmonth"  value="0" />
            <input type="hidden" name="htaskendyear"   value="0" />
            <input type="hidden" name="htaskendhour"   value="0" />
            <input type="hidden" name="htaskendminute" value="0" />
        </td>
    </tr>
    
    <tr valign="top">
    	<td align="right"><b><?php print_string('worktype', 'techproject') ?>:</b>
    	</td>
        <td align="left">
    <?php
            $worktypes = techproject_get_options('worktype', $project->id);
            $worktypeoptions = array();
            foreach($worktypes as $aWorktype){
                $worktypeoptions[$aWorktype->code] = '['. $aWorktype->code . '] ' . $aWorktype->label;
            }
            choose_from_menu ($worktypeoptions, 'worktype', $task->worktype);
            helpbutton('worktype', get_string('worktype', 'techproject'), 'techproject', true, false);
    ?>
        </td>
    </tr>
    <tr valign="top">
    	<td align="right">
    	   <b><?php print_string('status', 'techproject') ?>:</b>
    	</td>
        <td align="left">
    <?php
        $statusses = techproject_get_options('taskstatus', $project->id);
        $statussesoptions = array();
        foreach($statusses as $aStatus){
            $statussesoptions[$aStatus->code] = '['. $aStatus->code . '] ' . $aStatus->label;
        }
        choose_from_menu ($statussesoptions, 'status', $task->status);
        helpbutton('status', get_string('status', 'techproject'), 'techproject', true, false);
    ?>
        </td>
    </tr>
    
    <tr valign="top">
    	<td align="right"><b><?php print_string('costrate', 'techproject') ?>:</b></td>
        <td align="left">
            <input type="text" name="costrate" size="3" value="<?php p($task->costrate) ?>" alt="<?php  print_string('costrate', 'techproject') ?>" onchange="update('quoted');update('spent');" />
        </td>
    </tr>
    <tr valign="top">
    	<td align="right"><b><?php print_string('costplanned', 'techproject') ?>:</b></td>
        <td align="left">
    <?php
        // we can only edit planned indicator if we are a leaf task.
        if (techproject_count_subs('techproject_task', $taskid) == 0){
            echo "<input type=\"text\" name=\"planned\" size=\"3\" value=\"{$task->planned}\" alt=\"".get_string('costplanned', 'techproject')."\"  onchange=\"update('quoted');\" /> ".$TIMEUNITS[$project->timeunit];
        }
        else {
            echo "{$task->planned}<input type=\"hidden\" name=\"planned\" size=\"3\" value=\"{$task->planned}\" /> ".$TIMEUNITS[$project->timeunit]; 
        }
    ?>
            <?php helpbutton('costplanned', get_string('planned', 'techproject'), 'techproject', true, false); ?>
        </td>
    </tr>
    <tr valign="top">
    	<td align="right"><b><?php print_string('quoted', 'techproject') ?>:</b></td>
        <td align="left">
            <?php 
                echo "<span id=\"quoted\">{$task->quoted} ".$project->costunit.'</span>';
                helpbutton('quoted', get_string('quoted', 'techproject'), 'techproject', true, false); 
            ?>
        </td>
    </tr>
    <tr valign="top">
    	<td align="right"><b><?php  print_string('risk', 'techproject') ?>:</b>
    	</td>
        <td align="left">
    <?php
            $risks = techproject_get_options('risk', $project->id);
            $risksesoptions = array();
            foreach($risks as $aRisk){
                $risksoptions[$aRisk->code] = '['. $aRisk->code . '] ' . $aRisk->label;
            }
            choose_from_menu ($risksoptions, 'risk', @$task->risk, 'choose', '', '0');
            helpbutton('risk', get_string('risk', 'techproject'), 'techproject', true, false);
    ?>
        </td>
    </tr>
    <tr valign="top">
    	<td align="right"><b><?php print_string('done', 'techproject') ?>:</b></td>
        <td align="left">
    <?php
        // we can only edit done indicator if we are a leaf task.
        if (techproject_count_subs('techproject_task', $taskid) == 0){
            echo "<input type=\"text\" name=\"done\" size=\"3\" value=\"{$task->done}\" alt=\"".get_string('done', 'techproject')."\" />"; 
        }
        else {
            echo "{$task->done}<input type=\"hidden\" name=\"done\" size=\"3\" value=\"{$task->done}\" />"; 
        }
    ?>
            %
            <?php helpbutton('done', get_string('done', 'techproject'), 'techproject', true, false); ?>
        </td>
    </tr>
    <tr valign="top">
    	<td align="right"><b><?php print_string('used', 'techproject') ?>:</b></td>
        <td align="left">
            <?php
                // we can only edit planned indicator if we are a leaf task.
                if (techproject_count_subs('techproject_task', $taskid) == 0){
                    echo "<input type=\"text\" name=\"used\" size=\"3\" value=\"{$task->used}\" alt=\"".get_string('used', 'techproject')."\"  onchange=\"update('spent');\" /> ".$TIMEUNITS[$project->timeunit];
                }
                else {
                    echo "{$task->used}<input type=\"hidden\" name=\"used\" value=\"{$task->used}\" /> ".$TIMEUNITS[$project->timeunit]; 
                }
                helpbutton('used', get_string('used', 'techproject'), 'techproject', true, false); 
            ?>
        </td>
    </tr>
    <tr valign="top">
    	<td align="right"><b><?php print_string('spent', 'techproject') ?>:</b></td>
        <td align="left">
            <?php 
                echo "<span id=\"spent\">{$task->spent} ".$project->costunit.'</span>';
                helpbutton('spent', get_string('spent', 'techproject'), 'techproject', true, false); 
            ?>
        </td>
    </tr>
    <tr valign="top">
        <td align="right"><b><?php print_string('description', 'techproject') ?>:</b><br />
        <font size="1">
         <?php
            helpbutton('writing', get_string('helpwriting'), 'moodle', true, true);
            echo "<br />";
            if ($usehtmleditor) {
               helpbutton('richtext', get_string('helprichtext'), 'moodle', true, true);
            } else {
               helpbutton('text', get_string('helptext'), 'moodle', true, true);
               echo "<br />";
               emoticonhelpbutton('updatetaskform', 'description', 'moodle', true, true);
               echo "<br />";
            }
          ?>
          <br />
        </font>
        </td>
        <td>
        <?php
           print_textarea($usehtmleditor, 20, 60, 595, 400, 'description', $task->description);
    
           if ($usehtmleditor) {
               echo '<input type="hidden" name="format" value="'.FORMAT_HTML.'" />';
               $nohtmleditorneeded = false;
           } else {
               echo '<p align="right">';
               helpbutton('textformat', get_string('formattexttype'));
               print_string('formattexttype');
               echo ':&nbsp;';
               if (empty($task->format)) {
                   $task->format = $defaultformat;
               }
               choose_from_menu(format_text_menu(), 'format', $task->format, '');
               echo '</p>';
           }
        ?>
        </td>
    </tr>
    <tr valign="top">
    	<td align="right"><b><?php print_string('milestone', 'techproject') ?>:</b>
    	</td>
        <td align="left">
    <?php
        $milestones = get_records_select('techproject_milestone', "projectid = {$project->id} AND groupid = {$currentGroupId}", 'ordering ASC', 'id, abstract, ordering');
        $milestonesoptions = array();
        if ($milestones){
            foreach($milestones as $aMilestone){
                $milestonesoptions[$aMilestone->id] = format_string($aMilestone->abstract);
            }
            choose_from_menu ($milestonesoptions, 'milestoneid', $task->milestoneid, get_string('unassigned', 'techproject') );
        }
        else{
            print_string('nomilestones', 'techproject');
        }
        helpbutton('tasktomilestone', get_string('milestone', 'techproject'), 'techproject', true, false);
    ?>
        </td>
    </tr>
<?php
if (@$project->projectusesspecs){
?>
    <tr valign="top">
    	<td align="right"><b><?php print_string('tasktospec', 'techproject') ?>:</b>
    	</td>
        <td align="left">
            <select name="tasktospec[]" multiple="multiple" size="6" style="width:100%">
    <?php
            $specifications = techproject_get_tree_options('techproject_specification', $project->id, $currentGroupId);
            $selection = get_records_select_menu('techproject_task_to_spec', "taskid = $taskid", '', 'specid, taskid');
            foreach($specifications as $aSpec){
                $aSpec->abstract = format_string($aSpec->abstract);
                $selected = ($selection && in_array($aSpec->id, array_keys($selection))) ? "selected=\"selected\"" : '' ;
                echo "<option value=\"{$aSpec->id}\" $selected>{$aSpec->ordering} - {$aSpec->abstract}</option>";
            }
    ?>
            </select>
            <?php helpbutton('tasktospecification', get_string('tasktospec', 'techproject'), 'techproject', true, false); ?>
        </td>
    </tr>
<?php
}
?>
    <tr valign="top">
    	<td align="right"><b><?php print_string('taskdependency', 'techproject') ?>:</b>
    	</td>
        <td align="left">
            <select name="taskdependency[]" multiple="multiple" size="6" style="width:100%">
    <?php
            $tasks = techproject_get_tree_options('techproject_task', $project->id, $currentGroupId);
            $selection = get_records_select_menu('techproject_task_dependency', "slave = $taskid", '', 'master,slave');
            foreach($tasks as $aTask){
                $aTask->abstract = format_string($aTask->abstract);
                if ($aTask->id == $taskid) continue;
                if ($aTask->id == $task->fatherid) continue;
                if (techproject_check_task_circularity($taskid, $taskid)) continue;
                $selected = ($selection && in_array($aTask->id, array_keys($selection))) ? "selected=\"selected\"" : '' ;
                echo "<option value=\"{$aTask->id}\" {$selected}>{$aTask->ordering} - {$aTask->abstract}</option>";
            }
    ?>
            </select>
            <?php helpbutton('taskdependency', get_string('taskdependency', 'techproject'), 'techproject', true, false); ?>
        </td>
    </tr>
<?php
if (@$project->projectusesdelivs){
?>
    <tr valign="top">
    	<td align="right"><b><?php print_string('tasktodeliv', 'techproject') ?>:</b>
    	</td>
        <td align="left">
            <select name="tasktodeliv[]" multiple="multiple" size="6" style="width:100%">
    <?php
            $deliverables = techproject_get_tree_options('techproject_deliverable', $project->id, $currentGroupId);
            $selection = get_records_select_menu('techproject_task_to_deliv', "taskid = $taskid", '', 'delivid, taskid');
            foreach($deliverables as $aDeliv){
                $aDeliv->abstract = format_string($aDeliv->abstract);
                $selected = ($selection && in_array($aDeliv->id, array_keys($selection))) ? "selected=\"selected\"" : '' ;
                echo "<option value=\"{$aDeliv->id}\" $selected>{$aDeliv->ordering} - {$aDeliv->abstract}</option>";
            }
    ?>
            </select>
            <?php helpbutton('tasktodeliv', get_string('tasktodeliv', 'techproject'), 'techproject', true, false); ?>
        </td>
    </tr>
<?php
}
?>
    </table>
    <input type="button" name="go_btn" value="<?php print_string('savechanges'); ?>" onclick="senddata();" />
    <input type="button" name="cancel_btn" value="<?php print_string('cancel'); ?>" onclick="cancel();" />
    </form>
    <script type="text/javascript">
    //<![CDATA[
    <?php
        if (!$task->taskstartenable) echo "lockoptions('updatetaskform','taskstartenable', taskstartitems);\n";
        if (!$task->taskendenable) echo "lockoptions('updatetaskform','taskendenable', taskenditems);\n";
    ?>
    //]]>
    </script>
    </center>
    
    <?php
    		}
    	}

/// Delete form *********************************************************

    	elseif ($work == 'delete') {
    	    $taskid = required_param('taskid', PARAM_INT);
    ?>
    
    <center>
    <?php print_heading(get_string('deletetask','techproject'), 'center'); ?>
    <script type="text/javascript">
    //<![CDATA[
    function senddata(){
        document.forms['deletetaskform'].work.value='dodelete';
        document.forms['deletetaskform'].submit();
    }
    
    function cancel(){
        document.forms['deletetaskform'].submit();
    }
    //]]>
    </script>
    <form name="deletetaskform" method="get" action="view.php">
    <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
    <input type="hidden" name="work" value="" />
    <input type="hidden" name="taskid" value="<?php p($taskid) ?>" />
    <input type="button" name="go_btn" value="<?php print_string('delete') ?>" onclick="senddata();" />
    <input type="button" name="cancel_btn" value="<?php print_string('cancel') ?>" onclick="cancel();" />
    </form>
    </center>
    
    <?php
    	}

/// Group operation form *********************************************************

    	elseif ($work == "groupcmd") {
    	    $ids = required_param('ids', PARAM_INT);
    	    $cmd = required_param('cmd', PARAM_ALPHA);
    ?>
    
    <center>
    <?php 
    print_heading(get_string('groupoperations', 'techproject'), 'center');
    print_heading(get_string("group$cmd", 'techproject'), 'center', 'h3');
    if ($cmd == 'copy' || $cmd == 'move')
        print_simple_box(get_string('groupcopymovewarning', 'techproject'), 'center', '70%'); 
    ?>
    <script type="text/javascript">
    //<![CDATA[
    function senddata(cmd){
        document.forms['groupopform'].work.value="do" + cmd;
        document.forms['groupopform'].submit();
    }
    
    function cancel(){
        document.forms['groupopform'].submit();
    }
    //]]>
    </script>
    <form name="groupopform" method="post" action="view.php">
    <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
    <input type="hidden" name="work" value="" />
    <?php
            foreach($ids as $anId){
                echo "<input type=\"hidden\" name=\"ids[]\" value=\"{$anId}\" />\n";
            }
			
			// special command post options
            if (($cmd == 'move')||($cmd == 'copy')){
                echo get_string('to', 'techproject');
                if (@$project->projectusesrequs) $options['requs'] = get_string('requirements', 'techproject');
                if (@$project->projectusesspecs) $options['specs'] = get_string('specifications', 'techproject');
                if (@$project->projectusesspecs) $options['specswb'] = get_string('specificationswithbindings', 'techproject');
                if (@$project->projectusesdelivs) $options['deliv'] = get_string('deliverables', 'techproject');
                if (@$project->projectusesdelivs) $options['delivwb'] = get_string('deliverableswithbindings', 'techproject');
                choose_from_menu($options, 'to', '', 'choose');
            }
            
            if ($cmd == 'applytemplate'){
				echo '<input type="checkbox" name="applyroot" value="1" /> '.get_string('alsoapplyroot', 'techproject');
				echo '<br/>';
            }
			echo '<br/>';
    ?>
    <input type="button" name="go_btn" value="<?php print_string('continue') ?>" onclick="senddata('<?php p($cmd) ?>')" />
    <input type="button" name="cancel_btn" value="<?php print_string('cancel') ?>" onclick="cancel()" />
    </form>
    </center>
    
    <?php
    	} else {
    ?>
    <script type="text/javascript">
    //<![CDATA[
    function sendgroupdata(){
        document.forms['groupopform'].submit();
    }
    //]]>
    </script>
    <form name="groupopform" method="post" action="view.php">
    <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
    <input type="hidden" name="work" value="groupcmd" />
    <?php
            if ($USER->editmode == 'on' && has_capability('mod/techproject:changetasks', $context)) {
        		echo "<br/><a href='view.php?id={$cm->id}&amp;work=add&amp;fatherid=0'>".get_string('addroottask','techproject')."</a> ";
    	    }
    		techproject_print_tasks($project, $currentGroupId, 0, $cm->id);
            if ($USER->editmode == 'on' && has_capability('mod/techproject:changetasks', $context)) {
	        	echo "<br/><a href='javascript:selectall(document.forms[\"groupopform\"])'>".get_string('selectall','techproject')."</a>&nbsp;";
	        	echo "<a href='javascript:unselectall(document.forms[\"groupopform\"])'>".get_string('unselectall','techproject')."</a>&nbsp;";
        		echo "<a href='view.php?id={$cm->id}&amp;work=add&amp;fatherid=0'>".get_string('addroottask','techproject')."</a> ";
        		if (@$SESSION->techproject->tasktemplateid){
	        		techproject_print_group_commands('markasdone,fullfill,applytemplate');
	        	} else {
	        		techproject_print_group_commands('markasdone,fullfill');
	        	}
        		echo "<br/><a href='view.php?id={$cm->id}&amp;work=recalc'>".get_string('recalculate','techproject')."</a> ";
    	    }
    ?>
    </form>
<?php
	}
?>