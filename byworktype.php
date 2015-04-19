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
* This screen show tasks plan grouped by worktype.
*
* @package mod-techproject
* @category mod
* @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
* @date 2008/03/03
* @version phase1
* @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
*/

if (!defined('MOODLE_INTERNAL')) {
    die('You cannot use this script that way');
}

echo $pagebuffer;

$TIMEUNITS = array(get_string('unset','techproject'),get_string('hours','techproject'),get_string('halfdays','techproject'),get_string('days','techproject'));
/** useless ?
if (!groups_get_activity_groupmode($cm, $project->course)){
    $groupusers = get_course_users($project->course);
} else {
    $groupusers = get_group_users($currentgroupid);
}*/

// Get tasks by worktype.

$query = "
   SELECT
      t.*
   FROM
      {techproject_task} as t
   LEFT JOIN
      {techproject_qualifier} as qu
   ON 
      qu.code = t.worktype AND
      qu.domain = 'worktype'
   WHERE
      t.projectid = {$project->id} AND
      t.groupid = {$currentgroupid}
   ORDER BY
      qu.id ASC
";

if ($tasks = $DB->get_records_sql($query)) {

echo '
<script type="text/javascript">
function sendgroupdata(){
    document.groupopform.submit();
}
</script>
';

echo '<form name="groupopform" action="view.php" method="post">';
echo '<input type="hidden" name="id" value="'.$cm->id.'" />';
echo '<input type="hidden" name="work" value="groupcmd" />';
echo '<input type="hidden" name="view" value="tasks" />';

foreach ($tasks as $aTask) {
    $sortedtasks[$aTask->worktype][] = $aTask;
}

foreach (array_keys($sortedtasks) as $aWorktype) {
    $hidesub = "<a href=\"javascript:toggle('{$aWorktype}','sub{$aWorktype}');\"><img name=\"img{$aWorktype}\" src=\"{$CFG->wwwroot}/mod/techproject/pix/p/switch_minus.gif\" alt=\"collapse\" style=\"background-color : #E0E0E0\" /></a>";
    $theWorktype = techproject_get_option_by_key('worktype', $project->id, $aWorktype);
    if ($aWorktype == '') {
         $worktypeicon = '';
         $theWorktype->label = format_text(get_string('untypedtasks', 'techproject'), FORMAT_HTML)."</span>";
    } else {
         $worktypeicon = "<img src=\"".$OUTPUT->pix_url('/p/'.strtolower($theWorktype->code), 'techproject')."\" title=\"{$theWorktype->description}\" style=\"background-color : #F0F0F0\" />";
    }
    echo $OUTPUT->box($hidesub.' '.$worktypeicon.' <span class="worktypesheadingcontent">'.$theWorktype->label.'</span>', 'worktypesbox');
    echo "<div id=\"sub{$aWorktype}\">";
    foreach ($sortedtasks[$aWorktype] as $aTask) {
        techproject_print_single_task($aTask, $project, $currentgroupid, $cm->id, count($sortedtasks[$aWorktype]), 'SHORT_WITHOUT_TYPE');
    }
    echo '</div>';
}
echo '<p>';
techproject_print_group_commands();
echo '</p>';

echo '</form>';

} else {
   echo $OUTPUT->box(get_string('notasks', 'techproject'), 'center', '70%');
}
