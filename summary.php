<?php

    /**
    * Project : Technical Project Manager (IEEE like)
    *
    * this screen submarizes activity in the project for the current group
    * 
    * @package mod-techproject
    * @category mod
    * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
    * @date 2008/03/03
    * @version phase1
    * @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
    * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
    */
/// counting leaf requests
    if (has_capability('mod/techproject:viewpreproductionentities', $context)){
        $requirements = $DB->get_records_select('techproject_requirement', "projectid = ? AND groupid = ? AND fatherid = 0 ", array($project->id, $currentgroupid) , '', 'id,abstract');
        $leafRequs = array();
        $leafRequList = '';
        if ($requirements){
            foreach($requirements as $aRequirement){
                $leafRequs = array_merge($leafRequs, techproject_count_leaves('techproject_requirement', $aRequirement->id, true)) ;
            }
            $leafRequList = implode("','", $leafRequs);
        }
        $countrequ = count($leafRequs);
/// counting specifications

        $specifications = $DB->get_records_select('techproject_specification', "projectid = $project->id AND groupid = $currentgroupid AND fatherid = 0 ", array($project->id, $currentgroupid), '', 'id,abstract');
        $leafSpecs = array();
        $leafSpecList = '';
        if ($specifications){
            foreach($specifications as $aSpecification){
                $leafSpecs = array_merge($leafSpecs, techproject_count_leaves('techproject_specification', $aSpecification->id, true)) ;
            }
            $leafSpecList = implode("','", $leafSpecs);
        }
        $countspec = count($leafSpecs);
    }
/// counting tasks

        $tasks = $DB->get_records_select('techproject_task', "projectid = $project->id AND groupid = $currentgroupid AND fatherid = 0 ", array($project->id, $currentgroupid), '', 'id,abstract');
        $leafTasks = array();
        $leafTaskList = '';
        if ($tasks){
            foreach($tasks as $aTask){
                $leafTasks = array_merge($leafTasks, techproject_count_leaves('techproject_task', $aTask->id, true)) ;
            }
            $leafTaskList = implode("','", $leafTasks);
        }
        $counttask = count($leafTasks);
/// counting deliverables

        $deliverables = $DB->get_records_select('techproject_deliverable', "projectid = $project->id AND groupid = $currentgroupid AND fatherid = 0 ", array($project->id, $currentgroupid), '', 'id,abstract');
        $leafDelivs = array();
        $leafDelivList = '';
        if ($deliverables){
            foreach($deliverables as $aDeliverable){
                $leafDelivs = array_merge($leafDelivs, techproject_count_leaves('techproject_deliverable', $aDeliverable->id, true)) ;
            }
            $leafDelivList = implode("','", $leafDelivs);
        }
        $countdeliv = count($leafDelivs);
/// counting requirements uncovered

    if (has_capability('mod/techproject:viewpreproductionentities', $context)){
        if (isset($leafRequList)){
            $query = "
               SELECT
                 COUNT(IF(str.specid IS NULL, 1, NULL)) as uncovered
               FROM
                 {techproject_requirement} as r
               LEFT JOIN
                 {techproject_spec_to_req} as str
               ON 
                 r.id = str.reqid
               WHERE
                 r.projectid = {$project->id} AND
                 r.groupid = {$currentgroupid} AND
                 r.id IN ('{$leafRequList}')
            ";
            $res = $DB->get_records_sql($query);
            $res = array_values($res);
            $res = $res[0];
            $uncoveredrequ = $res->uncovered;
            $coveredrequ = $countrequ - $res->uncovered;
        }
/// counting specs uncovered

        if(isset($leafSpecList)){
            $query = "
               SELECT
                 COUNT(IF(stt.taskid IS NULL,1,NULL)) as uncovered
               FROM
                 {techproject_specification} as s
               LEFT JOIN
                 {techproject_task_to_spec} as stt
               ON 
                 s.id = stt.specid
               WHERE
                 s.projectid = {$project->id} AND
                 s.groupid = {$currentgroupid} AND
                 s.id IN ('{$leafSpecList}')
            ";
            $res = $DB->get_records_sql($query);
            $res = array_values($res);
            $res = $res[0];
            $uncoveredspec = $res->uncovered;
            $coveredspec = $countspec - $res->uncovered;
        }
    }
    $projectheading = $DB->get_record('techproject_heading', array('projectid' => $project->id, 'groupid' => $currentgroupid));
    // if missing create one
    if (!$projectheading){
        $projectheading->id = 0;
        $projectheading->projectid = $project->id;
        $projectheading->groupid = $currentgroupid;
        $projectheading->title = '';
        $projectheading->abstract = '';
        $projectheading->rationale = '';
        $projectheading->environment = '';
        $projectheading->organisation = '';
        $projectheading->department = '';        
        $DB->insert_record('techproject_heading', $projectheading);
    }
/********************************************* Start producing summary ***************************/

	echo $pagebuffer;
    echo '<center>';
    echo $OUTPUT->box_start('center', '100%');
    ?>
    <table width="80%">
        <tr valign="top">
            <th align="left" width="60%">
                <?php 
                print_string('summaryforproject', 'techproject');
                echo $OUTPUT->help_icon('leaves', 'techproject', true); 
                ?>
             </th>
             <th align="left" width="40%">
                <?php echo $projectheading->title; ?>
             </th>
        </tr>
    <?php
    if(has_capability('mod/techproject:viewpreproductionentities', $context)){
        echo '<tr class="sectionrow">';
        echo '<td align="left">'.get_string('totalrequ', 'techproject').'</td>';
        echo '<td align="left">'.(0 + @$countrequ).'</td>';
        echo '</tr>';
        echo '<tr class="subsectionrow">';
        echo '<td align="left"><span class="level4">&nbsp;&nbsp;&nbsp;' . get_string('covered', 'techproject') . '</span></td>';
        echo '<td align="left">'.(0 + @$coveredrequ).'</td>';
        echo '</tr>';
        echo '<tr class="subsectionrow">';
        echo '<td align="left"><span class="level4">&nbsp;&nbsp;&nbsp;' . get_string('uncovered', 'techproject') . '</span></td>';
        echo '<td align="left">'.(0 + @$uncoveredrequ).'</td>';
        echo '</tr>';
        echo '<tr class="sectionrow">';
        echo '<td align="left">'.get_string('totalspec', 'techproject').'</td>';
        echo '<td align="left">'.(0 + @$countspec).'</td>';
        echo '</tr>';
        echo '<tr class="subsectionrow">';
        echo '<td align="left"><span class="level4">&nbsp;&nbsp;&nbsp;' . get_string('covered', 'techproject') . '</span></td>';
        echo '<td align="left">'.(0 + @$coveredspec).'</td>';
        echo '</tr>';
        echo '<tr class="subsectionrow">';
        echo '<td align="left"><span class="level4">&nbsp;&nbsp;&nbsp;' . get_string('uncovered', 'techproject') . '</span></td>';
        echo '<td align="left">'.(0 + @$uncoveredspec).'</td>';
        echo '</tr>';
    }
    echo '<tr class="sectionrow">';
    echo '<td align="left">' . get_string('totaltask', 'techproject') . '</td>';
    echo '<td align="left">'.(0 + @$counttask).'</td>';
    echo '</tr>';
    echo '<tr class="sectionrow">';
    echo '<td align="left">' . get_string('totaldeliv', 'techproject') . '</td>';
    echo '<td align="left">'.(0 + @$countdeliv).'</td>';
    echo '</tr>';
    ?>
    </table>
    <br/>
    <?php
    if ($project->teacherusescriteria){
    	$table = new html_table();
        $table->head = array(get_string('criteria', 'techproject'), '');
        $table->align = array('left', 'left');
        $table->size = array('40%', '60%');
        $table->wrap = array('','');
        $table->data = array();
        $freeCriteria = $DB->get_records_select('techproject_criterion', "projectid = ? AND isfree = 1", array($project->id));
        $criteria = $DB->get_records_select('techproject_criterion', "projectid = ? AND isfree = 0", array($project->id));
        $str = '';
        if ($criteria){
            foreach($criteria as $aCriteria){
                $str .= $aCriteria->label . ' (x' . $aCriteria->weight . ')<br/>';
            }
            $table->data[] = array(get_string('itemcriteriaset', 'techproject'), $str);
        }
        $str = '';
        if ($freeCriteria){
            foreach($freeCriteria as $aCriteria){
                $str .= $aCriteria->label . ' (x' . $aCriteria->weight . ')<br/>';
            }
            $table->data[] = array(get_string('freecriteriaset', 'techproject'), $str);
        }
        echo html_writer::table($table);
        unset($table);
    }
    $table = new html_table();
    $table->head = array(get_string('assessments', 'techproject'), '');
    $table->align = array('left', 'left');
    $table->size = array('40%', '60%');
    $table->wrap = array('','');
    if (time() < $project->assessmentstart){
        $table->data[] = array(get_string('notavailable'), '');
    }
    else{
        // getting all grades
        $milestones = $DB->get_records_select('techproject_milestone', "projectid = ? AND groupid = ? ", array($project->id, $currentgroupid));
        $grades = $DB->get_records_select('techproject_assessment', "projectid = ? AND groupid = ? GROUP BY itemid, criterion, itemclass", array($project->id, $currentgroupid));
        $freecriteria =  $DB->get_records_select('techproject_criterion', "projectid = ? AND isfree = 1", array($project->id));
        $criteria = $DB->get_records_select('techproject_criterion', "projectid = ? AND isfree = 0", array($project->id));
        $gradesByClass = array();
        // if there are any grades yet, compile them by categories
        if($grades){
            $grades = array_values($grades);
            for($i = 0 ; $i < count($grades) ; $i++ ){
                $gradesByClass[$grades[$i]->itemclass][$grades[$i]->itemid][$grades[$i]->criterion] = $grades[$i]->grade;
            }
            if($milestones && (!$project->teacherusescriteria || $criteria)){
                foreach($milestones as $aMilestone){
                    $table->data[] = array(get_string('evaluatingfor','techproject'), $aMilestone->abstract);
                    if ($project->autogradingenabled){
                        $autograde = @$gradesByClass['auto_milestone'][$aMilestone->id][0];
                        if (empty($autograde))
                            $table->data[] = array(get_string('autograde','techproject'), get_string('notevaluated','techproject'));
                        else
                            $table->data[] = array(get_string('autograde','techproject'), $autograde);
                    }
                    if (!$project->teacherusescriteria){
                        $teachergrade = @$gradesByClass['milestone'][$aMilestone->id][0];
                        if (empty($teachergrade))
                            $table->data[] = array(get_string('teachergrade','techproject'), get_string('notevaluated','techproject'));
                        else
                            $table->data[] = array(get_string('teachergrade','techproject'), $teachergrade);
                    }
                    else{
                        foreach($criteria as $aCriterion){
                            $criteriongrade = @$gradesByClass['milestone'][$aMilestone->id][$aCriterion->id];
                            if (empty($criteriongrade))
                                $table->data[] = array($aCriterion->label, get_string('notevaluated','techproject'));
                            else
                                $table->data[] = array($aCriterion->label, $criteriongrade . ' (x' . $aCriterion->weight . ')');
                        }
                    }
                }
            }
        }
        // additional free criteria for grading
        if ($freecriteria){
            foreach($freecriteria as $aFreeCriterion){
                $freegrade = @$gradesByClass['free'][0][$aFreeCriterion->id];
                if (empty($freegrade))
                    $table->data[] = array($aFreeCriterion->label, get_string('notevaluated','techproject'));
                else
                    $table->data[] = array($aFreeCriterion->label, $freegrade . ' (x'.$aFreeCriterion->weight.')');
            }
        }
    }
    echo html_writer::table($table);
    unset($table);
    echo $OUTPUT->box_end();
    echo '</center>';

?>