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
require_once($CFG->dirroot.'/lib/uploadlib.php');

// Controller.
if ($work == 'add' || $work == 'update') {
    include($CFG->dirroot.'/mod/techproject/edit_deliverable.php');

// Group operation form.

} elseif ($work == 'groupcmd') {
    echo $pagebuffer;
    $ids = required_param_array('ids', PARAM_INT);
    $cmd = required_param('cmd', PARAM_ALPHA);
?>
    <center>
    <?php echo $OUTPUT->heading(get_string('groupoperations', 'techproject')); ?>
    <?php echo $OUTPUT->heading(get_string("group$cmd", 'techproject'), 3); ?>
    <script type="text/javascript">
    //<![CDATA[
    function senddata(cmd) {
        document.forms['groupopform'].work.value="do" + cmd;
        document.forms['groupopform'].submit();
    }
    function cancel() {
        document.forms['groupopform'].submit();
    }
    //]]>
    </script>
    <form name="groupopform" method="get" action="view.php">
    <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
    <input type="hidden" name="work" value="" />
<?php
        foreach ($ids as $anid) {
            echo "<input type=\"hidden\" name=\"ids[]\" value=\"{$anid}\" />\n";
        }
        if (($cmd == 'move') || ($cmd == 'copy')) {
            echo get_string('to', 'techproject');
            if (@$project->projectusesrequs) $options['requs'] = get_string('requirements', 'techproject');
            if (@$project->projectusesspecs) $options['specs'] = get_string('specifications', 'techproject');
            $options['tasks'] = get_string('tasks', 'techproject');
            echo html_writer::select($options, 'to', '', 'choose');
        }
?>
    <input type="button" name="go_btn" value="<?php print_string('continue') ?>" onclick="senddata('<?php p($cmd) ?>')" />
    <input type="button" name="cancel_btn" value="<?php print_string('cancel') ?>" onclick="cancel()" />
    </form>
    </center>
<?php
} else {
    if ($work) {
         include($CFG->dirroot.'/mod/techproject/deliverables.controller.php');
    }
    echo $pagebuffer;
?>
    <script type="text/javascript">
    //<![CDATA[
    function sendgroupdata() {
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
    techproject_print_deliverables($project, $currentgroupid, 0, $cm->id);
    if ($USER->editmode == 'on' && has_capability('mod/techproject:changedelivs', $context)) {
        echo "<br/><a href='view.php?id={$cm->id}&amp;work=add&amp;fatherid=0'>".get_string('adddeliv','techproject')."</a>&nbsp; ";
        techproject_print_group_commands();
    }
?>
    </form>
<?php
}
