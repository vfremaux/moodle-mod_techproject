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

    /**
    * a form constraint checking function
    * @param object $project the surrounding project cntext
    * @param object $milestone form object to be checked
    * @return a control hash array telling error statuses
    */
/// Controller

if ($work == 'add' || $work == 'update') {
	include 'edit_milestone.php';
/// Clear all *********************************************************

} elseif ($work == 'clearall') {
	echo $pagebuffer;
    echo '<center>';
	echo $OUTPUT->heading(get_string('clearallmilestones','techproject')); 
    echo $OUTPUT->box(get_string('clearwarning','techproject'), 'generalbox'); 
    ?>
    <script type="text/javascript">
    function senddata(){
        document.clearmilestoneform.work.value = 'doclearall';
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
	if ($work){
		include 'milestones.controller.php';
	}
	echo $pagebuffer;
	techproject_print_milestones($project, $currentGroupId, NULL, $cm->id);
       if ($USER->editmode == 'on' && (has_capability('mod/techproject:changemiles', $context))) {
   		echo "<br/><a href='view.php?id={$cm->id}&amp;work=add'>".get_string('addmilestone','techproject')."</a>";
   		echo " - <a href='view.php?id={$cm->id}&amp;work=clearall'>".get_string('clearall','techproject')."</a>";
   		echo " - <a href='view.php?id={$cm->id}&amp;work=sortbydate'>".get_string('sortbydate','techproject')."</a>";
   	}
}
?>