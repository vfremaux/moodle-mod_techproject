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
 *
 * This screen show tasks plan grouped by worktype.
 */
defined('MOODLE_INTERNAL') || die();

echo $pagebuffer;

$timeunitsarr = array(get_string('unset', 'techproject'),
                   get_string('hours', 'techproject'),
                   get_string('halfdays', 'techproject'),
                   get_string('days', 'techproject'));

// Get tasks by worktype.

$sql = "
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

if ($tasks = $DB->get_records_sql($sql)) {

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

    foreach ($tasks as $atask) {
        $sortedtasks[$atask->worktype][] = $atask;
    }

    foreach (array_keys($sortedtasks) as $aworktype) {
        $pix = $OUTPUT->pix_icon('/p/switch_minus', '', 'techproject', array('name' => 'img'.$aworktype));
        $jshandler = 'javascript:toggle(\''.$aworktype.'\',\'sub'.$aworktype.'\');';
        $hidesub = '<a href="'.$jshandler.'">'.$pix.'</a>';
        $theworktype = techproject_get_option_by_key('worktype', $project->id, $aworktype);
        if ($aworktype == '') {
            $worktypeicon = '';
            $theworktype->label = format_text(get_string('untypedtasks', 'techproject'), FORMAT_HTML)."</span>";
        } else {
            $attrs = array('class' => 'worktypeicon');
            $worktypeicon = $OUTPUT->pix_icon('/p/'.strtolower($theworktype->code), $theworktype->description, 'techproject', $attrs);
        }
        echo $OUTPUT->box($hidesub.' '.$worktypeicon.' <span class="worktypesheadingcontent">'.$theworktype->label.'</span>', 'worktypesbox');
        echo '<div id="sub'.$aworktype.'">';
        foreach ($sortedtasks[$aworktype] as $atask) {
            techproject_print_single_task($atask, $project, $currentgroupid, $cm->id, count($sortedtasks[$aworktype]), 'SHORT_WITHOUT_TYPE');
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
