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
    function block($title, $content) {
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
    function bar_graph_over($value, $over, $width = 50, $height = 4, $maxover = 60) {
        global $CFG;

        if ($value == -1) {
            $pixurl = $this->output->pix_url('p/graypixel', 'techproject');
            $title = get_string('nc', 'techproject');
            return '<img class="techproject-bargraph" src="'.$pixurl.'" title="'.$title.'" width="'.$width.'" />';
        }
        $done = floor($width * $value / 100);
        $todo = floor($width * (1 - $value / 100));
        $pixurl = $this->output->pix_url('p/greenpixel', 'techproject');
        $bargraph = '<img class="techproject-bargraph" src="'.$pixurl.'" title="'.$value.'%" width="'.$done.'" />';
        $pixurl = $this-output->pix_url('p/bluepixel', 'techproject');
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
}