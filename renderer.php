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
 * @package mod_techproject
 * @category mod
 * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
 * @date 2008/03/03
 * @version phase1
 * @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
defined('MOODLE_INTERNAL') || die();

class mod_techproject_renderer extends plugin_renderer_base {

    /**
     * prints a "block-like" widget
     *
     */
    public function block($title, $content) {
        $str = '';

        $str .= '<div id="inst" class="block_techproject block detail">';
        $str .= '<div class="header">'.$title.'</div>';
        $str .= '<div class="content">';
        foreach ($content as $elm) {
            $str .= '<li style="list-style:none"> '.$elm;
        }
        $str .= '</div>';
        $str .= '<div class="footer">';
        $str .= '</div>';
        $str .= '</div>';

        return $str;
    }

    /**
     * prints a graphical bargraph with overhead signalling
     * @param value the current value claculated against the regular width of the bargraph
     * @param over the value of the overhead, in the width based scaling
     * @param width the physical width of the bargraph (in pixels)
     * @param height the physical height of the bargraph (in pixels)
     * @param maxover the overhead width limit. Will produce an alternate overhead rendering if over is over.
     *
     */
    public function bar_graph_over($value, $over, $width = 50, $height = 4, $maxover = 60) {

        if ($value == -1) {
            $pixurl = $this->output->pix_url('p/graypixel', 'techproject');
            $title = get_string('nc', 'techproject');
            return '<img class="techproject-bargraph" src="'.$pixurl.'" title="'.$title.'" width="'.$width.'" />';
        }
        $bargraph = '';
        $done = floor($width * $value / 100);
        $todo = floor($width * (1 - $value / 100));
        $pixurl = $this->output->pix_url('p/greenpixel', 'techproject');
        $bargraph .= '<img class="techproject-bargraph" src="'.$pixurl.'" title="'.$value.'%" width="'.$done.'" />';
        $pixurl = $this->output->pix_url('p/bluepixel', 'techproject');
        $bargraph .= '<img class="techproject-bargraph" src="'.$pixurl.'" title="'.$value.'%" width="'.$todo.'" />';
        if ($over) {
            $displayover = (round($over / $width * 100)).'%';
            if ($over < $maxover) {
                $pixurl = $this->output->pix_url('p/redpixel', 'techproject');
                $title = get_string('overdone', 'techproject').': '.$displayover;
                $bargraph .= '<img class="techproject-bargraph" src="'.$pixurl.'" title="'.$title.'" width="'.$over.'" />';
            } else {
                $pixurl = $this->output->pix_url('p/maxover', 'techproject');
                $title = get_string('overoverdone', 'techproject').': '.$displayover;
                $bargraph .= '<img class="techproject-bargraph" src="'.$pixurl.'" title="'.$title.'" width="'.$width.'" />';
            }
        }
        return $bargraph;
    }

    public function criteria_header() {

        $str = '';

        $str .= '<table>';
        $str .= '<tr>';
        $str .= '<td align="right">';
        $str .= get_string('criterion', 'techproject').'&nbsp;';
        $str .= '</td>';
        $str .= '<td>';
        $str .= '<input type="text" name="criterion" value="" />';
        $str .= $this->output->help_icon('criterion', 'techproject');
        $str .= '</td>';
        $str .= '</tr>';
        $str .= '<tr>';
        $str .= '<td align="right">';
        $str .= get_string('label', 'techproject').'&nbsp;';
        $str .= '</td>';
        $str .= '<td>';
        $str .= '<input type="text" name="label" value="" />';
        $str .= $this->output->help_icon('label', 'techproject');
        $str .= '</td>';
        $str .= '</tr>';
        $str .= '<tr>';
        $str .= '<td align="right">';
        $str .= get_string('weight', 'techproject').'&nbsp;';
        $str .= '</td>';
        $str .= '<td>';
        $str .= '<input type="text" name="weight" value="" />';
        $str .= $this->output->help_icon('weight', 'techproject');
        $str .= '</td>';
        $str .= '</tr>';
        $str .= '<tr>';
        $str .= '<td>';
        $str .= '</td>';
        $str .= '<td align="right">';
        $str .= '<input type="button" name="go_btn" value="'.get_string('save', 'techproject').'" onclick="senddatafree(\'save\')" />';
        $str .= '</td>';
        $str .= '</tr>';
        $str .= '</table>';

        return $str;
    }

    public function criteria_form($set, $criteria, &$cm) {

        $str = '';

        $formurl = new moodle_url('/mod/techproject/view.php');
        $str .= '<form name="'.$set.'form" method="post" action="'.$formurl.'">';
        $str .= '<input type="hidden" name="id" value="'.$cm->id.'" />';
        $str .= '<input type="hidden" name="work" value="" />';
        $table->head = array();
        $table->width = "100%";
        $table->align = array('left', 'left');
        foreach ($criteria as $acriterion) {
            $params = array('id' => $cm->id, 'work' => 'dodelete', 'isfree' => 0, 'item' => $acriterion->id);
            $linkurl = new moodle_url('/mod/techproject/view.php', $params);
            $pixurl = $OUTPUT->pix_url('/t/delete');
            $pix = '<img src="'.$pixurl.'" />';
            $links = '<a href="'.$linkurl.'">'.$pix.'</a>';
            $jshandler = 'javascript:change(\''.$acriterion->id.'\', \''.$acriterion->criterion.'\', \''.$acriterion->label.'\', \''.$acriterion->weight.'\')';
            $pixurl = $OUTPUT->pix_url('/t/edit');
            $pix = '<img src="'.$pixurl.'" />';
            $links .= '<a href="'.$jshandler.'">'.$pix.'</a>';
            $table->data[] = array('<b>'.$acriterion->criterion.'</b> '.$acriterion->label.' ( x '.$acriterion->weight.')', $links);
        }
        $str .= html_writer::table($table);

        $str .= $this->criteria_header();
        $str .= '<input type="hidden" name="itemid" value="0" />';
        $str .= '<input type="hidden" name="isfree" value="0" />';
        $str .= '</form>';

        return $str;
    }

    public function criteria_form_script($set) {

        $str = '';

        $str .= '<script type="text/javascript">';
        $str .= '//<![CDATA[';
        $str .= 'function senddatafree() {';
        $str .= '    if (document.forms[\''.$set.'form\'].criterion == \'\') {';
        $str .= '        alert(\''.get_string('emptycriterion', 'techproject').'\');';
        $str .= '        return;';
        $str .= '    }';
        $str .= '    document.forms[\''.$set.'form\'].work.value = "update";';
        $str .= '    document.forms[\''.$set.'form\'].submit();';
        $str .= '}';
        $str .= 'function changefree(itemid, criterion, label, weight) {';
        $str .= '    document.forms[\''.$set.'form\'].itemid.value = itemid;';
        $str .= '    document.forms[\''.$set.'form\'].criterion.value = criterion;';
        $str .= '    document.forms[\''.$set.'form\'].label.value = label;';
        $str .= '    document.forms[\''.$set.'form\'].weight.value = weight;';
        $str .= '    document.forms[\''.$set.'form\'].work.value = "update";';
        $str .= '}';
        $str .= '//]]>';
        $str .= '</script>';

        return $str;
    }

    public function criteria_delete_form($cm, $criterion) {
        $str = '';

        $formurl = new moodle_url('/mod/techproject/view.php');
        $str = '<form name="confirmdeleteform" method="get" action="'.$formurl.'">';
        $str = '<input type="hidden" name="id" value="'.$cm->id.'" />';
        $str = '<input type="hidden" name="work" value="" />';
        $str = '<input type="hidden" name="item" value="'.$criterion->id.'" />';
        $str = '<input type="hidden" name="isfree" value="'.$criterion->isfree.'" />';
        $str = '<input type="button" name="go_btn" value="'.get_string('continue').'" onclick="senddata(\'confirmdelete\')" />';
        $str = '<input type="button" name="cancel_btn" value="'.get_string('cancel').'" onclick="cancel()" />';
        $str = '</form>';

        return $str;
    }

    public function criteria_delete_form_script() {

        $str = '';

        $str .= '<script type="text/javascript">';
        $str .= 'function senddata(cmd) {';
        $str .= '    document.confirmdeleteform.work.value = "do" + cmd;';
        $str .= '    document.confirmdeleteform.submit();';
        $str .= '}';
        $str .= 'function cancel() {';
        $str .= '    document.confirmdeleteform.submit();';
        $str .= '}';
        $str .= '</script>';

        return $str;
    }

    public function group_op_form() {

        $str = '';

        $str = '<script type="text/javascript">';
        $str = '//<![CDATA[';
        $str = 'function senddata(cmd) {';
        $str = '    document.forms[\'groupopform\'].work.value = "do" + cmd;';
        $str = '    document.forms[\'groupopform\'].submit();';
        $str = '}';
        $str = 'function cancel() {';
        $str = '    document.forms[\'groupopform\'].submit();';
        $str = '}';
        $str = '//]]>';
        $str = '</script>';

        return $str;
    }

    public function group_op_form_group() {

        $str = '';

        $str .= '<script type="text/javascript">';
        $str .= '//<![CDATA[';
        $str .= 'function sendgroupdata() {';
        $str .= '    document.forms[\'groupopform\'].submit();';
        $str .= '}';
        $str .= '//]]>';
        $str .= '</script>';

        return $str;
    }
}