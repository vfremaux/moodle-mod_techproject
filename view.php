<?php  // $Id: view.php,v 1.2 2011-07-14 13:33:03 vf Exp $

    /**
    * Project : Technical Project Manager (IEEE like)
    *
    * This page prints a particular instance of project
    *
    * @package mod-techproject
    * @subpackage framework
    * @category mod
    * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
    * @date 2008/03/03
    * @version phase1
    * @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
    * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
    */

    require_once('../../config.php');
    require_once($CFG->libdir.'/tablelib.php');
    require_once($CFG->dirroot.'/mod/techproject/lib.php');
    require_once($CFG->dirroot.'/mod/techproject/locallib.php');
    require_once($CFG->dirroot.'/mod/techproject/notifylib.php');

	require_js($CFG->wwwroot.'/mod/techproject/js/js.js');
    
    // fixes locale for all date printing.
    setLocale(LC_TIME, substr(current_language(), 0, 2));

    $id = required_param('id', PARAM_INT);   // module id
    $view = optional_param('view', @$_SESSION['currentpage'], PARAM_CLEAN);   // viewed page id
    
    $nohtmleditorneeded = true;
    $editorfields = '';

    $timenow = time();
    
    // get some useful stuff...
    if (! $cm = get_coursemodule_from_id('techproject', $id)) {
        error('Course Module ID was incorrect');
    }
    if (! $course = get_record('course', 'id', $cm->course)) {
        error('Course is misconfigured');
    }
    if (! $project = get_record('techproject', 'id', $cm->instance)) {
        error('Course module is incorrect');
    }
    
    require_login($course->id, false, $cm);
    
    if (@$CFG->enableajax){
        require_js(array('yui_yahoo',
                         'yui_dom',
                         'yui_event',
                         'yui_dragdrop',
                         'yui_connection'));
    }
    
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    $systemcontext = get_context_instance(CONTEXT_SYSTEM, 0);

    $strprojects = get_string('modulenameplural', 'techproject');
    $strproject  = get_string('modulename', 'techproject');
    $straction = (@$action) ? '-> '.get_string(@$action, 'techproject') : '';

    // get some session toggles if possible
    if (array_key_exists('editmode', $_GET) && !empty($_GET['editmode'])){
    	$_SESSION['editmode'] = $_GET['editmode'];
    } else {
        if (!array_key_exists('editmode', $_SESSION))
            $_SESSION['editmode'] = 'off';
    }
    $USER->editmode = $_SESSION['editmode'];

    // check current group and change, for anyone who could
	if (!$groupmode = groups_get_activity_groupmode($cm, $course)){ // groups are being used ?
		$currentGroupId = 0;
	} else {
        $changegroup = isset($_GET['group']) ? $_GET['group'] : -1;  // Group change requested?
        if (isguest()){ // for guests, use session
            if ($changegroup >= 0){
                $_SESSION['guestgroup'] = $changegroup;
            }
            $currentGroupId = 0 + @$_SESSION['guestgroup'];
        } else { // for normal users, change current group
            $currentGroupId = 0 + get_and_set_current_group($course, $groupmode, $changegroup);
            if (!groups_is_member($currentGroupId , $USER->id) && !has_capability('moodle/site:doanything', $systemcontext)) $USER->editmode = "off";
        }
    }

    // ...display header...
    print_header_simple(format_string($project->name), 
                        '',
                        build_navigation(array(), $cm),
                        '', 
                        '', 
                        true, 
                        update_module_button($cm->id, $course->id, $strproject), navmenu($course, $cm));

    echo "<div align=\"right\">";
    echo techproject_edition_enable_button($cm, $course, $project, $USER->editmode);
    echo "</div>";
    // ...and if necessary set default action
    if (has_capability('mod/techproject:gradeproject', $context)) {
        if (empty($action)) { // no action specified, either go straight to elements page else the admin page
			$action = 'teachersview';
        }
    }
    elseif (!isguest()) { // it's a student then
        if (!$cm->visible) {
            notice(get_string('activityiscurrentlyhidden'));
        }
    	if ($groupmode == SEPARATEGROUPS && !$currentGroupId && !$project->ungroupedsees){
    	    $action = 'notingroup';
    	}
		if ($timenow < $project->projectstart) {
			$action = 'notavailable';
		} elseif (!@$action) {
			$action = 'studentsview';
		}
    } else { // it's a guest, just watch if possible!
        if ($project->guestsallowed){
            $action = 'guestview';
        } else {
            $action = 'notavailable';
        }
    }
    
    // ...log activity...
    add_to_log($course->id, 'techproject', 'view', "view.php?id=$cm->id", $project->id, $cm->id);

	// ...Fonction for hide/show some information
	echo"
		<script type=\"text/javascript\">
		function toggle(i,n) {
			e = document.getElementById(n);
			if (e.style.display == 'none') {
				e.style.display = 'block';
				document.images['img' + i].src = '{$CFG->wwwroot}/mod/techproject/pix/p/switch_minus.gif';
	";
    if (@$CFG->enableajax){
        echo "
                    var sUrl = '{$CFG->wwwroot}/mod/techproject/ajax/updatecollapse.php?id={$cm->id}&entity={$view}&userid={$USER->id}&state=0&entryid=' + i;
                    var transaction = YAHOO.util.Connect.asyncRequest('GET', sUrl, null, null);
        ";
    }
    echo "
			} else {
				e.style.display = 'none';
				document.images['img' + i].src = '{$CFG->wwwroot}/mod/techproject/pix/p/switch_plus.gif';
	";
    if (@$CFG->enableajax){
            echo "
                var sUrl = '{$CFG->wwwroot}/mod/techproject/ajax/updatecollapse.php?id={$cm->id}&entity={$view}&userid={$USER->id}&state=1&entryid=' + i;
                var transaction = YAHOO.util.Connect.asyncRequest('GET', sUrl, null, null);
    ";
}
    echo "
			}
		}

		function toggle_show(i,n) {
			e = document.getElementById(n);
			if (e.style.display == 'none') {
				e.style.display = 'block';
				document.images['eye' + i].src = '{$CFG->wwwroot}/mod/techproject/pix/p/show.gif';
			} else {
				e.style.display = 'none';
				document.images['eye' + i].src = '{$CFG->wwwroot}/mod/techproject/pix/p/hide.gif';
			}
		}
		</script> 
	";
    require_js(array('yui_yahoo', 'yui_event', 'yui_connection'));


    /****************** display final grade (for students) ************************************/
    if ($action == 'displayfinalgrade' ) {
    	echo "Fin de projet, affichage des notes";
    	//==========> Y'as plus qu'a remplir
    
    
    /****************** assignment not available (for students)***********************/
    } elseif ($action == 'notavailable') {
        print_heading(get_string('notavailable', 'techproject'));

    /****************** student's view  ***********************/
    } elseif ($action == 'studentsview') {

		if ($timenow > $project->projectend) { // if project is over, just cannot change anything more
		    print_simple_box('<span class="inconsistency">'.get_string('projectisover','techproject').'</span>', 'center', '70%');
		    $USER->editmode = 'off';
		}
    	
            /// Print settings and things in a table across the top
        echo '<table width="100%" border="0" cellpadding="3" cellspacing="0"><tr valign="top">';

        /// Allow the student to change groups (for this session), seeing other's work
        if ($groupmode){ // if group are used
            $groups = groups_get_all_groups($course->id);
            if ($groups){
                $grouptable = array();
                foreach($groups as $aGroup){
                    // i can see only the groups i belong to
                    if (($groupmode == SEPARATEGROUPS) && !groups_is_member($aGroup->id, $USER->id)) continue;
                    // mark group as mine if i am member
                    if (($groupmode == VISIBLEGROUPS) && groups_is_member($aGroup->id, $USER->id)) $aGroup->name .= ' (*)';
                    $grouptable[$aGroup->id] = $aGroup->name;
                }
                echo '<td>';
                  echo '<form name="groupchooser" action="#" method="GET">';
                  echo "<input type=\"hidden\" name=\"id\" value=\"{$cm->id}\" />";
                  print_string('currentgroup', 'techproject');
                  choose_from_menu($grouptable, 'group', $currentGroupId, get_string('nogroup','techproject'), 'document.groupchooser.submit();');
                  echo "</form>";
                echo ' <span style="font-size : smaller">(*) '. get_string('mygroupsadvice', 'techproject') . '</td>';
            }
        }
        echo '</table>';    
    	
        // ungrouped students can view group 0's project (teacher's) but not change it if ungroupedsees is off.
        // in visible mode, student from other groups cannot edit our material.
    	if ($groupmode != SEPARATEGROUPS && (!$currentGroupId || !groups_is_member($currentGroupId, $USER->id))) {
    	    if (!$project->ungroupedsees){
    	        $USER->editmode = 'off';
    	    }
			include('techproject.php');
		} else { // just view unique project workspace
			include('techproject.php');
	    }
    }

    /****************** guest's view - display projects without editing capabilities  ************/
    elseif ($action == 'guestview') {

        $demostr = '';
        if (!$project->guestscanuse || $currentGroupId != 0){ // guest can sometimes edit group 0
            $USER->editmode = 'off';
        } elseif ($project->guestscanuse && !$currentGroupId && $timenow < $project->projectend) { // guest could have edited but project is closed
            $demostr = '(' . get_string('demomodeclosedproject', 'techproject') . ') ' . helpbutton('demomode', get_string('demomode', 'techproject'), 'techproject', true, false, '',true);
		    $USER->editmode = 'off';
		} else {
           $demostr = '(' . get_string('demomode', 'techproject') . ') ' . helpbutton('demomode', get_string('demomode', 'techproject'), 'techproject', true, false, '',true);
        }
    
        /// Print settings and things in a table across the top
        echo '<table width="100%" border="0" cellpadding="3" cellspacing="0"><tr valign="top">';

        /// Allow the guest to change groups (for this session) only for visible groups
        if ($groupmode == VISIBLEGROUPS) {
            $groups = groups_get_all_groups($course->id);
            if ($groups){
                $grouptable = array();
                foreach($groups as $aGroup){
                    $grouptable[$aGroup->id] = $aGroup->name;
                }
                echo '<td>';
                  echo '<form name="groupchooser" action="#" method="GET">';
                  echo "<input type=\"hidden\" name=\"id\" value=\"{$cm->id}\" />";
                  print_string('currentgroup', 'techproject');
                  choose_from_menu($grouptable, 'group', $currentGroupId, get_string('nogroup','techproject'), 'document.groupchooser.submit();');
                  echo "</form>";
                echo '</td>';
            }
        }    	
        echo '</table>';    
    	
    	include('techproject.php');    	

    /****************** teacher's view - display admin page  ************/
    } elseif ($action == 'teachersview') {
        /// Check to see if groups are being used in this workshop
        /// and if so, set $currentGroupId to reflect the current group
        $changegroup = isset($_REQUEST['group']) ? $_REQUEST['group'] : -1 ;  // Group change requested?
        $groupmode = groups_get_activity_groupmode($cm, $course);   // Groups are being used?
        $currentGroupId = 0 + get_and_set_current_group($course, $groupmode, $changegroup); 
        
        /// Print settings and things in a table across the top
        echo '<table width="100%" border="0" cellpadding="3" cellspacing="0"><tr valign="top">';

        /// Allow the teacher to change groups (for this session)
        if ($groupmode) {
            $groups = groups_get_all_groups($course->id);
            if (!empty($groups)){
                $grouptable = array();
                foreach($groups as $aGroup){
                    $grouptable[$aGroup->id] = $aGroup->name;
                }
                echo '<td>';
                  echo '<form name="groupchooser" action="#" method="GET">';
                  echo "<input type=\"hidden\" name=\"id\" value=\"{$cm->id}\" />";
                  print_string('currentgroup', 'techproject');
                  choose_from_menu($grouptable, 'group', $currentGroupId, get_string('nogroup','techproject'), "document.forms['groupchooser'].submit()");
                  echo "</form>";
                echo '</td>';
            }
        }    	
        echo '</tr></table>';    
    	
    	if (empty($currentGroupId)){
    		$currentGroupId = 0;
    	}
    	include('techproject.php');

    /****************** show description  ************/
    } elseif ($action == 'showdescription') {
        techproject_print_assignement_info($project);
        print_simple_box(format_text($project->description, $project->format), 'center', '70%', '', 5, 'generalbox', 'intro');
        print_continue($_SERVER["HTTP_REFERER"]);

    /*************** student is not in a group **************************************/
    } elseif ($action == 'notingroup') {
		print_simple_box(format_text(get_string('notingroup', 'techproject'), 'HTML'), 'center', '70%', '', 5, 'generalbox', 'intro');
		print_continue($_SERVER["HTTP_REFERER"]);     

    /*************** no man's land **************************************/
    } else {
        error("Fatal Error: Unknown Action: ".$action."\n");
    }

    if (empty($nohtmleditorneeded) and $usehtmleditor) {
        use_html_editor($editorfields);
    }

    print_footer($course);

?>