<?php

    /**
    * Project : Technical Project Manager (IEEE like)
    *
    * Specification operations.
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

	if ($work){
		include 'specification.controller.php';
	}

/// Add specification form *********************************************************

	if ($work == 'add'){
	    $fatherid = required_param('fatherid', PARAM_INT);
	    $spectitle = ($fatherid) ? 'addsubspec' : 'addspec';
?>
<center>
<?php print_heading(get_string($spectitle, 'techproject'), 'center'); ?>
<script type="text/javascript">
//<![CDATA[
function senddata(){
    document.forms['addspecform'].work.value='new';
    <?php if ($usehtmleditor && $CFG->defaulthtmleditor == 'htmlarea') echo "document.forms['addspecform'].onsubmit();\n"; ?>
    document.forms['addspecform'].submit();
}

function cancel(){
    <?php if ($usehtmleditor && $CFG->defaulthtmleditor == 'htmlarea') echo "document.forms['addspecform'].onsubmit();\n"; ?>
    document.forms['addspecform'].submit();
}
//]]>
</script>
<form name="addspecform" method="post" action="view.php#spe<?php p($fatherid) ?>">
<input type="hidden" name="id" value="<?php p($cm->id) ?>" />
<input type="hidden" name="work" value="" />
<input type="hidden" name="fatherid" value="<?php echo $fatherid; ?>" />
<table>
<tr valign="top">
	<td align="right"><b><?php print_string('spectitle', 'techproject') ?>:</b></td>
    <td>
        <input type="text" name="abstract" size="100%" value="" alt="<?php print_string('spectitle', 'techproject') ?>" />
    </td>
</tr>
<tr valign="top">
	<td align="right"><b><?php print_string('severity', 'techproject') ?>:</b>
	</td>
    <td align="left">
<?php
        $severities = techproject_get_options('severity', $project->id);
        $severityoptions = array();
        foreach($severities as $aSeverity){
            $severityoptions[$aSeverity->code] = '['. $aSeverity->code . '] ' . $aSeverity->label;
        }
        choose_from_menu ($severityoptions, 'severity');
        helpbutton('severity', get_string('severity', 'techproject'), 'techproject', true, false);
?>
    </td>
</tr>
<tr valign="top">
	<td align="right"><b><?php print_string('priority', 'techproject') ?>:</b>
	</td>
    <td align="left">
<?php
        $priorities = techproject_get_options('priority', $project->id);
        $priorityoptions = array();
        foreach($priorities as $aPriority){
            $priorityoptions[$aPriority->code] = '['. $aPriority->code . '] ' . $aPriority->label;
        }
        choose_from_menu ($priorityoptions, 'priority');
        helpbutton('priority', get_string('priority', 'techproject'), 'techproject', true, false);
?>
    </td>
</tr>
<tr valign="top">
	<td align="right"><b><?php print_string('complexity', 'techproject') ?>:</b>
	</td>
    <td align="left">
<?php
        $complexities = techproject_get_options('complexity', $project->id);
        $complexityoptions = array();
        foreach($complexities as $aComplexity){
            $complexityoptions[$aComplexity->code] = '['. $aComplexity->code . '] ' . $aComplexity->label;
        }
        choose_from_menu ($complexityoptions, 'complexity');
        helpbutton('complexity', get_string('complexity', 'techproject'), 'techproject', true, false);
?>
    </td>
</tr>
<tr valign="top">
    <td align="right"><b><?php  print_string('description', 'techproject') ?>:</b><br />
    <font size="1">
     <?php
        helpbutton('writing', get_string('helpwriting'), 'moodle', true, true);
        echo "<br />";
        if ($usehtmleditor) {
           helpbutton("richtext", get_string('helprichtext'), 'moodle', true, true);
        } else {
           helpbutton('text', get_string('helptext'), 'moodle', true, true);
           echo "<br />";
           emoticonhelpbutton('addspecform', 'description', 'moodle', true, true);
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
<input type="button" name="go_btn" value="<?php print_string('savechanges') ?>" onclick="senddata()" />
<input type="button" name="cancel_btn" value="<?php print_string('cancel') ?>" onclick="cancel()" />
</form>
</center>

<?php
	}

/// Update specification form *********************************************************

	elseif ($work == 'update') {
	    $specid = required_param('specid', PARAM_INT);

		if(! $specification = get_record('techproject_specification', 'id', $specid)){
			print_string('errorspecification','techproject');
		}
		else {
?>

<center>
<?php print_heading(get_string('updatespec','techproject'), 'center'); ?>
<script type="text/javascript">
//<![CDATA[
function senddata(){
    document.forms['updatespecform'].work.value='doupdate';
    <?php if ($usehtmleditor && $CFG->defaulthtmleditor == 'htmlarea') echo "document.forms['updatespecform'].onsubmit();\n"; ?>
    document.forms['updatespecform'].submit();
}

function cancel(){
    <?php if ($usehtmleditor && $CFG->defaulthtmleditor == 'htmlarea') echo "document.forms['updatespecform'].onsubmit();\n"; ?>
    document.forms['updatespecform'].submit();
}
//]]>
</script>
<form name="updatespecform" method="post" action="view.php#spe<?php p($specification->fatherid) ?>">
<input type="hidden" name="id" value="<?php p($cm->id) ?>" />
<input type="hidden" name="fatherid" value="<?php p($specification->fatherid) ?>" />
<input type="hidden" name="work" value="" />
<input type="hidden" name="specid" value="<?php p($specid) ?>" />
<table>
<tr valign="top">
	<td align="right"><b><?php print_string('spectitle', 'techproject') ?>:</b></td>
    <td align="left">
        <input type="text" name="abstract" size="100%" value="<?php p($specification->abstract) ?>" alt="<?php  print_string('spectitle', 'techproject') ?>" />
    </td>
</tr>
<tr valign="top">
	<td align="right"><b><?php print_string('severity', 'techproject') ?>:</b>
	</td>
    <td align="left">
<?php
        $severities = techproject_get_options('severity', $project->id);
        $severityoptions = array();
        foreach($severities as $aSeverity){
            $severityoptions[$aSeverity->code] = '['. $aSeverity->code . '] ' . $aSeverity->label;
        }
        choose_from_menu ($severityoptions, 'severity', $specification->severity);
        helpbutton('severity', get_string('severity', 'techproject'), 'techproject', true, false);
?>
    </td>
</tr>
<tr valign="top">
	<td align="right"><b><?php print_string('priority', 'techproject') ?>:</b>
	</td>
    <td align="left">
<?php
        $priorities = techproject_get_options('priority', $project->id);
        $priorityoptions = array();
        foreach($priorities as $aPriority){
            $priorityoptions[$aPriority->code] = '['. $aPriority->code . '] ' . $aPriority->label;
        }
        choose_from_menu ($priorityoptions, 'priority', $specification->priority);
        helpbutton('priority', get_string('priority', 'techproject'), 'techproject', true, false);
?>
    </td>
</tr>
<tr valign="top">
	<td align="right">
	    <b><?php print_string('complexity', 'techproject') ?>:</b>
	</td>
    <td align="left">
<?php
        $complexities = techproject_get_options('complexity', $project->id);
        $complexityoptions = array();
        foreach($complexities as $aComplexity){
            $complexityoptions[$aComplexity->code] = '['. $aComplexity->code . '] ' . $aComplexity->label;
        }
        choose_from_menu ($complexityoptions, 'complexity', $specification->complexity);
        helpbutton('complexity', get_string('complexity', 'techproject'), 'techproject', true, false);
?>
    </td>
</tr>
<tr valign="top">
    <td align="right"><b><?php print_string('description', 'techproject') ?>:</b><br />
    <font size="1">
     <?php
        helpbutton('writing', get_string("helpwriting"), 'moodle', true, true);
        echo "<br />";
        if ($usehtmleditor) {
           helpbutton('richtext', get_string('helprichtext'), 'moodle', true, true);
        } else {
           helpbutton('text', get_string('helptext'), 'moodle', true, true);
           echo "<br />";
           emoticonhelpbutton('updatespecform', 'description', 'moodle', true, true);
           echo "<br />";
        }
      ?>
      <br />
    </font>
    </td>
    <td>
    <?php
       print_textarea($usehtmleditor, 20, 60, 595, 400, "description", $specification->description);

       if ($usehtmleditor) {
           echo '<input type="hidden" name="format" value="'.FORMAT_HTML.'" />';
           $nohtmleditorneeded = false;
       } else {
           echo '<p align="right">';
           helpbutton('textformat', get_string('formattexttype'));
           print_string('formattexttype');
           echo ':&nbsp;';
           if (empty($specification->format)) {
               $specification->format = $defaultformat;
           }
           choose_from_menu(format_text_menu(), 'format', $specification->format, '');
           echo '</p>';
       }
    ?>
    </td>
</tr>
<?php 
if ($project->projectusesrequs){
?>
<tr valign="top">
	<td align="right"><b><?php print_string('spectoreq', 'techproject') ?>:</b>
	</td>
    <td align="left">
        <select name="spectoreq[]" multiple="multiple" size="10">
<?php
        $requirements = techproject_get_tree_options('techproject_requirement', $project->id, $currentGroupId);
        $selection = get_records_select_menu('techproject_spec_to_req', "specid = $specid", '', 'reqid, specid');
        if (!empty($requirements)){
            foreach($requirements as $aRequirement){
                $aRequirement->abstract = format_string($aRequirement->abstract);
                $selected = "";
                if ($selection)
                    $selected = (in_array($aRequirement->id, array_keys($selection))) ? "selected=\"selected\"" : '' ;
                echo "<option value=\"{$aRequirement->id}\" {$selected}>{$aRequirement->ordering} - ".shorten_text($aRequirement->abstract, 90)."</option>";
            }
        }
?>
        </select>
        <?php helpbutton('specificationtorequirement', get_string('spectoreq', 'techproject'), 'techproject', true, false); ?>
    </td>
</tr>
<?php
}
?>
</table>
<input type="button" name="go_btn" value="<?php print_string('savechanges') ?>" onclick="senddata()" />
<input type="button" name="cancel_btn" value="<?php print_string('cancel') ?>" onclick="cancel()" />
</form>
</center>

<?php
		}
	}

/// Delete specification form *********************************************************

	elseif ($work == 'delete') {
	    $specid = required_param('specid', PARAM_INT);
?>

<center>
<?php print_heading(get_string('delete'), 'center'); ?>
<script type="text/javascript">
//<![CDATA[
function senddata(){
    document.forms['deletespecform'].work.value='dodelete';
    document.forms['deletespecform'].submit();
}

function cancel(){
    document.forms['deletespecform'].submit();
}
//]]>
</script>
<form name="deletespecform" method="post" action="view.php">
<input type="hidden" name="id" value="<?php p($cm->id) ?>" />
<input type="hidden" name="specid" value="<?php p($specid) ?>" />
<input type="hidden" name="work" value="" />
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
        if (($cmd == 'move')||($cmd == 'copy')){
            echo get_string('to', 'techproject');
            if (@$project->projectusesrequs) $options['requs'] = get_string('requirements', 'techproject');
            if (@$project->projectusesrequs) $options['requswb'] = get_string('requirementswithbindings', 'techproject');
            $options['tasks'] = get_string('tasks', 'techproject');
            $options['taskswb'] = get_string('taskswithbindings', 'techproject');
            if (@$project->projectusesdelivs) $options['deliv'] = get_string('deliverables', 'techproject');
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
	}
	else {
	    require_js('yui_connection');
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
        if ($USER->editmode == 'on' && has_capability('mod/techproject:changespecs', $context)) {
    		echo "<br/><a href='view.php?id={$cm->id}&amp;work=add&amp;fatherid=0'>".get_string('addspec','techproject')."</a>&nbsp; ";
    	}
		techproject_print_specification($project, $currentGroupId, 0, $cm->id);
        if ($USER->editmode == 'on' && has_capability('mod/techproject:changespecs', $context)) {
	    	echo "<br/><a href='javascript:selectall(document.forms[\"groupopform\"])'>".get_string('selectall','techproject')."</a>&nbsp;";
	    	echo "<a href='javascript:unselectall(document.forms[\"groupopform\"])'>".get_string('unselectall','techproject')."</a>&nbsp;";
    		echo "<a href='view.php?id={$cm->id}&amp;work=add&amp;fatherid=0'>".get_string('addspec','techproject')."</a>&nbsp; ";
    		if (@$SESSION->techproject->spectemplateid){
        		techproject_print_group_commands('applytemplate');
        	} else {
	    		techproject_print_group_commands();
	    	}
    	}
?>
</form>
<?php
	}
?>