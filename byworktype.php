<?php

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

	if (!defined('MOODLE_INTERNAL'))  die('You cannot use this script that way');

	echo $pagebuffer;

    $TIMEUNITS = array(get_string('unset','techproject'),get_string('hours','techproject'),get_string('halfdays','techproject'),get_string('days','techproject'));
    /** useless ?
    if (!groups_get_activity_groupmode($cm, $project->course)){
        $groupusers = get_course_users($project->course);
    } else {
        $groupusers = get_group_users($currentgroupid);
    }*/
    // get tasks by worktype
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
    if ($tasks = $DB->get_records_sql($query)){
    ?>
    <script type="text/javascript">
    function sendgroupdata(){
        document.groupopform.submit();
    }
    </script>
    <form name="groupopform" action="view.php" method="post">
    <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
    <input type="hidden" name="work" value="groupcmd" />
    <input type="hidden" name="view" value="tasks" />
    <?php    
        foreach($tasks as $aTask){
            $sortedtasks[$aTask->worktype][] = $aTask;
        }
        foreach(array_keys($sortedtasks) as $aWorktype){
        	$hidesub = "<a href=\"javascript:toggle('{$aWorktype}','sub{$aWorktype}');\"><img name=\"img{$aWorktype}\" src=\"{$CFG->wwwroot}/mod/techproject/pix/p/switch_minus.gif\" alt=\"collapse\" style=\"background-color : #E0E0E0\" /></a>";
            $theWorktype = techproject_get_option_by_key('worktype', $project->id, $aWorktype);
            if ($aWorktype == ''){
                 $worktypeicon = '';
                 $theWorktype->label = format_text(get_string('untypedtasks', 'techproject'), FORMAT_HTML)."</span>";
            } else {
                 $worktypeicon = "<img src=\"".$OUTPUT->pix_url('/p/'.strtolower($theWorktype->code), 'techproject')."\" title=\"{$theWorktype->description}\" style=\"background-color : #F0F0F0\" />";
            }
            echo $OUTPUT->box($hidesub.' '.$worktypeicon.' <span class="worktypesheadingcontent">'.$theWorktype->label.'</span>', 'worktypesbox');
            echo "<div id=\"sub{$aWorktype}\">";
            foreach($sortedtasks[$aWorktype] as $aTask){
                techproject_print_single_task($aTask, $project, $currentgroupid, $cm->id, count($sortedtasks[$aWorktype]), 'SHORT_WITHOUT_TYPE');
            }
            echo '</div>';
        }
        echo '<p>';
    	techproject_print_group_commands();
        echo '</p>';
    ?>
    </form>
<?php
    } else {
       echo $OUTPUT->box(get_string('notasks', 'techproject'), 'center', '70%');
    }
?>