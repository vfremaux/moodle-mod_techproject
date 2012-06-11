<?php

    /**
    * Project : Technical Project Manager (IEEE like)
    *
    * Milestone operations.
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
    * @param object $milestone form object to be checked
    * @return a control hash array telling error statuses
    */
    function checkConstraints($project, $milestone){
        global $CFG;
        $control = NULL;
    
        switch($project->timeunit){
            case HOURS : $plannedtime = 3600 ; break ;
            case HALFDAY : $plannedtime = 3600 * 12 ; break ;
            case DAY : $plannedtime = 3600 * 24 ; break ;
            default : $plannedtime = 0;
        }
        // checking too soon task
        
        $query = "
           SELECT
              id,
              abstract,
              MAX(taskend) as latest
           FROM
              {$CFG->prefix}techproject_task
           WHERE
              milestoneid = {$milestone->id}
           GROUP BY
              milestoneid
        ";
        $latestTask = get_record_sql($query);
        if($latestTask && $milestone->deadline < $latestTask->latest){
            $control['milestonedeadline'] = get_string('assignedtaskendsafter','techproject') . '<br/>' . userdate($latestTask->latest);
        }
        return $control;
    }
    
    $usehtmleditor = can_use_html_editor();
    
/// Controller

    if ($work == 'new') {
        // get expected params
    	$milestone->groupid = $currentGroupId;
    	$milestone->projectid = $project->id;
        $milestone->description = addslashes(required_param('description', PARAM_CLEANHTML));
        $milestone->format = required_param('format', PARAM_INT);
        $milestone->abstract = required_param('abstract', PARAM_TEXT);
        $deadlineyear = optional_param('deadlineyear', 0, PARAM_INT);
        $deadlinemonth = optional_param('deadlinemonth', 0, PARAM_INT);
        $deadlineday = optional_param('deadlineday', 0, PARAM_INT);
        $deadlinehour = optional_param('deadlinehour', 0, PARAM_INT);
        $deadlineminute = optional_param('deadlineminute', 0, PARAM_INT);
        $deadline = 0 + make_timestamp($deadlineyear, 
            $deadlinemonth, $deadlineday, $deadlinehour, 
            $deadlineminute);
    	$milestone->deadline = ($deadline >= 0 ) ? $deadline : 0 ;
        $milestone->deadlineenable = optional_param('deadlineenable', 0, PARAM_INT);
		$milestone->userid = $USER->id;
		$milestone->created = time();
		$milestone->modified = time();
		$milestone->lastuserid = $USER->id;

        if ($milestone->abstract != ''){
            // getting old record if any        
        	$query = "
        	    SELECT 
        	        MAX(ordering) as position
        	    FROM 
        	        {$CFG->prefix}techproject_milestone
        	    WHERE 
        	        groupid = {$currentGroupId} AND 
        	        projectid = {$project->id} 
        	";
        
        	if(!$result = get_record_sql($query)){
        		$result->position = 0;
        	}
        
        	$milestone->ordering = $result->position + 1;
    
            $returnid = insert_record('techproject_milestone', $milestone);
            add_to_log($course->id, 'techproject', 'changemilestone', "view.php?id=$cm->id&view=milestone&group={$currentGroupId}", 'add', $cm->id);
        }

   		// if notifications allowed notify project managers
   		if( $project->allownotifications){
   		    $milestone->id = $returnid;
            techproject_notify_new_milestone($project, $cm->id, $milestone, $currentGroupId);
       	}
    } elseif ($work == 'doupdate') {
        // get expected params
        $milestone->id = required_param('milestoneid', PARAM_INT);
        $milestone->description = required_param('description', PARAM_CLEAN);
        $milestone->format = required_param('format', PARAM_INT);
        $milestone->abstract = required_param('abstract', PARAM_CLEAN);
        $deadlineyear = optional_param('deadlineyear', 0, PARAM_INT);
        $deadlinemonth = optional_param('deadlinemonth', 0, PARAM_INT);
        $deadlineday = optional_param('deadlineday', 0, PARAM_INT);
        $deadlinehour = optional_param('deadlinehour', 0, PARAM_INT);
        $deadlineminute = optional_param('deadlineminute', 0, PARAM_INT);
        $deadline = 0 + make_timestamp($deadlineyear, 
            $deadlinemonth, $deadlineday, $deadlinehour, 
            $deadlineminute);
    	$milestone->deadline = ($deadline >= 0 ) ? $deadline : 0 ;
        $milestone->deadlineenable = optional_param('deadlineenable', 0, PARAM_INT);
		$milestone->modified = time();
		$milestone->lastuserid = $USER->id;

        $controls = checkConstraints($project, $milestone);
        if (!$controls){
            if ($milestone->abstract != ''){
        	    $res = update_record('techproject_milestone', $milestone);
                add_to_log($course->id, 'techproject', 'changemilestone', "view.php?id=$cm->id&view=milestone&group={$currentGroupId}", 'update', $cm->id);
            }
        } else{
            $work = 'update';
        }
    } elseif ($work == 'dodelete') {
        $milestoneid = required_param('milestoneid', PARAM_INT);
    	techproject_tree_delete($milestoneid, 'techproject_milestone', 0); // uses list option switch
    	
    	// cleans up any assigned task
    	$query = "
    	   UPDATE
    	      {$CFG->prefix}techproject_task
    	   SET
    	      milestoneid = NULL
    	   WHERE
    	      milestoneid = $milestoneid
    	";
    	execute_sql($query);

    	// cleans up any assigned deliverable
    	$query = "
    	   UPDATE
    	      {$CFG->prefix}techproject_deliverable
    	   SET
    	      milestoneid = NULL
    	   WHERE
    	      milestoneid = $milestoneid
    	";
    	execute_sql($query);
        add_to_log($course->id, 'techproject', 'changemilestone', "view.php?id=$cm->id&view=milestone&group={$currentGroupId}", 'delete', $cm->id);
    } elseif ($work == 'doclearall') {
        // delete all records. POWERFUL AND DANGEROUS COMMAND.
		delete_records('techproject_milestone', 'projectid', $project->id);

        // do reset all milestone assignation in project
    	$query = "
    	   UPDATE
    	      {$CFG->prefix}techproject_task
    	   SET
    	      milestoneid = NULL
    	   WHERE
    	      projectid = {$project->id} AND
    	      groupid = {$currentGroupId}
    	";
    	execute_sql($query);

        // do reset all milestone assignation in project
    	$query = "
    	   UPDATE
    	      {$CFG->prefix}techproject_deliverable
    	   SET
    	      milestoneid = NULL
    	   WHERE
    	      projectid = {$project->id} AND
    	      groupid = {$currentGroupId}
    	";
    	execute_sql($query);
        add_to_log($course->id, 'techproject', 'changemilestones', "view.php?id=$cm->id&view=milestone&group={$currentGroupId}", 'clear', $cm->id);
	} elseif ($work == 'up') {
        $milestoneid = required_param('milestoneid', PARAM_INT);
    	techproject_tree_up($project, $currentGroupId,$milestoneid, 'techproject_milestone', 0);
    } elseif ($work == 'down') {
        $milestoneid = required_param('milestoneid', PARAM_INT);
    	techproject_tree_down($project, $currentGroupId,$milestoneid, 'techproject_milestone', 0);
    } elseif ($work == 'sortbydate'){
        $milestones = array_values(get_records_select('techproject_milestone', "projectid = {$project->id} AND groupid = {$currentGroupId}"));

        function sortByDate($a, $b){
            if ($a->deadline == $b->deadline) return 0;
            return ($a->deadline > $b->deadline) ? 1 : -1 ; 
        }

        usort($milestones, 'sortByDate');
        // reorders in memory and stores back
        $ordering = 1;
        foreach($milestones as $aMilestone){
            $aMilestone->ordering = $ordering;
            $aMilestone = addslashes_recursive($aMilestone);
            update_record('techproject_milestone', $aMilestone);
            $ordering++;
        }
    }
    
/// Add milestone form *********************************************************

    if ($work == 'add'){
    ?>
    
    <center>
    <?php print_heading(get_string('addmilestone','techproject'), 'center'); ?>
    <script type="text/javascript">
    
    function senddata(){
        document.addmilestoneform.work.value='new';
        <?php if ($usehtmleditor && @$CFG->defaulthtmleditor == 'htmlarea') echo "document.addmilestoneform.onsubmit();\n"; ?>
        document.addmilestoneform.submit();
    }
    
    function cancel(){
        <?php if ($usehtmleditor && @$CFG->defaulthtmleditor == 'htmlarea') echo "document.addmilestoneform.onsubmit();\n"; ?>
        document.addmilestoneform.submit();
    }
    
    var deadlineitems = ['deadlineday', 'deadlinemonth', 'deadlineyear', 'deadlinehour', 'deadlineminute'];
    </script>
    <form name="addmilestoneform" method="post" action="view.php">
    <input type="hidden" name="work" value="" />
    <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
    <table>
    <tr valign="top">
    	<td align="right"><b><?php print_string('milestonetitle', 'techproject') ?>:</b></td>
        <td align="left">
            <input type="text" name="abstract" size="100%" value="" alt="<?php  print_string('milestonetitle', 'techproject') ?>" />
        </td>
    </tr>
    <tr valign="top">
        <td align="right"><b><?php print_string('milestonedeadline', 'techproject') ?>:
            <?php if (isset($controls['milestonedeadline'])) echo "<br/><span class=\"inconsistency\">{$controls['milestonedeadline']}</span>" ?></b></td>
        <td align="left">
                <input name="deadlineenable" type="checkbox" value="1" alt="<?php print_string('milestonedeadlineenable', 'techproject') ?>" onclick="return lockoptions('addmilestoneform', 'deadlineenable', deadlineitems);" />
            <?php
                print_date_selector('deadlineday', 'deadlinemonth', 'deadlineyear', $project->projectend);
                echo "&nbsp;-&nbsp;";
                print_time_selector('deadlinehour', 'deadlineminute', $project->projectend);
                helpbutton('milestonedeadline', get_string('milestonedeadline', 'techproject'), 'techproject');
        ?>
            <input type="hidden" name="hdeadlineday"    value="0" />
            <input type="hidden" name="hdeadlinemonth"  value="0" />
            <input type="hidden" name="hdeadlineyear"   value="0" />
            <input type="hidden" name="hdeadlinehour"   value="0" />
            <input type="hidden" name="hdeadlineminute" value="0" />
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
               emoticonhelpbutton('addmilestoneform', 'description', 'moodle', true, true);
               echo "<br />";
            }
          ?>
          <br />
        </font>
        </td>
        <td align="right">
        <?php
           print_textarea($usehtmleditor, 20, 60, 595, 400, 'description', '');
    
           if ($usehtmleditor) {
               $nohtmleditorneeded = false;
               echo '<input type="hidden" name="format" value="'.FORMAT_HTML.'" />';
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
    </table>
    <input type="button" name="go_btn" value="<?php  print_string('savechanges') ?>" onclick="senddata();" />
    <input type="button" name="cancel_btn" value="<?php print_string('cancel') ?>" onclick="cancel()" />
    </form>
    <script type="text/javascript">
    lockoptions('addmilestoneform','deadlineenable', deadlineitems);
    </script></center>
    
    <?php
    }
    
/// Milestone Update Form *********************************************************

    elseif ($work == 'update') {
   	    $milestoneid = required_param('milestoneid', PARAM_INT);
   
   		$query = "
   		    SELECT 
   		        *
   		    FROM 
   		        {$CFG->prefix}techproject_milestone
   		    WHERE 
   		        id = $milestoneid
   		";
   		
   		if(! $milestone = get_record_sql($query)){
   			print_string('errormilestone','techproject');
   		}
   		else {
    ?>
    
    <center>
    <?php  print_heading(get_string('updatemilestone','techproject'), 'center'); ?>
    <script type="text/javascript">
    
    function senddata(){
        document.updatemilestoneform.work.value='doupdate';
        <?php if ($usehtmleditor && @$CFG->defaulthtmleditor == 'htmlarea') echo "document.updatemilestoneform.onsubmit();\n"; ?>
        document.updatemilestoneform.submit();
    }
    
    function cancel(){
        <?php if ($usehtmleditor && @$CFG->defaulthtmleditor == 'htmlarea') echo "document.updatemilestoneform.onsubmit();\n"; ?>
        document.updatemilestoneform.submit();
    }
    
    var deadlineitems = ['deadlineday','deadlinemonth','deadlineyear','deadlinehour', 'deadlineminute'];
    </script>
    <form name="updatemilestoneform" method="post" action="view.php#mile<?php p($milestoneid) ?>">
    <input type="hidden" name="work" value="" />
    <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
    <input type="hidden" name="milestoneid" value="<?php  p($milestoneid) ?>" />
    <table>
    
    <tr valign="top">
    	<td align="right"><b><?php  print_string('milestonetitle', 'techproject') ?>:</b></td>
        <td align="left">
            <input type="text" name="abstract" size="100%" value="<?php p($milestone->abstract) ?>" alt="<?php  print_string('milestonetitle', 'techproject') ?>" />
        </td>
    </tr>
    
    <tr valign="top">
        <td align="right"><b><?php print_string('milestonedeadline', 'techproject') ?>:
            <?php if (isset($controls['milestonedeadline'])) echo "<br/><span class=\"inconsistency\">{$controls['milestonedeadline']}</span>" ?></b></td>
        <td align="left">
                <input name="deadlineenable" type="checkbox" value="1" alt="<?php print_string('milestonedeadlineenable', 'techproject') ?>" onclick="return lockoptions('updatemilestoneform', 'deadlineenable', deadlineitems)" <?php if ($milestone->deadlineenable) echo 'checked="checked"' ?> />
            <?php
                print_date_selector('deadlineday', 'deadlinemonth', 'deadlineyear', $milestone->deadline);
                echo "&nbsp;-&nbsp;";
                print_time_selector('deadlinehour', 'deadlineminute', $milestone->deadline);
                helpbutton('milestonedeadline', get_string('milestonedeadline', 'techproject'), 'techproject');
        ?>
            <input type="hidden" name="hdeadlineday"    value="0" />
            <input type="hidden" name="hdeadlinemonth"  value="0" />
            <input type="hidden" name="hdeadlineyear"   value="0" />
            <input type="hidden" name="hdeadlinehour"   value="0" />
            <input type="hidden" name="hdeadlineminute" value="0" />
        </td>
    </tr>
    
    <tr valign="top">
        <td align="right"><b><?php  print_string('description', 'techproject') ?>:</b><br />
        <font size="1">
         <?php
            helpbutton("writing", get_string('helpwriting'), 'moodle', true, true);
            echo "<br />";
            if ($usehtmleditor) {
               helpbutton("richtext", get_string("helprichtext"), "moodle", true, true);
            } else {
               helpbutton("text", get_string('helptext'), 'moodle', true, true);
               echo "<br />";
               emoticonhelpbutton('updatemilestoneform', 'description', 'moodle', true, true);
               echo "<br />";
            }
          ?>
          <br />
        </font>
        </td>
        <td>
        <?php
           print_textarea($usehtmleditor, 20, 60, 595, 400, 'description', $milestone->description);
    
           if ($usehtmleditor) {
               $nohtmleditorneeded = false;
               echo '<input type="hidden" name="format" value="'.FORMAT_HTML.'" />';
           } else {
               echo '<p align="right">';
               helpbutton('textformat', get_string('formattexttype'));
               print_string('formattexttype');
               echo ':&nbsp;';
               if (empty($milestone->format)) {
                   $milestone->format = $defaultformat;
               }
               choose_from_menu(format_text_menu(), "format", $milestone->format, "");
               echo '</p>';
           }
        ?>
        </td>
    </tr>
    </table>
    <input type="button" name="go_btn" value="<?php  print_string('savechanges') ?>" onclick="senddata();" />
    <input type="button" name="cancel_btn" value="<?php  print_string('cancel') ?>" onclick="cancel();"  />
    </form>
    <script type="text/javascript">
    <?php
        if (!$milestone->deadlineenable) echo "lockoptions('updatemilestoneform','deadlineenable', deadlineitems);";
    ?>
    </script></center>
    
    <?php
   		}

/// Milestone Delete Form *********************************************************

    } elseif ($work == 'delete') {
   	    $milestoneid = required_param('milestoneid', PARAM_INT);
   ?>
    
    <center>
    <?php 
        print_heading(get_string('deletemilestone','techproject'), 'center'); 
        $milestone = get_record('techproject_milestone', 'id', $milestoneid);
        print_heading_block($milestone->id . " " . $milestone->abstract);
    ?>
    
    <script type="text/javascript">
    
    function senddata(){
        document.deletemilestoneform.work.value='dodelete';
        document.deletemilestoneform.submit();
    }
    
    function cancel(){
        document.deletemilestoneform.submit();
    }
    
    </script>
    <form name="deletemilestoneform" method="post" action="view.php">
    <input type="hidden" name="work" value="" />
    <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
    <input type="hidden" name="milestoneid" value="<?php  p($milestoneid) ?>" />
    <input type="button" name="go_btn" value="<?php print_string('delete') ?>"  onclick="senddata();"/>
    <input type="button" name="cancel_btn" value="<?php print_string('cancel') ?>" onclick="cancel();" />
    </form>
    </center>
    
    <?php

/// Clear all *********************************************************

    } elseif ($work == 'clearall') {
    ?>
    
    <center>
    <?php 
            print_heading(get_string('clearallmilestones','techproject'), 'center'); 
    ?>
    
    <?php print_simple_box (get_string('clearwarning','techproject'), $align = 'center', $width = '80%', $color = '#FF3030', $padding = 5, $class = 'generalbox'); ?>
    <script type="text/javascript">
    
    function senddata(){
        document.clearmilestoneform.work.value='doclearall';
        document.clearmilestoneform.submit();
    }
    
    function cancel(){
        document.clearmilestoneform.submit();
    }
    
    </script>
    <form name="clearmilestoneform" method="post" action="view.php">
    <input type="hidden" name="work" value="" />
    <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
    <input type="button" name="go_btn" value="<?php print_string('yes') ?>"  onclick="senddata();"/>
    <input type="button" name="cancel_btn" value="<?php print_string('no') ?>" onclick="cancel();" />
    </form>
    </center>
    <?php
    } else {
   		techproject_print_milestones($project, $currentGroupId, NULL, $cm->id);
           if ($USER->editmode == 'on' && (isteacher($project->course) || has_capability('mod/techproject:changemiles', $context))) {
       		echo "<br/><a href='view.php?id={$cm->id}&amp;work=add'>".get_string('addmilestone','techproject')."</a>";
       		echo " - <a href='view.php?id={$cm->id}&amp;work=clearall'>".get_string('clearall','techproject')."</a>";
       		echo " - <a href='view.php?id={$cm->id}&amp;work=sortbydate'>".get_string('sortbydate','techproject')."</a>";
       	}
   	}
    
?>