<?php

    /**
    * Project : Technical Project Manager (IEEE like)
    *
    * This screen show tasks plan ordered by decreasing priority.
    *
    * @package mod-techproject
    * @category mod
    * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
    * @date 2008/03/03
    * @version phase1
    * @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
    * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
    */

	if (!defined('MOODLE_INTERNAL')) die('You cannot use this script that way');

	echo $pagebuffer;

    $TIMEUNITS = array(get_string('unset','techproject'),get_string('hours','techproject'),get_string('halfdays','techproject'),get_string('days','techproject'));
    /** useless
    if (!groups_get_activity_groupmode($cm, $project->course)){
        $groupusers = get_users_by_capability($project->course);
    } else {
        $groupusers = get_group_users($currentgroupid);
    }
    */
    //memorizes current page - typical session switch
    $viewmode = optional_param('viewMode', '', PARAM_ALPHA);
    if (!empty($viewmode)) {
    	$_SESSION['viewmode'] = $viewmode;
    } elseif (empty($_SESSION['viewmode'])) {
    	$_SESSION['viewmode'] = 'alltasks';
    }
    $viewmode = $_SESSION['viewmode'];
    /*
    * priority is deduced from task_to_spec mapping. Priority of a trask is the priority of its
    * highest prioritary spec
    */
    echo "<center>";
    $tabs = array();
    $tabs[0][] = new tabobject('alltasks', "view.php?id={$cm->id}&amp;viewMode=alltasks", get_string('viewalltasks', 'techproject'));
    $tabs[0][] = new tabobject('onlyleaves', "view.php?id={$cm->id}&amp;viewMode=onlyleaves", get_string('viewonlyleaves', 'techproject'));
    $tabs[0][] = new tabobject('onlymasters', "view.php?id={$cm->id}&amp;viewMode=onlymasters", get_string('viewonlymasters', 'techproject'));
    print_tabs($tabs, $_SESSION['viewmode'], NULL, NULL, false);
    // get assigned tasks
    $query = "
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
    if ($tasks = $DB->get_records_sql($query)){
        foreach($tasks as $aTask){
            if ($aTask->priority = $DB->get_record('techproject_qualifier', array('code' => $aTask->taskpriority, 'domain' => 'priority', 'projectid' => $project->id))){
                $aTask->priority = $DB->get_record('techproject_qualifier', array('code' => $aTask->taskpriority, 'domain' => 'priority', 'projectid' => 0));
            }
            if (($viewmode == 'onlyleaves' || $viewmode == 'onlyslaves') && techproject_count_subs('techproject_task', $aTask->id) != 0) continue;
            if ($viewmode == 'onlymasters' && $DB->count_records('techproject_task_dependency', array('slave' => $aTask->id)) != 0) continue;
            techproject_print_single_task($aTask, $project, $currentgroupid, $cm->id, count($tasks), 'HEAD', 'SHORT_WITH_ASSIGNEE_ORDERED');
        }
    }
    // get unassigned tasks
    $query = "
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
          t.projectid = {$project->id} AND
          t.groupid = {$currentgroupid}
       GROUP BY
          t.id
       HAVING 
          specs = 0
    ";
    // echo $query;
    if ($unassignedtasks = $DB->get_records_sql($query)){
        echo $OUTPUT->heading(get_string('unspecifiedtasks','techproject') . ' ' . $OUTPUT->help_icon('unspecifiedtasks', 'techproject', false));
        foreach($unassignedtasks as $aTask){
            if (($viewmode == 'onlyleaves' || $viewmode == 'onlyslaves') && techproject_count_subs('techproject_task', $aTask->id) != 0) continue;
            if ($viewmode == 'onlymasters' && $DB->count_records('techproject_task_dependency', array('slave' => $aTask->id)) != 0) continue;
            techproject_print_single_task($aTask, $project, $currentgroupid, $cm->id, count($unassignedtasks), 'SHORT', 'dithered', 'SHORT_WITHOUT_TYPE_NOEDIT');
        }
    }
    if (($tasks || $unassignedtasks) && $USER->editmode == 'on' && has_capability('mod/techproject:changetasks', $context)){
        echo '<br/>';
    	techproject_print_group_commands(array('markasdone', 'fullfill'));
    }
?>
</form>
</center>