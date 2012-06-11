<?php

    /**
    * Project : Technical Project Manager (IEEE like)
    *
    * Requirements operations.
    *
    * @package mod-techproject
    * @category mod
    * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
    * @date 2008/03/03
    * @version phase1
    * @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
    * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
    */

    $usehtmleditor = can_use_html_editor();
    $defaultformat = FORMAT_MOODLE;

/// Controller

	if ($work == 'new') {
		$requirement->groupid = $currentGroupId;
		$requirement->projectid = $project->id;
		$requirement->abstract = required_param('abstract', PARAM_TEXT);
		$requirement->description = addslashes(required_param('description', PARAM_CLEANHTML));
        $requirement->format = required_param('format', PARAM_INT);
		$requirement->fatherid = required_param('fatherid', PARAM_INT);
		$requirement->strength = required_param('strength', PARAM_CLEAN);
		$requirement->heaviness = required_param('heaviness', PARAM_CLEAN);
		$requirement->userid = $USER->id;
		$requirement->created = time();
		$requirement->modified = time();
		$requirement->lastuserid = $USER->id;

        if(!empty($requirement->abstract)){
    		$requirement->ordering = techproject_tree_get_max_ordering($project->id, $currentGroupId, 'techproject_requirement', true, $requirement->fatherid) + 1;
    		$requirement->id = insert_record('techproject_requirement', $requirement);
            add_to_log($course->id, 'techproject', 'changerequirement', "view.php?id={$cm->id}&amp;view=requirements&amp;group={$currentGroupId}", 'add', $cm->id);
    	}

   		// if notifications allowed notify project managers
   		if( $project->allownotifications){
            techproject_notify_new_requirement($project, $cm->id, $requirement, $currentGroupId);
       	}
	}
	elseif ($work == 'doupdate') {
		$requirement->id = required_param('requid', PARAM_INT);
		$requirement->abstract = required_param('abstract', PARAM_TEXT);
		$requirement->description = addslashes(required_param('description', PARAM_CLEANHTML));
        $requirement->format = required_param('format', PARAM_INT);
		$requirement->strength = required_param('strength', PARAM_CLEAN);
		$requirement->heaviness = required_param('heaviness', PARAM_CLEAN);
		$requirement->modified = time();
		$requirement->lastuserid = $USER->id;

        if(!empty($requirement->abstract)){
    		$res = update_record('techproject_requirement', $requirement);
            add_to_log($course->id, 'techproject', 'changerequirement', "view.php?id={$cm->id}&amp;view=requirements&amp;group={$currentGroupId}", 'update', $cm->id);
        }
	}
	elseif ($work == 'dodelete') {
		$requid = required_param('requid', PARAM_INT);
		techproject_tree_delete($requid, 'techproject_requirement');

        // delete all related records
		delete_records('techproject_spec_to_req', 'reqid', $requid);
        add_to_log($course->id, 'techproject', 'changerequirement', "view.php?id={$cm->id}&amp;view=requirements&amp;group={$currentGroupId}", 'delete', $cm->id);
	}
	elseif ($work == 'domove' || $work == 'docopy') {
		$ids = required_param('ids', PARAM_INT);
		$to = required_param('to', PARAM_ALPHA);
		$autobind = false;
		$bindtable = '';
		switch($to){
		    case 'specs' :
		    	$table2 = 'techproject_specification'; 
		    	$redir = 'specification'; 
		    	$autobind = false;
		    	break;
		    case 'specswb' :
		    	$table2 = 'techproject_specification'; 
		    	$redir = 'specification'; 
		    	$autobind = true;
		    	$bindtable = 'techproject_spec_to_req';
		    	break;
		    case 'tasks' : 
		    	$table2 = 'techproject_task'; 
		    	$redir = 'task'; 
		    	break;
		    case 'deliv' : 
		    	$table2 = 'techproject_deliverable'; 
		    	$redir = 'deliverable'; 
		    	break;
		    default:
		    	error('Bad copy case', $CFG->wwwroot."/mod/techproject/view.php?id=$cm->id");
		}
		techproject_tree_copy_set($ids, 'techproject_requirement', $table2, 'description,format,abstract,projectid,groupid,ordering', $autobind, $bindtable);
        add_to_log($course->id, 'techproject', "change{$redir}", "view.php?id={$cm->id}&amp;view={$redir}s&amp;group={$currentGroupId}", 'delete', $cm->id);
		if ($work == 'domove'){
		    // bounce to deleteitems
		    $work = 'dodeleteitems';
		    $withredirect = 1;
		} else {
		    redirect("{$CFG->wwwroot}/mod/techproject/view.php?id={$cm->id}&amp;view={$redir}s", get_string('redirectingtoview', 'techproject') . ' : ' . get_string($redir, 'techproject'));
	    }
	}
	if ($work == 'dodeleteitems') {
		$ids = required_param('ids', PARAM_INT);
		foreach($ids as $anItem){

    	    // save record for further cleanups and propagation
    	    $oldRecord = get_record('techproject_requirement', 'id', $anItem);
		    $childs = get_records('techproject_requirement', 'fatherid', $anItem);
		    
		    // update fatherid in childs 
		    $query = "
		        UPDATE
		            {$CFG->prefix}techproject_requirement
		        SET
		            fatherid = $oldRecord->fatherid
		        WHERE
		            fatherid = $anItem
		    ";
		    execute_sql($query);

            // delete record for this item
    		delete_records('techproject_requirement', 'id', $anItem);
    
            // delete all related records for this item
    		delete_records('techproject_spec_to_req', 'projectid', $project->id, 'groupid', $currentGroupId, 'reqid', $anItem);
    	}
        add_to_log($course->id, 'techproject', 'deleterequirement', "view.php?id={$cm->id}&amp;view=requirements&amp;group={$currentGroupId}", 'deleteItems', $cm->id);
    	if (isset($withredirect) && $withredirect){
		    redirect("{$CFG->wwwroot}/mod/techproject/view.php?id={$cm->id}&amp;view={$redir}s", get_string('redirectingtoview', 'techproject') . ' : ' . get_string($redir, 'techproject'));
		}
	}
	elseif ($work == 'doclearall') {
        // delete all records. POWERFUL AND DANGEROUS COMMAND.
		delete_records('techproject_requirement', 'projectid', $project->id, 'groupid', $currentGroupId);
		delete_records('techproject_spec_to_req', 'projectid', $project->id, 'groupid', $currentGroupId);
        add_to_log($course->id, 'techproject', 'changerequirement', "view.php?id={$cm->id}&amp;view=requirements&amp;group={$currentGroupId}", 'clear', $cm->id);
	}
	elseif ($work == 'doexport') {
	    $ids = required_param('ids', PARAM_INT);
	    $idlist = implode("','", $ids);
	    $select = "
	       id IN ('$idlist')	       
	    ";
	    $requirements = get_records_select('techproject_requirement', $select);
	    $strengthes = get_records_select('techproject_qualifier', " projectid = $project->id AND domain = 'strength' ");
	    if (empty($strenghes)){
	        $strengthes = get_records_select('techproject_qualifier', " projectid = 0 AND domain = 'strength' ");
	    }
	    include "xmllib.php";
	    $xmlstrengthes = recordstoxml($strengthes, 'strength', '', false, 'techproject');
	    $xml = recordstoxml($requirements, 'requirement', $xmlstrengthes);
	    $escaped = str_replace('<', '&lt;', $xml);
	    $escaped = str_replace('>', '&gt;', $escaped);
	    print_heading(get_string('xmlexport', 'techproject'));
	    print_simple_box("<pre>$escaped</pre>");
        add_to_log($course->id, 'techproject', 'changerequirement', "view.php?id={$cm->id}&amp;view=requirements&amp;group={$currentGroupId}", 'export', $cm->id);
        print_continue("view.php?view=requirements&amp;id=$cm->id");
        return;
	}
	elseif ($work == 'up') {
		$requid = required_param('requid', PARAM_INT);
		techproject_tree_up($project, $currentGroupId, $requid, 'techproject_requirement');
	}
	elseif ($work == 'down') {
		$requid = required_param('requid', PARAM_INT);
		techproject_tree_down($project, $currentGroupId, $requid, 'techproject_requirement');
	}
	elseif ($work == 'left') {
		$requid = required_param('requid', PARAM_INT);
		techproject_tree_left($project, $currentGroupId, $requid, 'techproject_requirement');
	}
	elseif ($work == 'right') {
		$requid = required_param('requid', PARAM_INT);
		techproject_tree_right($project, $currentGroupId, $requid, 'techproject_requirement');
	}

/// Add requirement form *********************************************************

	if ($work == 'add'){
	    $fatherid = required_param('fatherid', PARAM_INT);
	    $requtitle = ($fatherid) ? 'addsubrequirement' : 'addrequirement';
    ?>
    <center>
    <?php print_heading(get_string($requtitle, 'techproject'), 'center'); ?>
    <script type="text/javascript">
    //<![CDATA[
    function senddata(){
        document.forms['addrequform'].work.value='new';
        <?php if ($usehtmleditor && @$CFG->defaulthtmleditor == 'htmlarea') echo "document.forms['addrequform'].onsubmit();\n"; ?>
        document.forms['addrequform'].submit();
    }
    
    function cancel(){
        <?php if ($usehtmleditor && @$CFG->defaulthtmleditor == 'htmlarea') echo "document.forms['addrequform'].onsubmit();\n"; ?>
        document.forms['addrequform'].submit();
    }
    //]]>
    </script>
    <form name="addrequform" method="post" action="view.php#req<?php p($fatherid) ?>">
    <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
    <input type="hidden" name="work" value="" />
    <input type="hidden" name="fatherid" value="<?php echo $fatherid; ?>" />
    <table>
    <tr valign="top">
    	<td align="right"><b><?php print_string('requirementtitle', 'techproject') ?>:</b></td>
        <td align="left">
            <input type="text" name="abstract" size="100%" value="" alt="<?php print_string('requirementtitle', 'techproject') ?>" />
        </td>
    </tr>
    <tr valign="top">
    	<td align="right"><b><?php print_string('strength', 'techproject') ?>:</b>
    	</td>
        <td align="left">
    <?php
            $strengthes = techproject_get_options('strength', $project->id);
            $strengthoptions = array();
            foreach($strengthes as $aStrength){
                $strengthoptions[$aStrength->code] = '['. $aStrength->code . '] ' . $aStrength->label;
            }
            choose_from_menu ($strengthoptions, 'strength');
            helpbutton('strength', get_string('strength', 'techproject'), 'techproject', true, false);
    ?>
        </td>
    </tr>
    <tr valign="top">
    	<td align="right"><b><?php print_string('heaviness', 'techproject') ?>:</b>
    	</td>
        <td align="left">
    <?php
            $heavinesses = techproject_get_options('heaviness', $project->id);
            $heavinessoptions = array();
            foreach($heavinesses as $anHeaviness){
                $heavinessoptions[$anHeaviness->code] = '['. $anHeaviness->code . '] ' . $anHeaviness->label;
            }
            choose_from_menu ($heavinessoptions, 'heaviness');
            helpbutton('heaviness', get_string('heaviness', 'techproject'), 'techproject', true, false);
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
               emoticonhelpbutton('addrequform', 'description', 'moodle', true, true);
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
    </table>
    <input type="button" name="go_btn" value="<?php print_string('savechanges') ?>" onclick="senddata()" />
    <input type="button" name="cancel_btn" value="<?php print_string('cancel') ?>" onclick="cancel()" />
    </form>
    </center>
    
    <?php
    	}
    
/// Update requirement form *********************************************************
	elseif ($work == 'update') {
	    $requid = required_param('requid', PARAM_INT);

        if(! $requirement = get_record('techproject_requirement', 'id', $requid)){
			print_string('errorrequirement','techproject');
		}
		else {
    ?>
    <center>
    <div class="content">
    <?php  print_heading(get_string('updaterequirement','techproject'), 'center'); ?>
    <script type="text/javascript">
    //<![CDATA[
    function senddata(){
        document.forms['updaterequform'].work.value='doupdate';
        <?php if ($usehtmleditor && @$CFG->defaulthtmleditor == 'htmlarea') echo "document.forms['updaterequform'].onsubmit();\n"; ?>
        document.forms['updaterequform'].submit();
    }
    
    function cancel(){
        <?php if ($usehtmleditor && @$CFG->defaulthtmleditor == 'htmlarea') echo "document.forms['updaterequform'].onsubmit();\n"; ?>
        document.forms['updaterequform'].submit();
    }
    //]]>
    </script>
    <form name="updaterequform" method="post" action="view.php#req<?php p($requirement->fatherid) ?>">
    <table>
    <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
    <input type="hidden" name="fatherid" value="<?php p($requirement->fatherid) ?>" />
    <input type="hidden" name="work" value="" />
    <input type="hidden" name="requid" value="<?php p($requid) ?>" />
    <tr valign="top">
    	<td align="right"><b><?php  print_string('requirementtitle', 'techproject') ?>:</b></td>
        <td align="left">
            <input type="text" name="abstract" size="60" value="<?php p($requirement->abstract) ?>" alt="<?php print_string('requirementtitle', 'techproject') ?>" />
        </td>
    </tr>
    <tr valign="top">
    	<td align="right"><b><?php print_string('strength', 'techproject') ?>:</b>
    	</td>
        <td align="left">
    <?php
            $strengthes = techproject_get_options('strength', $project->id);
            $strengthoptions = array();
            foreach($strengthes as $aStrength){
                $strengthoptions[$aStrength->code] = '['. $aStrength->code . '] ' . $aStrength->label;
            }
            choose_from_menu ($strengthoptions, 'strength', $requirement->strength);
            helpbutton('strength', get_string('strength', 'techproject'), 'techproject', true, false);
    ?>
        </td>
    </tr>
    <tr valign="top">
    	<td align="right"><b><?php print_string('heaviness', 'techproject') ?>:</b>
    	</td>
        <td align="left">
    <?php
            $heavinesses = techproject_get_options('heaviness', $project->id);
            $heavinessoptions = array();
            foreach($heavinesses as $anHeaviness){
                $heavinessoptions[$anHeaviness->code] = '['. $anHeaviness->code . '] ' . $anHeaviness->label;
            }
            choose_from_menu ($heavinessoptions, 'heaviness', $requirement->heaviness);
            helpbutton('heaviness', get_string('heaviness', 'techproject'), 'techproject', true, false);
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
               emoticonhelpbutton('updaterequform', 'description', 'moodle', true, true);
               echo "<br />";
            }
          ?>
          <br />
        </font>
        </td>
        <td>
        <?php
           print_textarea($usehtmleditor, 20, 60, 595, 400, 'description', $requirement->description);
    
           if ($usehtmleditor) {
               echo '<input type="hidden" name="format" value="'.FORMAT_HTML.'" />';
               $nohtmleditorneeded = false;
           } else {
               echo '<p align="right">';
               helpbutton('textformat', get_string('formattexttype'));
               print_string('formattexttype');
               echo ':&nbsp;';
               if (empty($requirement->format)) {
                   $requirement->format = $defaultformat;
               }
               choose_from_menu(format_text_menu(), 'format', $requirement->format, '');
               echo '</p>';
           }
        ?>
        </td>
    </tr>
    </table>
    <input type="button" name="go_btn" value="<?php print_string('savechanges') ?>" onclick="senddata()" />
    <input type="submit" name="cancel_btn" value="<?php print_string('cancel') ?>" onclick="cancel()" />
    </form>
    </div>
    </center>
    
    <?php
    		}
    	}

/// Delete requirement form *********************************************************

    	elseif ($work == "delete") {
    	    $requid = required_param('requid', PARAM_INT);
    ?>
    
    <center>
    <?php print_heading(get_string('delete'), 'center'); ?>
    <script type="text/javascript">
    //<![CDATA[
    function senddata(){
        document.forms['deleterequform'].work.value='dodelete';
        document.forms['deleterequform'].submit();
    }
    
    function cancel(){
        document.forms['deleterequform'].submit();
    }
    //]]>
    </script>
    <form name="deleterequform" method="post" action="view.php">
    <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
    <input type="hidden" name="work" value="" />
    <input type="hidden" name="requid" value="<?php p($requid) ?>" />
    <input type="button" name="go_btn" value="<?php print_string('delete') ?>" onclick="senddata()" />
    <input type="button" name="cancel_btn" value="<?php print_string('cancel') ?>" onclick="cancel()" />
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
    <?php print_heading(get_string('groupoperations', 'techproject'), 'center'); ?>
    <?php print_heading(get_string("group$cmd", 'techproject'), 'center', 'h3'); ?>
    <script type="text/javascript">
    //<!{CDATA{
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
        if (($cmd == 'move')||($cmd == 'copy')){
            echo get_string('to', 'techproject');
            if (@$project->projectusesspecs) $options['specs'] = get_string('specifications', 'techproject');
            if (@$project->projectusesspecs) $options['specswb'] = get_string('specificationswithbindings', 'techproject');
            $options['tasks'] = get_string('tasks', 'techproject');
            if (@$project->projectusesdelivs) $options['deliv'] = get_string('deliverables', 'techproject');
            choose_from_menu($options, 'to', '', 'choose');
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
        if ($USER->editmode == 'on' && has_capability('mod/techproject:changerequs', $context)) {
        	echo "<br/><a href='view.php?id={$cm->id}&amp;work=add&amp;fatherid=0'>".get_string('addrequirement','techproject')."</a>&nbsp;";
        }
    	techproject_print_requirement($project, $currentGroupId, 0, $cm->id);
        if ($USER->editmode == 'on' && has_capability('mod/techproject:changerequs', $context)) {
        	echo "<br/><a href='javascript:selectall(document.forms[\"groupopform\"])'>".get_string('selectall','techproject')."</a>&nbsp;";
        	echo "<a href='javascript:unselectall(document.forms[\"groupopform\"])'>".get_string('unselectall','techproject')."</a>&nbsp;";
        	echo "<a href='view.php?id={$cm->id}&amp;work=add&amp;fatherid=0'>".get_string('addrequirement','techproject')."</a>&nbsp;";
        	techproject_print_group_commands();
        }
    ?>
    </form>
<?php
}
?>