<?php // $Id: gantt.php,v 1.1.1.1 2012-08-01 10:16:10 vf Exp $

    /**
    * Project : Technical Project Manager (IEEE like)
    *
    * Gant chart for the project.
    *
    * @package mod-techproject
    * @category mod
    * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
    * @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
    * @date 2008/03/03
    * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
    */

    require_once('ganttlib.php');
    
    // we cannot use require_js because of the parameters

	echo $pagebuffer;

    echo "<script type=\"text/javascript\" src=\"{$CFG->wwwroot}/mod/techproject/js/ganttevents.php?id={$course->id}&projectid={$project->id}\"></script>";
    
    echo "<link type=\"text/css\" rel=\"stylesheet\" href=\"{$CFG->wwwroot}/mod/techproject/js/dhtmlxGantt/codebase/dhtmlxgantt.css\">";
        
    echo $OUTPUT->heading(get_string('ganttchart', 'techproject'));

    $sortedTasks = array();
    $unscheduledTasks = array();
    $assignees = array();
    $leadtasks = array();
    $gantt = false;
    $parent = null;

	// delayed printing, allows evaluate if there is somethin inside
	gantt_print_all_tasks($parent, $project, $currentGroupId, $unscheduledTasks, $assignees, $leadtasks, $str);

    if (!empty($leadtasks)){
    	$gantt = true;
    	gantt_print_init('GantDiv');
    	echo $str;
    	gantt_print_control(array_keys($leadtasks));
    	gantt_print_end();
    } else {
       	echo '<center>';
      	echo $OUTPU->box(get_string('notasks', 'techproject'));
       	echo '</center>';
       	return;
    }
        
    ?>
    <br/>
    <table width="100%">
    <?php
    if ($unscheduledTasks){
        echo $OUTPUT->heading_block(get_string('unscheduledtasks','techproject'));
        echo $OUTPUT->box_start('center', $labelWidth + $timeXWidth);
        foreach($unscheduledTasks as $aTask){
            techproject_print_single_task($aTask, $project, $currentGroupId, $cm->id, count($unscheduledTasks), false, $style='NOEDIT');
        }
        echo $OUTPUT->box_end();
    }
    ?>
    </table>
    </center>

<?php
if ($gantt){
	gantt_render('GantDiv');
}