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
 *
 * This screen show tasks plan ordered by decreasing priority.
 */
defined('MOODLE_INTERNAL') || die();

echo $pagebuffer;

$timeunits = array(get_string('unset', 'techproject'),
                   get_string('hours', 'techproject'),
                   get_string('halfdays', 'techproject'),
                   get_string('days', 'techproject'));

// Memorizes current page - typical session switch.

$viewmode = optional_param('viewMode', '', PARAM_ALPHA);

if (!empty($viewmode)) {
    $_SESSION['viewmode'] = $viewmode;
} else if (empty($_SESSION['viewmode'])) {
    $_SESSION['viewmode'] = 'alltasks';
}
$viewmode = $_SESSION['viewmode'];

/*
 * priority is deduced from task_to_spec mapping. Priority of a trask is the priority of its
 * highest prioritary spec
 */
echo "<center>";
$tabs = array();
$taburl = new moodle_url('/mod/techproject/view.php', array('id' => $cm->id, 'viewMode' => 'alltasks'));
$tabs[0][] = new tabobject('alltasks', $taburl, get_string('viewalltasks', 'techproject'));
$taburl = new moodle_url('/mod/techproject/view.php', array('id' => $cm->id, 'viewMode' => 'onlyleaves'));
$tabs[0][] = new tabobject('onlyleaves', $taburl, get_string('viewonlyleaves', 'techproject'));
$taburl = new moodle_url('/mod/techproject/view.php', array('id' => $cm->id, 'viewMode' => 'onlymasters'));
$tabs[0][] = new tabobject('onlymasters', $taburl, get_string('viewonlymasters', 'techproject'));
print_tabs($tabs, $_SESSION['viewmode'], null, null, false);

// Get assigned tasks.
$sql = "
   SELECT
      t.*,
      MAX(s.priority) as taskpriority
   FROM
      {techproject_task} as t,
      {techproject_task_to_spec} as tts,
      {techproject_specification} as s
   WHERE
      t.id = tts.taskid AND
      s.id = tts.specid AND
      t.projectid = {$project->id} AND
      t.groupid = {$currentgroupid}
   GROUP BY
      t.id
   ORDER BY
      taskpriority DESC
   LIMIT 0, 10
";
?>
<script type="text/javascript">
function sendgroupdata(){
    document.groupopform.submit();
}
</script>
<form name="groupopform" action="view.php" method="post" style="text-align : left">
<input type="hidden" name="id" value="<?php p($cm->id) ?>" />
<input type="hidden" name="work" value="groupcmd" />
<input type="hidden" name="view" value="tasks" />
<?php
if ($tasks = $DB->get_records_sql($sql)) {
    foreach ($tasks as $atask) {
        $params = array('code' => $atask->taskpriority, 'domain' => 'priority', 'projectid' => $project->id);
        if ($atask->priority = $DB->get_record('techproject_qualifier', $params)) {
            $params = array('code' => $atask->taskpriority, 'domain' => 'priority', 'projectid' => 0);
            $atask->priority = $DB->get_record('techproject_qualifier', $params);
        }
        if (($viewmode == 'onlyleaves' || $viewmode == 'onlyslaves') &&
                techproject_count_subs('techproject_task', $atask->id) != 0) {
            continue;
        }
        if ($viewmode == 'onlymasters' &&
                $DB->count_records('techproject_task_dependency', array('slave' => $atask->id)) != 0) {
            continue;
        }
        techproject_print_single_task($atask, $project, $currentgroupid, $cm->id, count($tasks), 'HEAD',
                                      'SHORT_WITH_ASSIGNEE_ORDERED');
    }
}
// Get unassigned tasks.
$sql = "
   SELECT
      t.*,
      COUNT(tts.specid) as specs
   FROM
      {techproject_task} as t
   LEFT JOIN
      {techproject_task_to_spec} as tts
   ON
      t.id = tts.taskid
   WHERE
      tts.specid IS NULL AND
      t.projectid = ? AND
      t.groupid = ?
   GROUP BY
      t.id
   HAVING 
      specs = 0
";

if ($unassignedtasks = $DB->get_records_sql($sql, array($project->id, $currentgroupid))) {
    echo $OUTPUT->heading(get_string('unspecifiedtasks','techproject') . ' ' . $OUTPUT->help_icon('unspecifiedtasks', 'techproject', false));
    foreach ($unassignedtasks as $atask) {
        if (($viewmode == 'onlyleaves' || $viewmode == 'onlyslaves') &&
                techproject_count_subs('techproject_task', $atask->id) != 0) {
            continue;
        }
        if (($viewmode == 'onlymasters') &&
                $DB->count_records('techproject_task_dependency', array('slave' => $atask->id)) != 0) {
            continue;
        }
        techproject_print_single_task($atask, $project, $currentgroupid, $cm->id, count($unassignedtasks), 'SHORT', 'dithered',
                                      'SHORT_WITHOUT_TYPE_NOEDIT');
    }
}
if (($tasks || $unassignedtasks) && $USER->editmode == 'on' && has_capability('mod/techproject:changetasks', $context)) {
    echo '<br/>';
    techproject_print_group_commands(array('markasdone', 'fullfill'));
}
?>
</form>
</center>