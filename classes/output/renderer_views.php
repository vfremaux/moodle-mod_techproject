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

class views_renderer extends \mod_techproject_renderer {

    public function assignee(&$project, $res, &$auser) {
        static $timeunits;

        if (!isset($timeunits)) {
            $timeunits = array(get_string('unset', 'techproject'),
                       get_string('hours', 'techproject'),
                       get_string('halfdays', 'techproject'),
                       get_string('days', 'techproject'));
        }

        $str = '<table width="100%">';
        $str .= '<tr>';
        $str .= '<td class="byassigneeheading level1">';

        $pixurl = $this->output->pix_url('/p/switch_minus', 'techproject');
        $jshandler = 'javascript:toggle(\''.$auser->id.'\', \'sub'.$auser->id.'\', false);';
        $hidesub = '<a href="'.$jshandler.'"><img name="img'.$auser->id.'" src="'.$pixurl.'" alt="collapse" /></a>';
        $str .= $hidesub.' '.get_string('assignedto', 'techproject').' '.fullname($auser).' '.$this->output->user_picture($auser);

        $str .= '</td>';
        $str .= '<td class="byassigneeheading level1" align="right">';

        if ($res) {
            $over = ($res->planned) ? round((($res->spent - $res->planned) / $res->planned) * 100) : 0;
            // Calculates a local alarm for lateness.
            $hurryup = '';
            if ($res->planned && ($res->spent <= $res->planned)) {
                $pixurl = $this->output->pix_url('/p/late', 'techproject');
                $pix = '<img src="'.$pixurl.'" title="'.get_string('hurryup', 'techproject').'" />';
                $hurryup = (round(($res->spent / $res->planned) * 100) > ($res->done / $res->count)) ? $pix : '';
            }
            $lateclass = ($over > 0) ? 'toolate' : 'intime';
            $workplan = get_string('assignedwork', 'techproject').' '.(0 + $res->planned).' '.$timeunits[$project->timeunit];
            $realwork = get_string('realwork', 'techproject');
            $realwork .= ' <span class="'.$lateclass.'">'.(0 + $res->spent).' '.$timeunits[$project->timeunit].'</span>';
            $completion = ($res->count != 0) ? $this->bar_graph_over($res->done / $res->count, $over, 100, 10) : $this->bar_graph_over(-1, 0);
            $str .= "{$workplan} - {$realwork} {$completion} {$hurryup}";
        }

        $str .= '</td>';
        $str .= '</tr>';
        $str .= '</table>';

        return $str;
    }

    public function tasks(&$project, &$cm, $currentgroupid, &$tasks, &$user) {
        global $DB;

        $str = '<table id="sub'.$user->id.'" width="100%">';

        // Get assigned tasks.
        if (!isset($tasks) || count($tasks) == 0 || empty($tasks)) {

            $str .= '<tr>';
            $str .= '<td>';
            $str .= $this->output->notification(get_string('notaskassigned', 'techproject'));
            $str .= '</td>';
            $str .= '</tr>';

        } else {
            foreach ($tasks as $tsk) {
                $haveassignedtasks = true;
                // Feed milestone titles for popup display.
                if ($milestone = $DB->get_record('techproject_milestone', array('id' => $tsk->milestoneid))) {
                    $tsk->milestoneabstract = $milestone->abstract;
                }

                $str .= '<tr>';
                $str .= '<td class="level2">';
                $str .= techproject_print_single_task($tsk, $project, $currentgroupid, $cm->id, count($tasks), true, 'SHORT_WITHOUT_ASSIGNEE_NOEDIT');
                $str .= '</td>';
                $str .= '</tr>';

            }
        }

        $str .= '</table>';

        return $str;
    }

    public function unassignedtasks(&$cm, &$unassignedtasks) {

        $str = '<table width="100%">';

        if (!isset($unassignedtasks) || count($unassignedtasks) == 0 || empty($unassignedtasks)) {

            $str .= '<tr>';
            $str .= '<td>';
            $str .= get_string('notaskunassigned', 'techproject');
            $str .= '</td>';
            $str .= '</tr>';

        } else {
            foreach ($unassignedtasks as $atask) {

                $str .= '<tr>';
                $str .= '<td class="level2">';
                $branch = techproject_tree_get_upper_branch('techproject_task', $atask->id, true, true);
                $str .= 'T'.implode('.', $branch) . '. ' . $atask->abstract;
                $title = get_string('detail', 'techproject');
                $pix = '<img src="'.$this->output->pix_url('p/hide', 'techproject').'" title="'.$title.'" />';
                $params = array('id' => $cm->id, 'view' => 'view_detail', 'objectClass' => 'task', 'objectId' => $atask->id);
                $linkurl = new moodle_url('/mod/techproject/view.php', $params);
                $str .= '&nbsp;<a href="'.$linkurl.'">'.$pix.'</a>';
                $str .= '</td>';
                $str .= '<td>';
                $str .= '</td>';
                $str .= '</tr>';
            }
        }

        $str .= '</table>';

        return $str;
    }
}