<?php //$Id: backuplib.php,v 1.2 2011-07-07 14:04:23 vf Exp $

    /**
    * Project : Technical Project Manager (IEEE like)
    *
    * This php script contains all the stuff to backup/restore
    * techproject mods
    *
    * @package mod-techproject
    * @subpackage backup/restore
    * @category mod
    * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
    * @date 2008/03/03
    * @version phase1
    * @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
    * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
    */

    /**
    * Requires and includes
    */ 
    include "{$CFG->dirroot}/mod/techproject/backup_commons_lib.php";

    //This is the "graphical" structure of the techproject mod:
    //                    
    //          techproject
    //          (CL,pk->id)             
    //              |
    //              +--------------------------------+--------------------------------+
    //              |                                |                                |
    //              |                        techproject_qualifier             techproject_assessment_criteria                 
    //              |                        (ML,IL,pk->id,fk->projectid)     (IL,pk->id,fk->projectid)                       
    //              |                                                                  |                                           
    //              |                                                         techproject_assessment                          
    //              |                                                         (UL,pk->id,fk->projectid,fk->groupid,fk->userid,fk->criterion)
    //          techproject_heading
    //          (UL,pk->id,fk->projectid,fk->groupid) 
    //              |
    //              |
    //   techproject_requirement
    //   (UL,pk->id,fk->projectid,
    //   fk->groupid,nt->fatherid) ----------------- techproject_spec_to_req                                   
    //                                     /-------- (UL,pk->id,fk->projectid,fk->groupid,fk->specid,fk->reqid)
    //   techproject_specification -------/                                  
    //   (UL,pk->id,fk->projectid,
    //    fk->groupid,nt->fatherid) ---------------- techproject_task_to_spec                                   
    //                                     /-------- (UL,pk->id,fk->projectid,fk->groupid,fk->taskid,fk->specid)
    //   techproject_task(1)       -------/         
    //   (UL,pk->id,fk->projectid,
    //   fk->groupid,fk->milestoneid,
    //   nt->fatherid)           ---------\ 
    //                                     \------- techproject_task_to_deliv                                                                     
    //   techproject_deliverable(2)       /-------- (UL,pk->id,fk->projectid,fk->groupid,fk->taskid,fk->delivid)
    //   (UL,pk->id,fk->projectid -------/         ,
    //    fk->groupid,fk->milestoneid, ------------- techproject_task_dependency                               
    //    nt->fatherid)                             (UL,pk->id,fk->projectid,fk->groupid,fk->master,fk->slave)
    //                    
    //             (1)--> techproject_milestone <-- (2)                             
    //                    (UL,pk->id,fk->projectid,fk->groupid) 
    //                        
    //
    // Meaning: pk->primary key field of the table
    //          fk->foreign key to link with parent
    //          nt->nested field (recursive data)
    //          CL->course level info
    //          ML->module level info
    //          IL->instance level info
    //          UL->user level info
    //          files->table may have files)
    //
    //-----------------------------------------------------------

    /**
    * This function backups all available modules in the current course
    * @param file $bf the backup file handle
    * @param object $preferences a set of collected values for backup
    */
    function techproject_backup_mods($bf, $preferences) {
        global $CFG;

        $status = true; 
        
        ////Iterate over techproject table
        $techprojects = get_records('techproject', 'course', $preferences->backup_course);

        if ($techprojects) {
            foreach ($techprojects as $aProject) {
                $status = techproject_backup_one_mod($bf, $preferences, $aProject);
            }
        }
        return $status;
    }
    
    /**
    * This function backups a single module
    * @param file $bf the backup file handle
    * @param object $preferences a set of collected values for backup
    * @param object $project the backupable instance record
    */
    function techproject_backup_one_mod($bf, $preferences, $project){

        if (is_numeric($project)) {
            $project = get_record('techproject', 'id', $project);
        }
        
        $status = true;

        //Start mod
        $level = 3;
        fwrite ($bf,start_tag("MOD",$level,true));
        //Print techproject data
        $level++;
        fwrite ($bf,full_tag("ID",$level,false,$project->id));
        fwrite ($bf,full_tag("MODTYPE",$level,false,"techproject"));
        fwrite ($bf,full_tag("NAME",$level,false,$project->name));
        fwrite ($bf,full_tag("DESCRIPTION",$level,false,$project->description));
        fwrite ($bf,full_tag("PROJECTSTART",$level,false,$project->projectstart));
        fwrite ($bf,full_tag("ASSESSMENTSTART",$level,false,$project->assessmentstart));
        fwrite ($bf,full_tag("PROJECTEND",$level,false,$project->projectend));
        fwrite ($bf,full_tag("TIMEMODIFIED",$level,false,$project->timemodified));
        fwrite ($bf,full_tag("ALLOWDELETEWHENASSIGNED",$level,false,$project->allowdeletewhenassigned));
        fwrite ($bf,full_tag("TIMEUNIT",$level,false,$project->timeunit));
        fwrite ($bf,full_tag("COSTUNIT",$level,false,$project->costunit));
        fwrite ($bf,full_tag("GUESTSALLOWED",$level,false,$project->guestsallowed));
        fwrite ($bf,full_tag("GUESTSCANUSE",$level,false,$project->guestscanuse));
        fwrite ($bf,full_tag("UNGROUPEDSEES",$level,false,$project->ungroupedsees));
        fwrite ($bf,full_tag("GRADE",$level,false,$project->grade));
        fwrite ($bf,full_tag("TEACHERUSESCRITERIA",$level,false,$project->teacherusescriteria));
        fwrite ($bf,full_tag("ALLOWNOTIFICATIONS",$level,false,$project->allownotifications));
        fwrite ($bf,full_tag("AUTOGRADINGENABLED",$level,false,$project->autogradingenabled));
        fwrite ($bf,full_tag("AUTOGRADINGWEIGHT",$level,false,$project->autogradingweight));
        fwrite ($bf,full_tag("ENABLECVS",$level,false,$project->enablecvs));
        fwrite ($bf,full_tag("USERISKCORRECTION",$level,false,$project->useriskcorrection));
        fwrite ($bf,full_tag("XSLFILTER",$level,false,$project->xslfilter));
        fwrite ($bf,full_tag("CSSFILTER",$level,false,$project->cssfilter));

        techproject_backup_qualifiers($bf, $preferences, $level, $project->id);
        techproject_backup_criteria($bf, $preferences, $level, $project->id);

        // check group usage after getting all related records
        $module = get_record('modules', 'name', 'techproject');
        $course = get_record('course', 'id', $project->course);
        $cm = get_record('course_modules', 'course', $project->course, 'instance', $project->id, 'module', $module->id);

        // if no groups, always store group 0 project (out of group, or single project)
        fwrite ($bf,start_tag("DEFAULTGROUP", $level, true));
        $level++;
        techproject_backup_heading($bf, $preferences, $level, $project->id, 0);
        techproject_backup_requirements($bf, $preferences, $level, $project->id, 0);
        techproject_backup_specifications($bf, $preferences, $level, $project->id, 0);
        techproject_backup_tasks($bf, $preferences, $level, $project->id, 0);
        techproject_backup_milestones($bf, $preferences, $level, $project->id, 0);
        techproject_backup_deliverables($bf, $preferences, $level, $project->id, 0);
        techproject_xmlwrite_association($bf, $project->id, 0, $level, 'spec_to_req', 'specid', 'reqid');
        techproject_xmlwrite_association($bf, $project->id, 0, $level, 'task_to_spec', 'taskid', 'specid');
        techproject_xmlwrite_association($bf, $project->id, 0, $level, 'task_to_deliv', 'taskid', 'delivid');
        techproject_xmlwrite_association($bf, $project->id, 0, $level, 'task_dependency', 'master', 'slave');
        $level--;
        fwrite ($bf,end_tag("DEFAULTGROUP",4,true));

        // if groups and user info is backuped, store groups information along with project data for each group
        if($groupmode = groups_get_activity_groupmode($cm, $course)){
            $backup_user_info = "backup_user_info_techproject_instance_{$project->id}";
            if (@$preferences->{$backup_user_info} && @$preferences->backup_user_info_techproject){
                techproject_backup_assessments($bf, $preferences, $level, $project->id);
                $groups = get_groups($project->course);
                if ($groups){
                    foreach($groups as $aGroup){
                        fwrite ($bf,start_tag("GROUP",4,true));
                        $level++;
                        fwrite ($bf,full_tag('ID', $level, false, $aGroup->id));
                        techproject_backup_heading($bf, $preferences, $level, $project->id, $aGroup->id);
                        techproject_backup_requirements($bf, $preferences, $level, $project->id, $aGroup->id);
                        techproject_backup_specifications($bf, $preferences, $level, $project->id, $aGroup->id);
                        techproject_backup_tasks($bf, $preferences, $level, $project->id, $aGroup->id);
                        techproject_backup_milestones($bf, $preferences, $level, $project->id, $aGroup->id);
                        techproject_backup_deliverables($bf, $preferences, $level, $project->id, $aGroup->id);
                        if (@$preferences->user_files)
                            techproject_backup_submissions($bf, $preferences, $level, $project->id, $aGroup->id);
                        techproject_xmlwrite_association($bf, $project->id, $aGroup->id, $level, 'spec_to_req', 'specid', 'reqid');
                        techproject_xmlwrite_association($bf, $project->id, $aGroup->id, $level, 'task_to_spec', 'taskid', 'specid');
                        techproject_xmlwrite_association($bf, $project->id, $aGroup->id, $level, 'task_to_deliv', 'taskid', 'delivid');
                        techproject_xmlwrite_association($bf, $project->id, $aGroup->id, $level, 'task_dependency', 'master', 'slave');
                        $level--;
                        fwrite ($bf,end_tag("GROUP",4,true));
                    }
                }
            }
        }

        //End mod
        $status = fwrite ($bf,end_tag('MOD',3,true));
        return $status;
    }
        
    /**
    * stores default qualifiers for reference. Default qualifier set could be used 
    * for checking project environement compatibility when tranferring a project backup
    * from one server to another
    * @param file $bf the backup file handle
    * @param object $preferences a set of collected values for backup
    * @param int $level the indent level
    * @param int $projectid the id of the module instance in process
    */
    function techproject_backup_qualifiers($bf, $preferences, $level, $projectid){

        fwrite($bf, start_tag('QUALIFIERS', $level, true));
        $level++;
        fwrite($bf, start_tag('DEFAULT', $level, true));
        $level++;
        techproject_backup_one_qualifier($bf, $preferences, $level, 0, 'strength');
        techproject_backup_one_qualifier($bf, $preferences, $level, 0, 'priority');
        techproject_backup_one_qualifier($bf, $preferences, $level, 0, 'severity');
        techproject_backup_one_qualifier($bf, $preferences, $level, 0, 'complexity');
        techproject_backup_one_qualifier($bf, $preferences, $level, 0, 'worktype');
        techproject_backup_one_qualifier($bf, $preferences, $level, 0, 'taskstatus');
        techproject_backup_one_qualifier($bf, $preferences, $level, 0, 'risk');
        $level--;
        fwrite($bf, end_tag('DEFAULT', $level, true));
        fwrite($bf, start_tag('LOCAL', $level, true));
        $level++;
        techproject_backup_one_qualifier($bf, $preferences, $level, $projectid, 'strength');
        techproject_backup_one_qualifier($bf, $preferences, $level, $projectid, 'priority');
        techproject_backup_one_qualifier($bf, $preferences, $level, $projectid, 'severity');
        techproject_backup_one_qualifier($bf, $preferences, $level, $projectid, 'complexity');
        techproject_backup_one_qualifier($bf, $preferences, $level, $projectid, 'worktype');
        techproject_backup_one_qualifier($bf, $preferences, $level, $projectid, 'taskstatus');
        techproject_backup_one_qualifier($bf, $preferences, $level, $projectid, 'risk');
        $level--;
        fwrite($bf, end_tag('LOCAL', $level, true));
        $level--;
        fwrite($bf, end_tag('QUALIFIERS', $level, true));
        
        // stores project custom qualifiers if any
    }
    
    /**
    * stores a single qualifier
    * @param file $bf the backup file handle
    * @param object $preferences a set of collected values for backup
    * @param int $level the indent level
    * @param int $projectid the id of the module instance in process
    * @param string $qualifier the qualifier name
    */
    function techproject_backup_one_qualifier($bf, $preferences, $level, $projectid, $qualifier){
        $recs = get_records_select("techproject_qualifier", " projectid = $projectid AND domain = '$qualifier' ");
        if ($recs){
            fwrite($bf, start_tag(strtoupper("{$qualifier}_QUALIFIER"), $level, true));
            $level++;
            foreach($recs as $aQualifier){
                fwrite($bf, start_tag(strtoupper($qualifier), $level, true));
                $level++;
                fwrite($bf, full_tag('DOMAIN',$level,false,$qualifier));
                fwrite($bf, full_tag('CODE',$level,false,@$aQualifier->{$qualifier}));
                fwrite($bf, full_tag('LABEL',$level,false,$aQualifier->label));
                fwrite($bf, full_tag('DESCRIPTION',$level,false,$aQualifier->description));
                $level--;
                fwrite($bf, end_tag(strtoupper($qualifier), $level, true));
            }
            $level--;
            fwrite($bf, end_tag("{$qualifier}_QUALIFIER", $level, true));
        }
    }

    /**
    * backups assessment criteria (id needs being stored => fk->assessments)
    * @param file $bf the backup file handle
    * @param object $preferences a set of collected values for backup
    * @param int $level the indent level
    * @param int $projectid the id of the module instance in process
    */
    function techproject_backup_criteria($bf, $preferences, $level, $projectid){
        fwrite($bf, start_tag('CRITERIA', $level, true));
        $level++;
        $recs = get_records('techproject_criterion', 'projectid', $projectid);
        if ($recs){
            foreach($recs as $aCriterion){
                fwrite($bf, start_tag('CRITERION', $level, true));
                $level++;
                fwrite($bf, full_tag('ID',$level,false,$aCriterion->id));
                fwrite($bf, full_tag('CRITERION',$level,false,$aCriterion->criterion));
                fwrite($bf, full_tag('LABEL',$level,false,$aCriterion->label));
                fwrite($bf, full_tag('WEIGHT',$level,false,$aCriterion->weight));
                fwrite($bf, full_tag('ISFREE',$level,false,$aCriterion->isfree));
                $level--;
                fwrite($bf, end_tag("CRITERION", $level, true));
            }
        }
        $level--;
        fwrite($bf, end_tag('CRITERIA', $level, true));
    }

    /**
    * backups assessments
    * @param file $bf the backup file handle
    * @param object $preferences a set of collected values for backup
    * @param int $level the indent level
    * @param int $projectid the id of the module instance in process
    */
    function techproject_backup_assessments($bf, $preferences, $level, $projectid){
        fwrite($bf, start_tag('ASSESSMENTS', $level, true));
        $level++;
        $recs = get_records('techproject_assessment', 'projectid', $projectid);
        if ($recs){
            foreach($recs as $anAssessment){
                fwrite($bf, start_tag('GRADE', $level, true));
                $level++;
                fwrite($bf, full_tag('GROUP',$level,false,$anAssessment->groupid));
                fwrite($bf, full_tag('USER',$level,false,$anAssessment->userid));
                fwrite($bf, full_tag('ITEM',$level,false,$anAssessment->itemid));
                fwrite($bf, full_tag('ITEMCLASS',$level,false,$anAssessment->itemclass));
                fwrite($bf, full_tag('CRITERION',$level,false,$anAssessment->criterion));
                fwrite($bf, full_tag('GRADE',$level,false,$anAssessment->grade));
                $level--;
                fwrite($bf, end_tag("GRADE", $level, true));
            }
        }
        $level--;
        fwrite($bf, end_tag('ASSESSMENTS', $level, true));
    }

    /**
    * backups heading descriptions for a group
    * @param file $bf the backup file handle
    * @param object $preferences a set of collected values for backup
    * @param int $level the indent level
    * @param int $projectid the id of the module instance in process
    * @param int $groupid the id of the group being backup
    */   
    function techproject_backup_heading($bf, $preferences, $level, $projectid, $groupid){
        $heading = get_record('techproject_heading', 'projectid', $projectid, 'groupid', $groupid);
        //Start mod
        if ($heading){
            fwrite ($bf,start_tag("HEADING",$level,true));
            $level++;
            //Print heading data ignoring id and foreign keys. Heading might have never been initialized.
                fwrite ($bf,full_tag("TITLE", $level, false, $heading->title));
                fwrite ($bf,full_tag("ABSTRACT", $level, false, $heading->abstract)); 
                fwrite ($bf,full_tag("RATIONALE", $level, false, $heading->rationale)); 
                fwrite ($bf,full_tag("ENVIRONMENT", $level, false, $heading->environment)); 
                fwrite ($bf,full_tag("ORGANISATION", $level, false, $heading->organisation)); 
                fwrite ($bf,full_tag("DEPARTMENT", $level, false, $heading->department)); 
            $level--;
            fwrite ($bf,end_tag("HEADING",$level,true));
        }
    }
    
    /**
    * backups requirements for a group
    * @param file $bf the backup file handle
    * @param object $preferences a set of collected values for backup
    * @param int $level the indent level
    * @param int $projectid the id of the module instance in process
    * @param int $groupid the id of the group being backup
    */   
    function techproject_backup_requirements($bf, $preferences, $level, $projectid, $groupid){
        global $SITE;
        
        $requirements = get_records_select('techproject_requirement', "projectid = {$projectid} AND groupid = {$groupid} AND fatherid = 0", 'ordering ASC');
        if ($requirements){
            techproject_xmlwrite_entity($bf, $projectid, $groupid, $level, 'requirement', $requirements, $SITE->TECHPROJECT_BACKUP_FIELDS['requirement'], true, false, true);
        }
    }
    
    /**
    * backups specifications for a group
    * @param file $bf the backup file handle
    * @param object $preferences a set of collected values for backup
    * @param int $level the indent level
    * @param int $projectid the id of the module instance in process
    * @param int $groupid the id of the group being backup
    */   
    function techproject_backup_specifications($bf, $preferences, $level, $projectid, $groupid){
        global $SITE;
        
        $specifications = get_records_select('techproject_specification', "projectid = {$projectid} AND groupid = {$groupid} AND fatherid = 0", 'ordering ASC');
        if ($specifications){
            techproject_xmlwrite_entity($bf, $projectid, $groupid, $level, 'specification', $specifications, $SITE->TECHPROJECT_BACKUP_FIELDS['specification'], true, false, true);
        }
    }
    
    /**
    * backups tasks for a group
    * @param file $bf the backup file handle
    * @param object $preferences a set of collected values for backup
    * @param int $level the indent level
    * @param int $projectid the id of the module instance in process
    * @param int $groupid the id of the group being backup
    */   
    function techproject_backup_tasks($bf, $preferences, $level, $projectid, $groupid){
        global $SITE;

        $tasks = get_records_select('techproject_task', "projectid = {$projectid} AND groupid = {$groupid} AND fatherid = 0", 'ordering ASC');
        if ($tasks){
            techproject_xmlwrite_entity($bf, $projectid, $groupid, $level, 'task', $tasks, $SITE->TECHPROJECT_BACKUP_FIELDS['task'], true, false, true);
        }
    }
    
    /**
    * backups milestones for a group
    * @param file $bf the backup file handle
    * @param object $preferences a set of collected values for backup
    * @param int $level the indent level
    * @param int $projectid the id of the module instance in process
    * @param int $groupid the id of the group being backup
    */   
    function techproject_backup_milestones($bf, $preferences, $level, $projectid, $groupid){
        global $SITE;

        $milestones = get_records_select('techproject_milestone', "projectid = {$projectid} AND groupid = {$groupid}", 'ordering ASC');
        if ($milestones){
            techproject_xmlwrite_entity($bf, $projectid, $groupid, $level, 'milestone', $milestones, $SITE->TECHPROJECT_BACKUP_FIELDS['milestone'], false);
        }
    }
    
    /**
    * backups milestones for a group
    * @param file $bf the backup file handle
    * @param object $preferences a set of collected values for backup
    * @param int $level the indent level
    * @param int $projectid the id of the module instance in process
    * @param int $groupid the id of the group being backup
    */   
    function techproject_backup_deliverables($bf, $preferences, $level, $projectid, $groupid){
        global $SITE;

        $deliverables = get_records_select('techproject_deliverable', "projectid = {$projectid} AND groupid = {$groupid} AND fatherid = 0", 'ordering ASC');
        if ($deliverables){
            techproject_xmlwrite_entity($bf, $projectid, $groupid, $level, 'deliverable', $deliverables, $SITE->TECHPROJECT_BACKUP_FIELDS['deliverable'], true, false, true);
        }
    }
    
    /**
    * backups submissions
    * we may have nothing to do here, as course stores already files.
    * @param file $bf the backup file handle
    * @param object $preferences a set of collected values for backup
    * @param int $level the indent level
    * @param int $projectid the id of the module instance in process
    * @param int $groupid the id of the group being backup
    */
    function techproject_backup_submissions($bf, $preferences, $level, $projectid, $groupid){
    }
    
    /**
    * prints a generic tree_shaped entity to xml
    * @param file $bf the xml file buffer
    * @param int $projectid the id of the module instance in process
    * @param int $groupid the id of the group being backup
    * @param int $level the indent level
    * @param string $entity the entities name
    * @param array $entries an array of entries to be xmlized
    * @param string $fields the comma separated list of fields that will generate entity elements
    * @param boolean $isTree tells if the entity is tree or list
    * @param boolean $flat if entity is a tree, do we generate a record list, or an element tree 
    * @param boolean $isRoot must be set true for generating the root XML element
    */
    function techproject_xmlwrite_entity($bf, $projectid, $groupid, $level, $entity, $entries, $fields, $isTree = true, $flat = true, $isRoot = true){
        if ($isRoot){ 
            fwrite ($bf, start_tag(strtoupper("{$entity}s"), $level, true));
            $level++;
        }
        foreach($entries as $anEntry){
            fwrite ($bf,start_tag(strtoupper($entity), $level, true));
            $level++;
            fwrite ($bf,full_tag("ID", $level, false, $anEntry->id));
            if ($isTree) fwrite ($bf,full_tag("FATHERID", $level, false, $anEntry->fatherid));
            fwrite ($bf,full_tag("ORDERING", $level, false, $anEntry->ordering));
            fwrite ($bf,full_tag("USERID", $level, false, $anEntry->userid));
            fwrite ($bf,full_tag("CREATED", $level, false, $anEntry->created));
            fwrite ($bf,full_tag("MODIFIED", $level, false, $anEntry->modified));
            fwrite ($bf,full_tag("LASTUSERID", $level, false, $anEntry->lastuserid));

            // tagging individual properties
            $fieldArray = explode(",", $fields);
            if (!empty($fieldArray)){
                foreach($fieldArray as $aField){
                    if (!empty($aField)){
                        fwrite ($bf,full_tag(strtoupper($aField), $level, false, $anEntry->{$aField}));
                    }
                }
            }
            // tagging subs
            if ($isTree && !$flat){
                $subs = get_records_select("techproject_{$entity}", "projectid = {$projectid} AND groupid = {$groupid} AND fatherid = {$anEntry->id}");
                if ($subs){
                    techproject_xmlwrite_entity($bf, $projectid, $groupid, $level, $entity, $subs, $fields, true, false, false);
                }
            }
            $level--;
            fwrite ($bf,end_tag(strtoupper($entity),$level,true));
        }
        if ($isRoot){
            $level--;
            fwrite ($bf,end_tag(strtoupper("{$entity}s"), $level, true));
        }
    }

    /**
    * prints a generic cross-entity association to xml
    * @param file $bf the xml file buffer
    * @param int $projectid the id of the module instance in process
    * @param int $groupid the id of the group being backup
    * @param int $level the indent level
    * @param string $association an association by name
    * @param string $key1 the Id key of the source entity
    * @param string $key2 the Id key of the destination entity
    */
    function techproject_xmlwrite_association($bf, $projectid, $groupid, $level, $association, $key1, $key2){        
        $entries = get_records_select("techproject_{$association}", "projectid = {$projectid} AND groupid = {$groupid}");        
        fwrite ($bf, start_tag(strtoupper($association), $level, true));
        $level++;
        fwrite ($bf,full_tag('KEY1', $level, false, $key1));
        fwrite ($bf,full_tag('KEY2', $level, false, $key2));
        if ($entries){
            foreach($entries as $anEntry){
                fwrite ($bf,start_tag('MAP', $level, true));
                $level++;
                fwrite ($bf,full_tag('FROM', $level, false, $anEntry->{$key1}));
                fwrite ($bf,full_tag('TO', $level, false, $anEntry->{$key2}));
                $level--;
                fwrite ($bf,end_tag('MAP', $level, true));
            }
        }
        $level--;
        fwrite ($bf,end_tag(strtoupper($association), $level, true));
    }

   /**
   * Returns an array of info (name,value)
   * @param int $course the course id
   * @param boolean $user_data true if we do need storing user related indexes
   * @param int $backup_unique_code the actual unique id of the backup session
   */
   function techproject_check_backup_mods($course, $user_data=false, $backup_unique_code) {

        // First the techproject data
        $i = 0;

        $info[$i][0] = get_string('modulenameplural','techproject');
        if ($ids = techproject_ids($course)) {
            $info[$i][1] = count($ids);
        } else {
            $info[$i][1] = 0;
        }
        
        $i++;
        
        if ($user_data){
            $info[$i][0] = get_string('headings','techproject');
            if ($ids = techproject_headings_ids($course)) {
                $info[$i][1] = count($ids);
            } else {
                $info[$i][1] = 0;
            }

            $i++;

            $info[$i][0] = get_string('requirements','techproject');
            if ($ids = techproject_requirements_ids($course)) {
                $info[$i][1] = count($ids);
            } else {
                $info[$i][1] = 0;
            }

            $i++;

            $info[$i][0] = get_string('specifications','techproject');
            if ($ids = techproject_specifications_ids($course)) {
                $info[$i][1] = count($ids);
            } else {
                $info[$i][1] = 0;
            }

            $i++;

            $info[$i][0] = get_string('tasks','techproject');
            if ($ids = techproject_tasks_ids($course)) {
                $info[$i][1] = count($ids);
            } else {
                $info[$i][1] = 0;
            }

            $i++;

            $info[$i][0] = get_string('milestones','techproject');
            if ($ids = techproject_milestones_ids($course)) {
                $info[$i][1] = count($ids);
            } else {
                $info[$i][1] = 0;
            }
            
            $i++;

            $info[$i][0] = get_string('deliverables','techproject');
            if ($ids = techproject_deliverables_ids($course)) {
                $info[$i][1] = count($ids);
            } else {
                $info[$i][1] = 0;
            }
        }

        return $info;
    }

    /**
    * Return a content encoded to support interactivities linking. Every module
    * should have its own. They are called automatically from the backup procedure.
    */
    function techproject_encode_content_links ($content, $preferences) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot,"/");

        // Link to the list of techprojects
        $search="/(".$base."\/mod\/techproject\/index.php\?id\=)([0-9]+)/";
        $result= preg_replace($search,'$@TECHPROJECTINDEX*$2@$', $content);

        // Link to techproject view by moduleid
        $search="/(".$base."\/mod\/techproject\/view.php\?id\=)([0-9]+)/";
        $result= preg_replace($search,'$@TECHPROJECTVIEWBYID*$2@$', $result);

        return $result;
    }

    // INTERNAL FUNCTIONS. BASED IN THE MOD STRUCTURE

    // Returns an array of techproject ids
    function techproject_ids ($course) {
        global $CFG;

        $query = "
            SELECT 
                t.id, 
                t.course
            FROM 
                {$CFG->prefix}techproject t
            WHERE 
                t.course = '{$course}'
        ";
        return get_records_sql ($query);
    }

    // Returns an array of heading ids
    function techproject_headings_ids ($course) {
        global $CFG;

        $query = "
            SELECT 
                h.id, 
                t.id
            FROM 
                {$CFG->prefix}techproject t,
                {$CFG->prefix}techproject_heading h
            WHERE 
                h.projectid = t.id AND
                t.course = '{$course}'
        ";
        return get_records_sql($query);
    }

    // Returns an array of requirement ids
    function techproject_requirements_ids ($course) {
        global $CFG;

        $query = "
            SELECT 
                r.id,
                t.id
            FROM 
                {$CFG->prefix}techproject t,
                {$CFG->prefix}techproject_requirement r
            WHERE 
                r.projectid = t.id AND
                t.course = '{$course}'
        ";
        return get_records_sql($query);
    }

    // Returns an array of specification ids
    function techproject_specifications_ids ($course) {
        global $CFG;

        $query = "
            SELECT 
                s.id,
                t.id
            FROM 
                {$CFG->prefix}techproject t,
                {$CFG->prefix}techproject_specification s
            WHERE 
                s.projectid = t.id AND
                t.course = '{$course}'
        ";
        return get_records_sql($query);
    }

    // Returns an array of task ids
    function techproject_tasks_ids ($course) {
        global $CFG;

        $query = "
            SELECT 
                ta.id,
                t.id
            FROM 
                {$CFG->prefix}techproject t,
                {$CFG->prefix}techproject_task ta
            WHERE 
                ta.projectid = t.id AND
                t.course = '{$course}'
        ";
        return get_records_sql($query);
    }

    // Returns an array of milestone ids
    function techproject_milestones_ids ($course) {
        global $CFG;

        $query = "
            SELECT 
                m.id,
                t.id
            FROM 
                {$CFG->prefix}techproject t,
                {$CFG->prefix}techproject_task m
            WHERE
                m.projectid = t.id AND
                t.course = '{$course}'
        ";
        return get_records_sql($query);
    }

    // Returns an array of deliverable ids
    function techproject_deliverables_ids ($course) {
        global $CFG;

        $query = "
            SELECT 
                d.id,
                t.id
            FROM 
                {$CFG->prefix}techproject t,
                {$CFG->prefix}techproject_deliverable d
            WHERE
                d.projectid = t.id AND
                t.course = '{$course}'
        ";
        return get_records_sql($query);
    }
  
?>