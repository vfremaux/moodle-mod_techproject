<?php

    /**
    * Project : Technical Project Manager (IEEE like)
    *
    * Deliverables operations.
    *
    * @package mod-techproject
    * @category mod
    * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
    * @date 2008/03/03
    * @version phase1
    * @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
    * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
    */

    /**
    * Requires and includes
    */
    include_once '../../lib/uploadlib.php';

    $usehtmleditor = can_use_html_editor();
    $defaultformat = FORMAT_MOODLE;

/// Controller

	if ($work == 'new') {
		$deliverable->groupid = $currentGroupId;
		$deliverable->projectid = $project->id;
		$deliverable->abstract = required_param('abstract', PARAM_CLEAN);
		$deliverable->description = addslashes(required_param('description', PARAM_CLEANHTML));
        $deliverable->format = required_param('format', PARAM_INT);
		$deliverable->status = required_param('status', PARAM_INT);
		$deliverable->fatherid = required_param('fatherid', PARAM_INT);
		$deliverable->userid = $USER->id;
		$deliverable->created = time();
		$deliverable->modified = time();
		$deliverable->lastuserid = $USER->id;

        if (!empty($deliverable->abstract)){
            $deliverable->ordering = techproject_tree_get_max_ordering($project->id, $currentGroupId, 'techproject_deliverable', true, $deliverable->fatherid) + 1;
		    $returnid = insert_record('techproject_deliverable', $deliverable );
            add_to_log($course->id, 'techproject', 'changedeliverable', "view.php?id={$cm->id}&amp;view=deliverables&amp;group={$currentGroupId}", 'add', $cm->id);
		}

   		// if notifications allowed notify project managers
   		if( $project->allownotifications){
            $class = get_string('deliverables', 'techproject');
       		$status = get_record('techproject_qualifier', 'domain', 'delivstatus', 'code', $deliverable->status);
       		if (!$status) $status->label = "N.Q.";
       		$qualifiers[] = get_string('status', 'techproject').': '.$status->label;
       		$projectheading = get_record('techproject_heading', 'projectid', $project->id, 'groupid', $currentGroupId);
       		$message = compile_mail_template('newentrynotify', array(
       		    'PROJECT' => $projectheading->title,
       		    'CLASS' => $class,
       		    'USER' => fullname($USER),
       		    'ENTRYNODE' => implode(".", techproject_tree_get_upper_branch('techproject_deliverable', $returnid, true, true)),
       		    'ENTRYABSTRACT' => stripslashes($deliverable->abstract),
       		    'ENTRYDESCRIPTION' => $deliverable->description,
       		    'QUALIFIERS' => implode('<br/>', $qualifiers),
       		    'ENTRYLINK' => $CFG->wwwroot."/mod/techproject/view.php?id={$project->id}&view=deliverables&group={$currentGroupId}"
       		), 'techproject');       		
       		$managers = get_users_by_capability($context, 'mod/techproject/manage', 'u.id, firstname, lastname, email, picture, mailformat');
       		if (!empty($managers)){
           		foreach($managers as $manager){
               		email_to_user ($manager, $USER, $course->shortname .' - '.get_string('notifynewdeliv', 'techproject'), html_to_text($message), $message);
               	}
            }
       	}
	} elseif ($work == 'doupdate') {
		$deliverable->id = required_param('delivid', PARAM_INT);
		$deliverable->abstract = required_param('abstract', PARAM_CLEAN);
		$deliverable->description = required_param('description', PARAM_CLEAN);
        $deliverable->format = required_param('format', PARAM_INT);
		$deliverable->status = required_param('status', PARAM_ALPHA);
		$deliverable->milestoneid = required_param('milestoneid', PARAM_INT);
		$deliverable->url = optional_param('url', '', PARAM_CLEAN);
 		$deliverable->modified = time();
		$deliverable->lastuserid = $USER->id;
       
        $uploader = new upload_manager('FILE_0', false, false, $course->id, true, 0, true);
        $uploader->preprocess_files();
        $deliverable->localfile = $uploader->get_new_filename();
        if (!empty($deliverable->localfile)){
            $uploader->save_files("{$course->id}/moddata/techproject/{$project->id}/".md5("techproject{$project->id}_{$currentGroupId}"));
            $deliverable->url = '';
            add_to_log($course->id, 'techproject', 'submit', "view.php?id={$cm->id}&amp;view=view_detail&amp;objectId={$deliverable->id}&amp;objectClass=deliverable&amp;group={$currentGroupId}", $project->id, $cm->id);
        }
		
		if (!empty($deliverable->abstract)){
    		$res = update_record('techproject_deliverable', $deliverable );
            add_to_log($course->id, 'techproject', 'changedeliverable', "view.php?id={$cm->id}&amp;view=deliverables&amp;group={$currentGroupId}", 'update', $cm->id);
    	}
	} elseif ($work == 'dodelete') {
		$delivid = required_param('delivid', PARAM_INT);
		techproject_tree_delete($delivid, 'techproject_deliverable');
        add_to_log($course->id, 'techproject', 'changedeliverable', "view.php?id={$cm->id}&amp;view=deliverables&amp;group={$currentGroupId}", 'delete', $cm->id);
	} elseif ($work == 'domove' || $work == 'docopy') {
		$ids = required_param('ids', PARAM_INT);
		$to = required_param('to', PARAM_ALPHA);
		switch($to){
		    case 'requs' : { $table2 = 'techproject_requirement'; $redir = 'requirement'; } break;
		    case 'specs' : { $table2 = 'techproject_specification'; $redir = 'specification'; } break;
		    case 'tasks' : { $table2 = 'techproject_task'; $redir = 'task'; } break;
		    case 'deliv' : { $table2 = 'techproject_deliverable'; $redir = 'deliverable'; } break;
		}
		techproject_tree_copy_set($ids, 'techproject_deliverable', $table2);
        add_to_log($course->id, 'techproject', 'change{$redir}', "view.php?id={$cm->id}&amp;view={$redir}s&amp;group={$currentGroupId}", 'copy/move', $cm->id);
		if ($work == 'domove'){
		    // bounce to deleteitems
		    $work = 'dodeleteitems';
		    $withredirect = 1;
		} else {
		    redirect("{$CFG->wwwroot}/mod/techproject/view.php?id={$cm->id}&amp;view={$redir}s", get_string('redirectingtoview', 'techproject') . get_string($redir, 'techproject'));
	    }
	}
	if ($work == 'dodeleteitems') {
		$ids = required_param('ids', PARAM_INT);
		foreach($ids as $anItem){
    	    // save record for further cleanups and propagation
    	    $oldRecord = get_record('techproject_deliverable', 'id', $anItem);
		    $childs = get_records('techproject_deliverable', 'fatherid', $anItem);
		    
		    // update fatherid in childs 
		    $query = "
		        UPDATE
		            {$CFG->prefix}techproject_deliverable
		        SET
		            fatherid = $oldRecord->fatherid
		        WHERE
		            fatherid = $anItem
		    ";
		    execute_sql($query);
    		delete_records('techproject_deliverable', 'id', $anItem);
    
            // delete all related records
    		delete_records('techproject_task_to_deliv', 'delivid', $anItem);
    	}
        add_to_log($course->id, 'techproject', 'changedeliverable', "view.php?id={$cm->id}&amp;view=deliverable&amp;group={$currentGroupId}", 'deleteItems', $cm->id);
    	if (isset($withredirect) && $withredirect){
		    redirect("{$CFG->wwwroot}/mod/techproject/view.php?id={$cm->id}&amp;view={$redir}s", get_string('redirectingtoview', 'techproject') . ' : ' . get_string($redir, 'techproject'));
		}
	} elseif ($work == 'doclearall') {
        // delete all records. POWERFUL AND DANGEROUS COMMAND.
		delete_records('techproject_deliverable', 'projectid', $project->id);
	} elseif ($work == 'doexport') {
	    $ids = required_param('ids', PARAM_INT);
	    $idlist = implode("','", $ids);
	    $select = "
	       id IN ('$idlist')	       
	    ";
	    $deliverables = get_records_select('techproject_deliverable', $select);
	    $delivstatusses = get_records_select('techproject_qualifier', " domain = 'delivstatus' AND projectid = $project->id ");
	    if (empty($delivstatusses)){
	        $delivstatusses = get_records_select('techproject_qualifier', " domain = 'delivstatus' AND projectid = 0 ");
	    }
	    include "xmllib.php";
	    $xmldelivstatusses = recordstoxml($delivstatusses, 'deliv_status_option', '', false, 'techproject');
	    $xml = recordstoxml($deliverables, 'deliverable', $xmldelivstatusses, true, null);
	    $escaped = str_replace('<', '&lt;', $xml);
	    $escaped = str_replace('>', '&gt;', $escaped);
	    print_heading(get_string('xmlexport', 'techproject'));
	    print_simple_box("<pre>$escaped</pre>");
        add_to_log($course->id, 'techproject', 'readdeliverable', "view.php?id={$cm->id}&amp;view=deliverables&amp;group={$currentGroupId}", 'export', $cm->id);
        print_continue("view.php?view=deliverables&amp;id=$cm->id");
        return;
	} elseif ($work == 'up') {
		$delivid = required_param('delivid', PARAM_INT);
		techproject_tree_up($project, $currentGroupId,$delivid, 'techproject_deliverable');
	} elseif ($work == 'down') {
		$delivid = required_param('delivid', PARAM_INT);
		techproject_tree_down($project, $currentGroupId,$delivid, 'techproject_deliverable');
	} elseif ($work == 'left') {
		$delivid = required_param('delivid', PARAM_INT);
		techproject_tree_left($project, $currentGroupId,$delivid, 'techproject_deliverable');
	} elseif ($work == 'right') {
		$delivid = required_param('delivid', PARAM_INT);
		techproject_tree_right($project, $currentGroupId,$delivid, 'techproject_deliverable');
	}

/// Add deliverable form *********************************************************

    if ($work == 'add'){
        $fatherid = required_param('fatherid', PARAM_INT);
        $delivtitle = ($fatherid) ? 'addsubdeliv' : 'adddeliv';
    ?>
    
    <center>
    <?php print_heading(get_string($delivtitle, 'techproject'), 'center'); ?>
    <script type="text/javascript">
    //<![CDATA[
    function senddata(){
        document.forms['adddelivform'].work.value='new';
        <?php if ($usehtmleditor && @$CFG->defaulthtmleditor == 'htmlarea') echo "document.forms['adddelivform'].onsubmit();\n"; ?>
        document.forms['adddelivform'].submit();
    }
    
    function cancel(){
        <?php if ($usehtmleditor && @$CFG->defaulthtmleditor == 'htmlarea') echo "document.forms['adddelivform'].onsubmit();\n"; ?>
        document.forms['adddelivform'].submit();
    }
    //]]>
    </script>
    <form name="adddelivform" method="post" action="view.php#del<?php p($fatherid) ?>">
    <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
    <input type="hidden" name="work" value="" />
    <input type="hidden" name="fatherid" value="<?php  echo $fatherid; ?>" />
    <table>
    <tr valign="top">
    	<td align="right"><b><?php  print_string('delivtitle', 'techproject') ?>:</b></td>
        <td>
            <input type="text" name="abstract" size="100%" value="" alt="<?php print_string('delivtitle', 'techproject') ?>" />
        </td>
    </tr>
    <tr valign="top">
    	<td align="right"><b><?php print_string('status', 'techproject') ?>:</b>
    	</td>
        <td align="left">
    <?php
            $statusses = techproject_get_options('delivstatus', $project->id);
            $deliverystatusses = array();
            foreach($statusses as $aStatus){
                $deliverystatusses[$aStatus->code] = '['. $aStatus->code . '] ' . $aStatus->label;
            }
            choose_from_menu ($deliverystatusses, 'status', '');
            helpbutton('deliverablestatus', get_string('status', 'techproject'), 'techproject', true, false);
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
               helpbutton("richtext", get_string('helprichtext'), 'moodle', true, true);
            } else {
               helpbutton('text', get_string('helptext'), 'moodle', true, true);
               echo "<br />";
               emoticonhelpbutton('adddelivform', 'description', 'moodle', true, true);
               echo "<br />";
            }
          ?>
          <br />
        </font>
        </td>
        <td>
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
    <input type="button" name="go_btn" value="<?php  print_string('savechanges') ?>" onclick="senddata()" />
    <input type="button" name="cancel_btn" value="<?php print_string('cancel') ?>" onclick="cancel()" />
    </form>
    </center>
    
    <?php

/// Update deliverable form *********************************************************

    } elseif ($work == 'update') {
	    $delivid = required_param('delivid', PARAM_INT);

		if(! $deliverable  = get_record('techproject_deliverable', 'id', $delivid)){
			print_string('errordeliverable','techproject');
		} else {
    ?>
    
    <center>
    <?php print_heading(get_string('updatedeliv','techproject'), 'center'); ?>
    <script type="text/javascript">
    //<![CDATA[
    function senddata(){
        document.forms['updatedelivform'].work.value='doupdate';
        <?php if ($usehtmleditor && @$CFG->defaulthtmleditor == 'htmlarea') echo "document.forms['updatedelivform'].onsubmit();\n"; ?>
        document.forms['updatedelivform'].submit();
    }
    
    function cancel(){
        <?php if ($usehtmleditor && @$CFG->defaulthtmleditor == 'htmlarea') echo "document.forms['updatedelivform'].onsubmit();\n"; ?>
        document.forms['updatedelivform'].submit();
    }
    //]]>
    </script>
    <form name="updatedelivform" method="post" action="view.php#del<?php p($deliverable->fatherid) ?>" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
    <input type="hidden" name="fatherid" value="<?php p($deliverable->fatherid) ?>" />
    <input type="hidden" name="work" value="" />
    <input type="hidden" name="delivid" value="<?php p($delivid) ?>" />
    <table>
    
    <tr valign="top">
    	<td align="right"><b><?php print_string('delivtitle', 'techproject') ?>:</b></td>
        <td>
            <input type="text" name="abstract" size="100%" value="<?php p($deliverable->abstract) ?>" alt="<?php  print_string('delivtitle', 'techproject') ?>" />
        </td>
    </tr>
    
    <tr valign="top">
    	<td align="right"><b><?php print_string('status', 'techproject') ?>:</b>
    	</td>
        <td align="left">
    <?php
            $statusses = techproject_get_options('delivstatus', $project->id);
            $statussesoption = array();
            foreach($statusses as $aStatus){
                $statussesoption[$aStatus->code] = '['. $aStatus->code . '] ' . $aStatus->label;
            }
            choose_from_menu ($statussesoption, 'status', $deliverable->status);
            helpbutton('status', get_string('status', 'techproject'), 'techproject', true, false);
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
               emoticonhelpbutton('updatedelivform', 'description', 'moodle', true, true);
               echo "<br />";
            }
          ?>
          <br />
        </font>
        </td>
        <td>
        <?php
           print_textarea($usehtmleditor, 20, 60, 595, 400, 'description', $deliverable->description);
    
           if ($usehtmleditor) {
               echo '<input type="hidden" name="format" value="'.FORMAT_HTML.'" />';
               $nohtmleditorneeded = false;
           } else {
               echo '<p align="right">';
               helpbutton('textformat', get_string('formattexttype'));
               print_string('formattexttype');
               echo ':&nbsp;';
               if (empty($deliverable->format)) {
                   $deliverable->format = $defaultformat;
               }
               choose_from_menu(format_text_menu(), 'format', $deliverable->format, '');
               echo '</p>';
           }
        ?>
        </td>
    </tr>
    
    <tr valign="top">
    	<td align="right">
    	    <b><?php print_string('milestone', 'techproject') ?>:</b>
    	</td>
        <td align="left">
    <?php
        $query = "
           SELECT
              id,
              abstract,
              ordering
           FROM
              {$CFG->prefix}techproject_milestone
           WHERE
              projectid = {$project->id} AND
              groupid = {$currentGroupId}
           ORDER BY
              ordering
        ";
        $milestones = get_records_sql($query);
        $milestonesoptions = array();
        foreach($milestones as $aMilestone){
            $milestonesoptions[$aMilestone->id] = format_string($aMilestone->abstract);
        }
        choose_from_menu ($milestonesoptions, 'milestoneid', $deliverable->milestoneid, get_string('unassigned', 'techproject') );
        helpbutton('deliverabletomilestone', get_string('milestone', 'techproject'), 'techproject', true, false);
    ?>
        </td>
    </tr>
    <tr>
        <td valign="top" align="right">
    	    <b><?php print_string('deliverable', 'techproject') ?>:</b>
        </td>
        <td valign="top" align="left"> 
    <?php
        if (!empty($deliverable->url)) {
            echo "<a href=\"{$deliverable->url}\" target=\"_blank\">{$deliverable->url}</a>";
        } else if ($deliverable->localfile) {
            $localfile = "{$course->id}/projects/{$project->id}/".md5($currentGroupId)."/{$deliverable->localfile}";
            echo "<a href=\"{$CFG->wwwroot}/file.php/{$localfile}\" target=\"_blank\">".basename($deliverable->localfile)."</a>";
        } else {
            print_string('notsubmittedyet','techproject');
        }
       helpbutton('delivered', get_string('delivered', 'techproject'), 'techproject', true, false);
    ?>
            <br/>
            <br/>
            <?php print_simple_box_start('left', '100%'); ?>
            <?php print_string('giveurl', 'techproject') ?>
            <input type="text" name="url" value="<?php p($deliverable->url) ?>" style="width : 100%" /><br/>
            <?php print_string('oruploadfile', 'techproject') ?><br/>
            <?php upload_print_form_fragment() ?>
            <?php print_simple_box_end(); ?>
        </td>
    </tr>
    
    </table>
    <input type="button" name="go_btn" value="<?php print_string('savechanges') ?>" onclick="senddata()" />
    <input type="button" name="cancel_btn" value="<?php print_string('cancel') ?>" onclick="cancel()" />
    </form>
    </center>
    
    <?php
    		}
    
/// Delete deliverable form *********************************************************
    
        } elseif ($work == 'delete') {
    	    $delivid = required_param('delivid', PARAM_INT);
    ?>
    
    <center>
    <?php print_heading(get_string('delete'), 'center'); ?>
    <script type="text/javascript">
    //<![CDATA[
    function senddata(){
        document.forms['deletedelivform'].work.value='dodelete';
        document.forms['deletedelivform'].submit();
    }
    
    function cancel(){
        document.forms['deletedelivform'].submit();
    }
    //]]>
    </script>
    <form name="deletedelivform" method="post" action="view.php">
    <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
    <input type="hidden" name="delivid" value="<?php p($delivid) ?>" />
    <input type="hidden" name="work" value="" />
    <input type="button" name="go_btn" value="<?php print_string('delete') ?>" onclick="senddata()" />
    <input type="button" name="cancel_btn" value="<?php print_string('cancel') ?>" onclick="cancel()" />
    </form>
    </center>
    
    <?php
    
/// Group operation form *********************************************************

    } elseif ($work == "groupcmd") {
	    $ids = required_param('ids', PARAM_INT);
	    $cmd = required_param('cmd', PARAM_ALPHA);
    ?>
    
    <center>
    <?php print_heading(get_string('groupoperations', 'techproject'), 'center'); ?>
    <?php print_heading(get_string("group$cmd", 'techproject'), 'center', 'h3'); ?>
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
    <form name="groupopform" method="get" action="view.php">
    <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
    <input type="hidden" name="work" value="" />
    <?php
            foreach($ids as $anId){
                echo "<input type=\"hidden\" name=\"ids[]\" value=\"{$anId}\" />\n";
            }
            if (($cmd == 'move')||($cmd == 'copy')){
                echo get_string('to', 'techproject');
                if (@$project->projectusesrequs) $options['requs'] = get_string('requirements', 'techproject');
                if (@$project->projectusesspecs) $options['specs'] = get_string('specifications', 'techproject');
                $options['tasks'] = get_string('tasks', 'techproject');
                choose_from_menu($options, 'to', '', 'choose');
            }
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
        if ($USER->editmode == 'on' && has_capability('mod/techproject:changedelivs', $context)) {
    		echo "<br/><a href='view.php?id={$cm->id}&amp;work=add&amp;fatherid=0'>".get_string('adddeliv','techproject')."</a>&nbsp; ";
    	}
    	techproject_print_deliverables($project, $currentGroupId, 0, $cm->id);
        if ($USER->editmode == 'on' && has_capability('mod/techproject:changedelivs', $context)) {
    		echo "<br/><a href='view.php?id={$cm->id}&amp;work=add&amp;fatherid=0'>".get_string('adddeliv','techproject')."</a>&nbsp; ";
    		techproject_print_group_commands();
    	}
    ?>
    </form>
    <?php
    }
?>