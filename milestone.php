<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

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

// Controller.

if ($work == 'add' || $work == 'update') {
    include($CFG->dirroot.'/mod/techproject/edit_milestone.php');
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
    if ($work) {
        include($CFG->dirroot.'/mod/techproject/milestones.controller.php');
    }

    echo $pagebuffer;
    techproject_print_milestones($project, $currentgroupid, NULL, $cm->id);
       if ($USER->editmode == 'on' && (has_capability('mod/techproject:changemiles', $context))) {
           echo "<br/><a href='view.php?id={$cm->id}&amp;work=add'>".get_string('addmilestone','techproject')."</a>";
           echo " - <a href='view.php?id={$cm->id}&amp;work=clearall'>".get_string('clearall','techproject')."</a>";
           echo " - <a href='view.php?id={$cm->id}&amp;work=sortbydate'>".get_string('sortbydate','techproject')."</a>";
       }
}