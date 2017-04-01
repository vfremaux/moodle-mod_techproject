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
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
namespace mod_techproject\output;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/techproject/renderer.php');

class summary_renderer extends \mod_techproject_renderer {

    public function summary(&$projectheading, &$context) {

        $str = '<table width="80%">';
        $str .= '<tr valign="top">';
        $str .= '<th align="left" width="60%">';
        $str .= get_string('summaryforproject', 'techproject');
        $str .= $this->output->help_icon('leaves', 'techproject', true);
        $str .= '</th>';
        $str .= '<th align="left" width="40%">';
        $str .= $projectheading->title;;
        $str .= '</th>';
        $str .= '</tr>';

        if (has_capability('mod/techproject:viewpreproductionentities', $context)) {
            $str .= '<tr class="sectionrow">';
            $str .= '<td align="left">'.get_string('totalrequ', 'techproject').'</td>';
            $str .= '<td align="left">'.(0 + @$projectheading->countrequ).'</td>';
            $str .= '</tr>';
            $str .= '<tr class="subsectionrow">';
            $str .= '<td align="left"><span class="level4">&nbsp;&nbsp;&nbsp;' . get_string('covered', 'techproject') . '</span></td>';
            $str .= '<td align="left">'.(0 + @$projectheading->coveredrequ).'</td>';
            $str .= '</tr>';
            $str .= '<tr class="subsectionrow">';
            $str .= '<td align="left"><span class="level4">&nbsp;&nbsp;&nbsp;' . get_string('uncovered', 'techproject') . '</span></td>';
            $str .= '<td align="left">'.(0 + @$projectheading->uncoveredrequ).'</td>';
            $str .= '</tr>';
            $str .= '<tr class="sectionrow">';
            $str .= '<td align="left">'.get_string('totalspec', 'techproject').'</td>';
            $str .= '<td align="left">'.(0 + @$projectheading->countspec).'</td>';
            $str .= '</tr>';
            $str .= '<tr class="subsectionrow">';
            $str .= '<td align="left"><span class="level4">&nbsp;&nbsp;&nbsp;' . get_string('covered', 'techproject') . '</span></td>';
            $str .= '<td align="left">'.(0 + @$projectheading->coveredspec).'</td>';
            $str .= '</tr>';
            $str .= '<tr class="subsectionrow">';
            $str .= '<td align="left"><span class="level4">&nbsp;&nbsp;&nbsp;' . get_string('uncovered', 'techproject') . '</span></td>';
            $str .= '<td align="left">'.(0 + @$projectheading->uncoveredspec).'</td>';
            $str .= '</tr>';
        }
        $str .= '<tr class="sectionrow">';
        $str .= '<td align="left">' . get_string('totaltask', 'techproject') . '</td>';
        $str .= '<td align="left">'.(0 + @$projectheading->counttask).'</td>';
        $str .= '</tr>';
        $str .= '<tr class="sectionrow">';
        $str .= '<td align="left">' . get_string('totaldeliv', 'techproject') . '</td>';
        $str .= '<td align="left">'.(0 + @$projectheading->countdeliv).'</td>';
        $str .= '</tr>';
        $str .= '</table>';

        return $str;
    }
}