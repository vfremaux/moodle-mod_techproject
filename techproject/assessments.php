<?php // $Id: assessments.php,v 1.1 2011-06-20 16:19:57 vf Exp $

    /**
    * Project : Technical Project Manager (IEEE like)
    *
    * This is a screen for assessing students
    *
    * @package mod-techproject
    * @category mod
    * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
    * @date 2008/03/03
    * @version phase1
    * @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
    * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
    */

    /**
    * A small utility function for making scale menus
    *
    * @param object $project
    * @param int $id
    * @param string $selected
    * @param boolean $return if return is true, returns the HTML flow as a string, prints it otherwise
    */
    function make_grading_menu($project, $id, $selected = '', $return = false){
        if ($project->grade > 0){
            for($i = 0 ; $i <= $project->grade ; $i++)
                $scalegrades[$i] = $i; 
        } else {
            $scaleid = - ($project->grade);
            if ($scale = get_record('scale', 'id', $scaleid)) {
                $scalegrades = make_menu_from_list($scale->scale);
            }
        }
        return choose_from_menu($scalegrades, $id, $selected, 'choose', '', '', $return);
    }


    if (!has_capability('mod/techproject:gradeproject', $context)){
        print_error(get_string('notateacher','techproject'));
        return;
    }
    
    print_heading(get_string('assessment'), 'center');
    
    // checks if assessments can occur
    if (!groups_get_activity_groupmode($cm, $project->course)){
        // $groupStudents = get_course_students($project->course);
        $groupStudents = get_users_by_capability($context, 'mod/techproject:canbeevaluated', 'id,firstname,lastname,email,picture', 'lastname');
    } else {
        $groupmembers = groups_get_members($currentGroupId);
        foreach($groupmembers as $amember){
            if (!has_capability('mod/techproject:canbeevaluated', $context, $amember->id)) continue;
            $groupStudents[] = clone($amember);
        }
    }
    if (!isset($groupStudents) || count($groupStudents) == 0 || empty($groupStudents)){
        print_simple_box(get_string('noonetoassess', 'techproject'), 'center', '70%');
        return;
    } else {
       $studentListArray = array();
       foreach($groupStudents as $aStudent){
          $studentListArray[] = $aStudent->lastname . ' ' . $aStudent->firstname . ' ' . print_user_picture($aStudent->id, $course->id, !empty($aStudent->picture), 0, true, true) ;
       }
       print_simple_box_start('center', '80%');
       echo '<center><i>'.get_string('evaluatingforusers', 'techproject') .' : </i> '. implode(',', $studentListArray) . '</center><br/>';
    }
    
    if ($work == 'regrade'){
        $autograde = techproject_autograde($project, $currentGroupId);
        if ($project->grade > 0){
            unset($assessment);
            $assessment->id = 0;
            $assessment->projectid = $project->id;
            $assessment->groupid = $currentGroupId;
            $assessment->userid = $aStudent->id;
            $assessment->itemid = 0;
            $assessment->itemclass = 'auto';
            $assessment->criterion = 0;
            $assessment->grade = round($autograde * $project->grade);
            if ($oldrecord = get_record_select('techproject_assessment', "projectid = {$project->id} AND userid = {$aStudent->id} AND itemid = 0 AND itemclass='auto'")){
                $assessment->id = $oldrecord->id;
                update_record('techproject_assessment', $assessment);
                add_to_log($course->id, 'techproject', 'grade', "view.php?id=$cm->id&view=view_summary", $project->id, $cm->id, $aStudent->id);
            } else {
                insert_record('techproject_assessment', $assessment);
            }
        }
    }
    print_simple_box_end();
    
    // do what needed
    if ($work == 'dosave'){
        // getting candidate keys for grading
        $parmKeys = array_keys($_POST);
    
        function filterTeachergradeKeys($var){
            return preg_match("/teachergrade_/", $var);
        }
    
        function filterFreegradeKeys($var){
            return preg_match("/free_/", $var);
        }
    
        $teacherGrades = array_filter($parmKeys, 'filterTeachergradeKeys');
        $freeGrades = array_filter($parmKeys, 'filterFreegradeKeys');
            
        foreach($groupStudents as $aStudent){
            // dispatch autograde for all students 
            if ($project->autogradingenabled){
                unset($assessment);
                $assessment->id = 0;
                $assessment->projectid = $project->id;
                $assessment->groupid = $currentGroupId;
                $assessment->userid = $aStudent->id;
                $assessment->itemid = 0;
                $assessment->itemclass = 'auto';
                $assessment->criterion = 0;
                $grade = optional_param('autograde', '', PARAM_INT);
                if (!empty($grade)) $assessment->grade = $grade;
                if ($oldrecord = get_record_select('techproject_assessment', "projectid = {$project->id} AND userid = {$aStudent->id} AND itemid = '{$assessment->itemid}' AND itemclass='{$assessment->itemclass}'")){
                    $assessment->id = $oldrecord->id;
                    update_record('techproject_assessment', $assessment);
                    add_to_log($course->id, 'techproject', 'grade', "view.php?id={$cm->id}&view=view_summary&group={$currentGroupId}", $project->id, $cm->id, $aStudent->id);
                } else {
                    insert_record('techproject_assessment', $assessment);
                }
            }
    
            // dispatch teachergrades for all students 
            foreach($teacherGrades as $aGradeKey){
                preg_match('/teachergrade_([^_]*?)_([^_]*?)(?:_(.*?))?$/', $aGradeKey, $matches);
                unset($assessment);
                $assessment->id = 0;
                $assessment->projectid = $project->id;
                $assessment->groupid = $currentGroupId;
                $assessment->userid = $aStudent->id;
                $assessment->itemid = $matches[2];
                $assessment->itemclass = $matches[1];
                $criterionClause = '';
                if (isset($matches[3])){
                     $assessment->criterion = $matches[3];
                     $criterionClause = "AND criterion='{$assessment->criterion}'";
                }
                $grade = optional_param($aGradeKey, '', PARAM_INT);
                if (!empty($grade)) $assessment->grade = $grade;
                if ($oldrecord = get_record_select('techproject_assessment', "projectid = {$project->id} AND userid = {$aStudent->id} AND itemid = {$assessment->itemid} AND itemclass='{$assessment->itemclass}' {$criterionClause}")){
                    $assessment->id = $oldrecord->id;
                    update_record('techproject_assessment', $assessment);
                } else {
                    insert_record('techproject_assessment', $assessment);
                }
            }
    
            // dispatch freegrades
            foreach($freeGrades as $aGradeKey){
                preg_match('/free_([^_]*?)$/', $aGradeKey, $matches);
                unset($assessment);
                $assessment->id = 0;
                $assessment->projectid = $project->id;
                $assessment->groupid = $currentGroupId;
                $assessment->userid = $aStudent->id;
                $assessment->itemclass = 'free';
                $assessment->itemid = 0;
                $assessment->criterion = $matches[1];
                $grade = optional_param($aGradeKey, '', PARAM_INT);
                if (!empty($grade)) $assessment->grade = $grade;
                if ($oldrecord = get_record_select('techproject_assessment', "projectid = {$project->id} AND userid = {$aStudent->id} AND itemclass = 'free' AND itemid = 0 AND criterion = {$assessment->criterion}")){
                    $assessment->id = $oldrecord->id;
                    update_record('techproject_assessment', $assessment);
                } else {
                    insert_record('techproject_assessment', $assessment);
                }
            }
            add_to_log($course->id, 'techproject', 'grade', "view.php?id=$cm->id&view=view_summary&group={$currentGroupId}", $project->id, $cm->id, $aStudent->id);
        }
    }
    elseif ($work == 'doerase'){
        foreach($groupStudents as $aStudent){
            delete_records('techproject_assessment', 'projectid', $project->id, 'userid', $aStudent->id);
            add_to_log($course->id, 'techproject', 'grade', "view.php?id=$cm->id&view=view_summary&group={$currentGroupId}", 'erase', $cm->id, $aStudent->id);
            add_to_log($course->id, 'techproject', 'grade', "view.php?id=$cm->id&view=view_summary&group={$currentGroupId}", 'erase', $cm->id);
        }
    }
    if ($project->teacherusescriteria){
        $freecriteria =  get_records_select('techproject_criterion', "projectid = {$project->id} AND isfree = 1");
        $criteria = get_records_select('techproject_criterion', "projectid = {$project->id} AND isfree = 0");
        if (!$criteria && !$freecriteria){
            print_simple_box(format_text(get_string('cannotevaluatenocriteria','techproject'), FORMAT_HTML), 'center');
            return;
        }
    }
    $canGrade = false;
    
    ?>
    <center>
    <script type="text/javascript">
    //<![CDATA[
    function senddata(cmd){
        document.forms['assessform'].work.value="do" + cmd;
        document.forms['assessform'].submit();
    }
    
    function cancel(){
        document.forms['assessform'].submit();
    }
    //]]>
    </script>
    <form name="assessform" method="post" action="view.php">
    <input type="hidden" name="id" value="<?php p($cm->id) ?>"/>
    <input type="hidden" name="work" value=""/>
    <table width="80%">
    <?php
        $milestones = get_records_select('techproject_milestone', "projectid = {$project->id} AND groupid = {$currentGroupId}");
        $grades = get_records_select('techproject_assessment', "projectid = {$project->id} AND groupid = {$currentGroupId} GROUP BY itemid,criterion,itemclass");
        $gradesByClass = array();
        
        // if there are any grades yet, compile them by categories
        if($grades){
            $grades = array_values($grades);
            for($i = 0 ; $i < count($grades) ; $i++ ){
                $gradesByClass[$grades[$i]->itemclass][$grades[$i]->itemid][$grades[$i]->criterion] = $grades[$i]->grade;
            }
        }
        
        if($milestones && (!$project->teacherusescriteria || $criteria)){
            $canGrade = true;
            echo "<tr><td colspan=\"2\" align=\"center\">";
            print_heading_block(get_string('itemevaluators', 'techproject'));
            echo "</td></tr>";
            foreach($milestones as $aMilestone){
                echo "<tr valign=\"top\"><td align=\"left\"><b>";
                echo get_string('evaluatingfor','techproject')." M.{$aMilestone->ordering} {$aMilestone->abstract}</b>";
                echo "</td></tr><tr><td>";
                if (!$project->teacherusescriteria){
                    $teachergrade = @$gradesByClass['milestone'][$aMilestone->id][0];
                    echo get_string('teachergrade','techproject').' ';
                    make_grading_menu($project, "teachergrade_milestone_{$aMilestone->id}", $teachergrade);
                } else {
                    foreach($criteria as $aCriterion){
                        $criteriongrade = @$gradesByClass['milestone'][$aMilestone->id][$aCriterion->id];
                        echo $aCriterion->label.' : ';
                        make_grading_menu($project, "teachergrade_milestone_{$aMilestone->id}_{$aCriterion->id}", $criteriongrade);
                        echo ' * ' . $aCriterion->weight . '<br/>';
                    }
                }
                echo "</td></tr>";
            }
        }
        
        // additional free criteria for grading (including autograde)
        if ($project->autogradingenabled || $project->teacherusescriteria){
            echo "<tr><td colspan=\"2\">";
            print_heading_block(get_string('globalevaluators', 'techproject'));
            echo "</td></tr>";
            $canGrade = true;
        }
        if ($project->autogradingenabled){
            $autograde = @$gradesByClass['auto'][0][0];
            echo '<tr><td align="left">'.get_string('autograde','techproject').'</td><td align="left">';
            echo make_grading_menu($project, 'autograde', $autograde, true);
            echo " <a href=\"?work=regrade&amp;id={$cm->id}\">".get_string('calculate','techproject').'</a></td></tr>';
        }
        if ($project->teacherusescriteria){
            if (@$freecriteria){
                foreach($freecriteria as $aFreeCriterion){
                    $freegrade = @$gradesByClass['free'][0][$aFreeCriterion->id];
                    echo "<tr><td align=\"left\">{$aFreeCriterion->label}</td><td align=\"left\">";
                    make_grading_menu($project, "free_{$aFreeCriterion->id}", $freegrade);
                    echo " x {$aFreeCriterion->weight}</td></tr>";
                }
            }
        }
        
        if (!$canGrade){
            print_simple_box(get_string('cannotevaluate', 'techproject'), 'center', '70%');
        }
        
    ?>
    </table>
    <br/>
    <br/>
    <input type="button" name="go_btn" value="<?php print_string('updategrades', 'techproject') ?>" onclick="senddata('save')" />
    <input type="button" name="erase_btn" value="<?php print_string('cleargrades', 'techproject') ?>" onclick="senddata('erase')" />
    </form>
    </center>