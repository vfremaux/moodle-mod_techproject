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

class gantt_renderer extends \mod_techproject_renderer {

    public function init($ganttid) {

        $str = '<center>';
        $str .= "<div style=\"width:90%px; height:610px; position:relative\" id=\"{$ganttid}\"></div>";

        $str .= "<script type=\"text/javascript\">\n";
        $str .= "
            /*<![CDATA[*/\n
            function createChartControl(htmlDiv)\n
            {\n
        ";

        return $str;
    }

    public function finish() {
        $str = "};\n";
        $str .= "/*]]>*/\n</script>\n";

        return $str;
    }

    public function all_tasks(&$parent, &$project, $group, &$unscheduled, &$assignees, &$tasks) {
        global $DB;

        $str = '';

        if (is_null($parent)) {
            $parentid = 0;
        } else {
            $parentid = $parent->id;
        }

        $select = " projectid = ? AND groupid = ? and fatherid = ? ";
        $tasks = $DB->get_records_select('techproject_task', $select, array($project->id, $group, $parentid), "assignee,taskstart");
        if ($tasks) {
            foreach ($tasks as $t) {

                // This version computes implicit dates based on parent or on project for lead tasks.
                if (!$t->taskstartenable && !$t->taskendenable) {
                    if ($t->fatherid == 0) {
                        $t->taskstart = $project->projectstart;
                        $t->taskend = $project->projectend;
                        $t->taskstartenable = 1;
                        $t->taskendenable = 1;
                    } else {
                        $t->taskstart = $parent->taskstart;
                        $t->taskend = $parent->taskend;
                        $t->taskstartenable = 1;
                        $t->taskendenable = 1;
                    }
                }

                $str .= $this->task($t);
                $ltasks = array();
                $str .= $this->all_tasks($t, $project, $group, $unscheduled, $assignees, $ltasks);
            }
        }

        return $str;
    }

    public function task(&$task) {
        global $DB;

        $str = '';
        $taskstart = $this->format_date($task->taskstart);
        $duration = ceil(($task->taskend - $task->taskstart) / DAYSECS * 8);
        $done = $task->done;
        $task->predecessor = 0;
        $user = $DB->get_record('user', array('id' => $task->assignee));
        $assignee = ($user) ? fullname($user) : '';
        $user = $DB->get_record('user', array('id' => $task->owner));
        $owner = ($user) ? fullname($user) : '';

        if ($task->fatherid == 0) {
            $str .= "var project{$task->id} = new GanttProjectInfo({$task->id}, \"$task->abstract\", new Date($taskstart));\n";
            $str .= "var Task{$task->id} = new GanttTaskInfo({$task->id}, \"$task->abstract\", new Date($taskstart), ";
            $str .= "$duration, $done, $task->predecessor, '$assignee', '$task->assignee', '$owner', $task->owner);\n";
            $str .= "project{$task->id}.addTask(Task{$task->id});";
        } else {
            $str .= "var Task{$task->id} = new GanttTaskInfo({$task->id}, \"$task->abstract\", new Date($taskstart), ";
            $str .= "$duration, $done, $task->predecessor, '$assignee', '$task->assignee', '$owner', $task->owner);\n";
            $str .= " Task{$task->fatherid}.addChildTask(Task{$task->id});\n";
        }

        return $str;
    }

    public function control($leadtasks) {
        global $CFG;

        $str = '';

        $months[get_string('jan', 'techproject')] = get_string('january', 'techproject');
        $months[get_string('feb', 'techproject')] = get_string('february', 'techproject');
        $months[get_string('mar', 'techproject')] = get_string('march', 'techproject');
        $months[get_string('apr', 'techproject')] = get_string('april', 'techproject');
        $months[get_string('may', 'techproject')] = get_string('may', 'techproject');
        $months[get_string('jun', 'techproject')] = get_string('june', 'techproject');
        $months[get_string('jul', 'techproject')] = get_string('july', 'techproject');
        $months[get_string('aug', 'techproject')] = get_string('august', 'techproject');
        $months[get_string('sep', 'techproject')] = get_string('september', 'techproject');
        $months[get_string('oct', 'techproject')] = get_string('october', 'techproject');
        $months[get_string('nov', 'techproject')] = get_string('november', 'techproject');
        $months[get_string('dec', 'techproject')] = get_string('december', 'techproject');

        $str .= "
        var ganttChartControl = new GanttChart();\n
        ganttChartControl.setLang('".substr(current_language(), 0, 2)."');
        // Setup paths and behavior\n
        ganttChartControl.setImagePath(\"{$CFG->wwwroot}/mod/techproject/js/dhtmlxGantt/codebase/imgs/\");\n
        ganttChartControl.setEditable(true);\n
        ganttChartControl.showTreePanel(true);\n
        ganttChartControl.showContextMenu(true);\n
        ganttChartControl.showDescTask(true,'n,s-f');\n
        ganttChartControl.showDescProject(true,'n,d');\n
        // localises monthnames
        ganttChartControl.setMonthNames(['".implode("','", array_values($months))."']);
        // localises shortmonthnames
        ganttChartControl.setShortMonthNames(['".implode("','", array_keys($months))."']);
         // Load data structure\n
        ";
        foreach ($leadtasks as $taskid) {
            $str .= "ganttChartControl.addProject(project{$taskid});\n";
        }
        $str .= "
        // attach events.
        ganttChartControl.attachEvent('onTaskEndDrag', gantt_handler_onTaskChangeBounds);
        ganttChartControl.attachEvent('onTaskEndResize', gantt_handler_onTaskChangeBounds);
        ganttChartControl.attachEvent('onTaskRename', gantt_handler_onTaskChangeAttributes);
        ganttChartControl.attachEvent('onTaskChangeCompletion', gantt_handler_onTaskChangeAttributes);
        ganttChartControl.attachEvent('onTaskDelete', gantt_handler_onTaskDelete);
        ganttChartControl.attachEvent('onTaskChangeDuration', gantt_handler_onTaskChangeBounds);
        ganttChartControl.attachEvent('onTaskChangeEST', gantt_handler_onTaskChangeBounds);
        ganttChartControl.attachEvent('onTaskInsert', gantt_handler_onTaskInsert);

        // Build control on the page\n
        ganttChartControl.create(htmlDiv);\n
        ";

        return $str;
    }

    protected function format_date($timestamp) {
        $y = date("Y", $timestamp);
        $m = date("m", $timestamp);
        $d = date("j", $timestamp);
        $m--;

        return "$y, $m, $d";
    }

    public function gantt($ganttid) {
        $str = "<script type=\"text/javascript\">";
        $str .= "createChartControl('$ganttid');\n";
        $str .= "</script>\n";

        return $str;
    }
}