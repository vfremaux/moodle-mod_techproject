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
 * @package mod-techproject
 * @category mod
 * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
 * @date 2008/03/03
 * @version phase1
 * @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
defined('MOODLE_INTERNAL') || die();

if (!has_capability('mod/techproject:manage', $context)) {
    print_error(get_string('notateacher','techproject'));
    return;
}

if ($work == 'update') {
    $criterion = new StdClass;
    $criterion->id = required_param('itemid', PARAM_INT);
    $criterion->projectid = $project->id;
    $criterion->criterion = required_param('criterion', PARAM_ALPHANUM);
    $criterion->label = required_param('label', PARAM_TEXT);
    $criterion->weight = required_param('weight', PARAM_INT);
    $criterion->isfree = optional_param('isfree', 0, PARAM_INT);
    if ($DB->get_record('techproject_criterion', array('id' => $criterion->id))) {
        $DB->update_record('techproject_criterion', $criterion);
    } else {
        $DB->insert_record('techproject_criterion', $criterion);
    }
} else if ($work == 'doconfirmdelete') {
    $criterion = new StdClass;
    $criterion->id = required_param('item', PARAM_INT);
    $criterion->isfree = optional_param('isfree', 0, PARAM_INT);
    if ($DB->get_record('techproject_criterion', array('id' => $criterion->id))) {
        $DB->delete_records('techproject_criterion', array('id' => $criterion->id));
        $params = array('projectid' => $project->id, 'criterion' => $criterion->id);
        $DB->delete_records('techproject_assessment', $params);
    }
}
if ($work == 'dodelete') {
    $criterion = new StdClass;
    $criterion->id = required_param('item', PARAM_INT);
    $criterion->isfree = optional_param('isfree', 0, PARAM_INT);
    echo $OUTPUT->heading(get_string('confirmdeletecriteria', 'techproject'));
    echo $OUTPUT->box(get_string('criteriadeleteadvice', 'techproject'), 'center', '70%');
?>
<script type="text/javascript">

function senddata(cmd) {
    document.confirmdeleteform.work.value="do" + cmd;
    document.confirmdeleteform.submit();
}

function cancel() {
    document.confirmdeleteform.submit();
}
</script>
<center>
<form name="confirmdeleteform" method="get" action="view.php">
<input type="hidden" name="id" value="<?php p($cm->id) ?>" />
<input type="hidden" name="work" value="" />
<input type="hidden" name="item" value="<?php p($criterion->id) ?>" />
<input type="hidden" name="isfree" value="<?php p($criterion->isfree) ?>" />
<input type="button" name="go_btn" value="<?php print_string('continue') ?>" onclick="senddata('confirmdelete')" />
<input type="button" name="cancel_btn" value="<?php print_string('cancel') ?>" onclick="cancel()" />
</form>
</center>
<?php

// Criteria update form ***************************************************.

} else {
    $freecriteria = $DB->get_records_select('techproject_criterion', "projectid = {$project->id} AND isfree = 1");
    $criteria = $DB->get_records_select('techproject_criterion', "projectid = {$project->id} AND isfree = 0");
?>
<center>
<table width="80%" cellspacing="10" style="padding : 10px">
    <tr>
        <td valign="top">
            <?php 
            echo $OUTPUT->heading(get_string('freecriteriaset', 'techproject') . ' ' . $OUTPUT->help_icon('freecriteriaset', 'techproject', false)) 
            ?>
            <script type="text/javascript">
            //<![CDATA[
            function senddatafree() {
                if (document.forms['freecriteriaform'].criterion == '') {
                    alert('<?php print_string('emptycriterion','techproject') ?>');
                    return;
                }
                document.forms['freecriteriaform'].work.value = "update";
                document.forms['freecriteriaform'].submit();
            }
            function changefree(itemid, criterion, label, weight) {
                document.forms['freecriteriaform'].itemid.value = itemid;
                document.forms['freecriteriaform'].criterion.value = criterion;
                document.forms['freecriteriaform'].label.value = label;
                document.forms['freecriteriaform'].weight.value = weight;
                document.forms['freecriteriaform'].work.value = "update";
            }
            //]]>
            </script>
            <form name="freecriteriaform" method="post" action="view.php">
                <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
                <input type="hidden" name="work" value="" />
                <?php
                $table->head = array();
                $table->width = "100%";
                $table->align = array('left', 'left');
                if ($freecriteria) {
                    foreach ($freecriteria as $acriterion) {
                        $params = array('id' => $cm->id, 'work' => 'dodelete', 'isfree' => 1, 'item' => $acriterion->id);
                        $linkurl = new moodle_url('/mod/techproject/view.php', $params);
                        $pixurl = $OUTPUT->pix_url('/t/delete');
                        $pix = '<img src="'.$pixurl.'" title="'.get_string('deletecriteria', 'techproject').'" />';
                        $links = '<a href="'.$linkurl.'">'.$pix.'</a>';
                        $jshandler = 'javascript:changefree(\''.$acriterion->id.'\', \''.$acriterion->criterion.'\', \''.$acriterion->label.'\', \''.$acriterion->weight.'\')';
                        $pixurl = $OUTPUT->pix_url('/t/edit');
                        $pix = '<img src="'.$pixurl.'" title="'.get_string('editcriteria', 'techproject').'" />';
                        $links .= '<a href="'.$jshandler.'">'.$pix.'</a>';
                        $table->data[] = array('<b>'.$acriterion->criterion.'</b> '.$acriterion->label.' ( x '.$acriterion->weight.')', $links);
                    }
                }
                echo html_writer::table($table);
                unset($table);
                echo $renderer->criteriaheader();
                ?>
                <input type="hidden" name="itemid" value="0" />
                <input type="hidden" name="isfree" value="1" />
            </form>
        </td>
        <td valign="top">
            <?php
            echo $OUTPUT->heading(get_string('itemcriteriaset', 'techproject').' '.$OUTPUT->help_icon('itemcriteriaset', 'techproject', false))
            ?>
            <script type="text/javascript">
            //<![CDATA[
            function senddata() {
                if (document.forms['criteriaform'].criterion == '') {
                    alert('<?php print_string('emptycriterion','techproject') ?>');
                    return;
                }
                document.forms['criteriaform'].work.value = "update";
                document.forms['criteriaform'].submit();
            }
            function change(itemid, criterion, label, weight) {
                document.forms['criteriaform'].itemid.value = itemid;
                document.forms['criteriaform'].criterion.value = criterion;
                document.forms['criteriaform'].label.value = label;
                document.forms['criteriaform'].weight.value = weight;
                document.forms['criteriaform'].work.value = "update";
            }
            //]]>
            </script>
            <form name="criteriaform" method="post" action="view.php">
                <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
                <input type="hidden" name="work" value="" />
                <?php 
                $table->head = array();
                $table->width = "100%";
                $table->align = array('left', 'left');
                if ($criteria) {
                    foreach ($criteria as $acriterion) {
                        $linkurl = new moodle_url('/mod/techproject/view.php', array('id' => $cm->id, 'work' => 'dodelete', 'isfree' => 0, 'item' => $acriterion->id));
                        $pixurl = $OUTPUT->pix_url('/t/delete');
                        $pix = '<img src="'.$pixurl.'" />';
                        $links = '<a href="'.$linkurl.'">'.$pix.'</a>';
                        $jshandler = 'javascript:change(\''.$acriterion->id'\',\''.$acriterion->criterion.'\',\''.$acriterion->label.'\',\''.$acriterion->weight.'\')';
                        $pixurl = $OUTPUT->pix_url('/t/edit');
                        $pix = '<img src="'.$pixurl.'" />';
                        $links .= '<a href="'.$jshandler.'">'.$pix.'</a>';
                        $table->data[] = array('<b>'.$acriterion->criterion.'</b> '.$acriterion->label.' ( x '.$acriterion->weight.')', $links);
                    }
                }
                echo html_writer::table($table);
                unset($table);
                ?>
                <table>
                    <tr>
                        <td align="right">
                            <?php print_string('criterion', 'techproject') ?>&nbsp;
                        </td>
                        <td> 
                            <input type="text" name="criterion" value="" />
                        </td>
                    </tr>
                    <tr>
                        <td align="right">
                            <?php print_string('label', 'techproject') ?>&nbsp;
                        </td>
                        <td> 
                            <input type="text" name="label" value="" />
                        </td>
                    </tr>
                    <tr>
                        <td align="right">
                            <?php print_string('weight', 'techproject') ?>&nbsp;
                        </td>
                        <td> 
                            <input type="text" name="weight" value="" />
                        </td>
                    </tr>
                    <tr>
                        <td>
                        </td>
                        <td align="right"> 
                            <input type="button" name="go_btn" value="<?php print_string('save', 'techproject') ?>" onclick="senddata('save')" />
                        </td>
                    </tr>
                </table>
                <input type="hidden" name="itemid" value="0" />
                <input type="hidden" name="isfree" value="0" />
             </form>
        </td>
    </tr>
</table>
</center>
<?php
}

