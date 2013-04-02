<?php

    /**
    * Project : Technical Project Manager (IEEE like)
    *
    * Prints a desciption of the project (heading).
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
    
    if ($work == 'update'){
        $heading->id = required_param('headingid', PARAM_INT);
        $heading->projectid = $project->id;
        $heading->groupid = $currentGroupId;
        $heading->title = addslashes(required_param('title', PARAM_CLEANHTML));
        $heading->abstract = addslashes(required_param('abstract', PARAM_CLEANHTML));
        $heading->rationale = addslashes(required_param('rationale', PARAM_CLEANHTML));
        $heading->environment = addslashes(required_param('environment', PARAM_CLEANHTML));
        $heading->organisation = addslashes(required_param('organisation', PARAM_CLEANHTML));
        $heading->department = addslashes(required_param('department', PARAM_CLEANHTML));
        
        update_record('techproject_heading', $heading);
    }
    if ($work == 'doexport'){
    	    $heading = get_record('techproject_heading', 'projectid', $project->id, 'groupid', $currentGroupId);
    	    $projects[$heading->projectid] = $heading;
    	    include_once "xmllib.php";
    	    $xml = recordstoxml($projects, 'project', '', true, null);
    	    $escaped = str_replace('<', '&lt;', $xml);
    	    $escaped = str_replace('>', '&gt;', $escaped);
    	    print_heading(get_string('xmlexport', 'techproject'));
    	    print_simple_box("<pre>$escaped</pre>");
            add_to_log($course->id, 'techproject', 'readdescription', "view.php?id={$cm->id}&amp;view=description&amp;group={$currentGroupId}", 'export', $cm->id);
            print_continue("view.php?view=description&amp;id=$cm->id");
            return;
    }

/// Header editing form ********************************************************

    if ($work == 'edit'){
         $projectheading = get_record('techproject_heading', 'projectid', $project->id, 'groupid', $currentGroupId);
    
    ?>
    <?php print_heading(get_string('editheading', 'techproject'), 'center'); ?>
    <script type="text/javascript">
    //<![CDATA[
    function senddata(){
        document.forms['editheadingform'].work.value='update';
        <?php if ($usehtmleditor && @$CFG->defaulthtmleditor == 'htmlarea') echo "document.forms['editheadingform'].onsubmit();\n"; ?>
        document.forms['editheadingform'].submit();
    }
    
    function cancel(){
        <?php if ($usehtmleditor && @$CFG->defaulthtmleditor == 'htmlarea') echo "document.forms['editheadingform'].onsubmit();\n"; ?>
        document.forms['editheadingform'].submit();
    }
    //]]>
    </script>
    <center>
    <form name="editheadingform" action="view.php" method="post" >
    <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
    <input type="hidden" name="headingid" value="<?php p($projectheading->id) ?>" />
    <input type="hidden" name="work" value="" />
    <table>
    <tr valign="top">
    	<td align="right"><b><?php print_string('projecttitle', 'techproject') ?>:</b></td>
        <td align="left">
            <input type="text" name="title" size="100%" value="<?php echo $projectheading->title ?>" alt="<?php  print_string('projecttitle', 'techproject') ?>" />
        </td>
    </tr>
    
    <tr valign="top">
        <td align="right"><b><?php print_string('abstract', 'techproject') ?>:</b><br />
        <font size="1">
         <?php
            helpbutton('writing', get_string('helpwriting'), 'moodle', true, true);
            echo '<br />';
            if ($usehtmleditor) {
               helpbutton('richtext', get_string('helprichtext'), 'moodle', true, true);
            } else {
               helpbutton('text', get_string('helptext'), 'moodle', true, true);
               echo '<br />';
               emoticonhelpbutton('editheadingform', 'abstract', 'moodle', true, true);
               echo '<br />';
            }
          ?>
          <br />
        </font>
        </td>
        <td align="right">
        <?php
           print_textarea($usehtmleditor, 20, 60, 595, 400, 'abstract', $projectheading->abstract);
    
           if ($usehtmleditor) {
               echo '<input type="hidden" name="format" value="'.FORMAT_HTML.'" />';
               $nohtmleditorneeded = false;
           } else {
               echo '<p align="right">';
               helpbutton('textformat', get_string('formattexttype'));
               print_string('formattexttype');
               echo ':&nbsp;';
               if (!$form->format) {
                   $form->format = $defaultformat;
               }
               choose_from_menu(format_text_menu(), 'format', $form->format, '');
               echo '</p>';
           }
        ?>
        </td>
    </tr>
    
    <tr valign="top">
        <td align="right"><b><?php print_string('rationale', 'techproject') ?>:</b><br />
        <font size="1">
         <?php
            helpbutton('writing', get_string('helpwriting'), 'moodle', true, true);
            echo "<br />";
            if ($usehtmleditor) {
               helpbutton('richtext', get_string('helprichtext'), 'moodle', true, true);
            } else {
               helpbutton('text', get_string('helptext'), 'moodle', true, true);
               echo "<br />";
               emoticonhelpbutton('editheadingform', 'rationale', 'moodle', true, true);
               echo "<br />";
            }
          ?>
          <br />
        </font>
        </td>
        <td align="right">
        <?php
           print_textarea($usehtmleditor, 20, 60, 595, 400, 'rationale', $projectheading->rationale );
    
           if ($usehtmleditor) {
               echo '<input type="hidden" name="format" value="'.FORMAT_HTML.'" />';
               $nohtmleditorneeded = false;
           } else {
               echo '<p align="right">';
               helpbutton('textformat', get_string('formattexttype'));
               print_string('formattexttype');
               echo ':&nbsp;';
               if (!$form->format) {
                   $form->format = $defaultformat;
               }
               choose_from_menu(format_text_menu(), 'format', $form->format, '');
               echo '</p>';
           }
        ?>
        </td>
    </tr>
    
    <tr valign="top">
        <td align="right"><b><?php print_string('environment', 'techproject') ?>:</b><br />
        <font size="1">
         <?php
            helpbutton('writing', get_string('helpwriting'), 'moodle', true, true);
            echo "<br />";
            if ($usehtmleditor) {
               helpbutton('richtext', get_string('helprichtext'), 'moodle', true, true);
            } else {
               helpbutton('text', get_string('helptext'), 'moodle', true, true);
               echo "<br />";
               emoticonhelpbutton('editheadingform', 'environment', 'moodle', true, true);
               echo "<br />";
            }
          ?>
          <br />
        </font>
        </td>
        <td align="right">
        <?php
           print_textarea($usehtmleditor, 20, 60, 595, 400, 'environment', $projectheading->environment);
    
           if ($usehtmleditor) {
               echo '<input type="hidden" name="format" value="'.FORMAT_HTML.'" />';
               $nohtmleditorneeded = false;
           } else {
               echo '<p align="right">';
               helpbutton('textformat', get_string('formattexttype'));
               print_string('formattexttype');
               echo ':&nbsp;';
               if (!$form->format) {
                   $form->format = $defaultformat;
               }
               choose_from_menu(format_text_menu(), 'format', $form->format, '');
               echo '</p>';
           }
        ?>
        </td>
    </tr>
    
    <tr valign="top">
    	<td align="right"><b><?php print_string('organisation', 'techproject') ?>:</b></td>
        <td align="left">
            <input type="text" name="organisation" size="100%" value="<?php echo $projectheading->organisation ?>" alt="<?php print_string('organisation', 'techproject') ?>" />
        </td>
    </tr>
    <tr valign="top">
    	<td align="right"><b><?php print_string('department', 'techproject') ?>:</b></td>
        <td align="left">
            <input type="text" name="department" size="100%" value="<?php echo $projectheading->department ?>" alt="<?php print_string('department', 'techproject') ?>" />
        </td>
    </tr>
    </table>
    <input type="button" name="go_btn" value="<?php print_string('savechanges') ?>" onclick="senddata()" />
    <input type="button" name="cancel_btn" value="<?php print_string('cancel') ?>" onclick="cancel()" />
    </form>
    </center>
    <?php
    } else {
        techproject_print_heading($project, $currentGroupId);
        echo "<center>";
        if ($USER->editmode == 'on') {
            echo "<br/><a href=\"view.php?work=edit&amp;id={$cm->id}\" >".get_string('editheading','techproject')."</a>";
            echo " - <a href=\"view.php?work=doexport&amp;id={$cm->id}\" >".get_string('exportheadingtoXML','techproject')."</a>";
        }
        echo "<br/><a href=\"xmlview.php?id={$cm->id}\" target=\"_blank\">".get_string('gettheprojectfulldocument','techproject')."</a>";
        echo "</center>";
    }
        
?>