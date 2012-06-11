<?php //$Id: restorelib.php,v 1.2 2011-07-07 14:04:23 vf Exp $

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
    * requires and includes
    */

    include "{$CFG->dirroot}/mod/techproject/backup_commons_lib.php";
    include "{$CFG->dirroot}/mod/techproject/filesystemlib.php";

    //This is the "graphical" structure of the techproject mod:
    //                    
    //          techproject
    //          (CL,pk->id)             
    //              |
    //              +--------------------------------+--------------------------------+
    //              |                                |                                |
    //              |                        techproject_qualifier             techproject_assessment_criteria                 
    //              |                                                          (IL,pk->id,fk->projectid)                       
    //              |                                                                 |                                           
    //              |                                                          techproject_assessment                          
    //              |                                                          (UL,pk->id,fk->projectid,fk->groupid,fk->userid,fk->criterion)
    //              |
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

    //This function executes all the restore procedure about this mod
    function techproject_restore_mods($mod,$restore) {
        global $CFG;
        global $SITE;

        $status = true;

        //Get record from backup_ids
        $data = backup_getid($restore->backup_unique_code,$mod->modtype,$mod->id);

        if ($data) {
            //Now get completed xmlized object
            $info = $data->info;
            //if necessary, write to restorelog and adjust date/time fields
            if ($restore->course_startdateoffset) {
                restore_log_date_changes('techproject', $restore, $info['MOD']['#'], array('TIMEDUE', 'TIMEAVAILABLE'));
            }
            // traverse_xmlize($info);                                   //Debug
            // print_object ($GLOBALS['traverse_array']);                //Debug
            // $GLOBALS['traverse_array']="";                            //Debug
            // print_object ($restore);                                  //Debug

            // Do some checks first
            // Check if default qualifiers are identical
            /**
            * global qualifiers (plateform wide) for project 0 should be identical. Make some warning if not.
            */
            echo '<li>'.get_string('preparingrestore', 'techproject').'...';

            /*
            $result = techproject_check_global_qualifiers($info, $restore);
            if (!$result[0]){
                echo "<br/>";
                print_string('baddefaultqualifierseterror','techproject');
                echo " : \"".$result[1]."\"";
                return false;
            }
            else{
                echo '<br/>...'.get_string('compatiblequalifiersfound','techproject').'</li>';
                echo '<ul>';
            }
            */

            // restore master record
            //Now, build the TECHPROJECT record structure
            $techproject->course = $restore->course_id;
            $modXmlBase = $info['MOD']['#'];
            $oldid = backup_todb($modXmlBase['ID']['0']['#']);
            $techproject->name = backup_todb($modXmlBase['NAME']['0']['#']);
            $techproject->description = backup_todb($modXmlBase['DESCRIPTION']['0']['#']);
            $techproject->projectstart = backup_todb($modXmlBase['PROJECTSTART']['0']['#']);
            $techproject->assessmentstart = backup_todb($modXmlBase['ASSESSMENTSTART']['0']['#']);
            $techproject->projectend = backup_todb($modXmlBase['PROJECTEND']['0']['#']);
            $techproject->timemodified = backup_todb($modXmlBase['TIMEMODIFIED']['0']['#']);
            $techproject->allowdeletewhenassigned = backup_todb($modXmlBase['ALLOWDELETEWHENASSIGNED']['0']['#']);
            $techproject->timeunit = backup_todb($modXmlBase['TIMEUNIT']['0']['#']);
            $techproject->costunit = backup_todb($modXmlBase['COSTUNIT']['0']['#']);
            $techproject->guestsallowed = backup_todb($modXmlBase['GUESTSALLOWED']['0']['#']);
            $techproject->guestscanuse = backup_todb($modXmlBase['GUESTSCANUSE']['0']['#']);
            $techproject->ungroupedsees = backup_todb($modXmlBase['UNGROUPEDSEES']['0']['#']);
            $techproject->grade = backup_todb($modXmlBase['GRADE']['0']['#']);
            $techproject->teacherusescriteria = backup_todb($modXmlBase['TEACHERUSESCRITERIA']['0']['#']);
            $techproject->allownotifications = backup_todb($modXmlBase['ALLOWNOTIFICATIONS']['0']['#']);
            $techproject->autogradingenabled = backup_todb($modXmlBase['AUTOGRADINGENABLED']['0']['#']);
            $techproject->autogradingweight = backup_todb($modXmlBase['AUTOGRADINGWEIGHT']['0']['#']);
            $techproject->enablecvs = backup_todb($modXmlBase['ENABLECVS']['0']['#']);
            $techproject->useriskcorrection = backup_todb($modXmlBase['USERISKCORRECTION']['0']['#']);
            $techproject->xslfilter = backup_todb($modXmlBase['XSLFILTER']['0']['#']);
            $techproject->cssfilter = backup_todb($modXmlBase['CSSFILTER']['0']['#']);
            
            //We have to recode the grade field if it is <0 (scale)
            if ($techproject->grade < 0) {
                $scaleId = backup_get_new_id($restore->backup_unique_code,'scale',abs($techproject->grade));        
                if ($scaleId) {
                    $techproject->grade = -($scaleId);       
                }
            }

            //The structure is equal to the db, so insert the project
            $newid = insert_record('techproject',$techproject);

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,$mod->modtype,
                             $mod->id, $newid);

                //Do some output     
                if (!defined('RESTORE_SILENTLY')) {
                    echo '<li>'.get_string('modulename','techproject')." \"".format_string(stripslashes($techproject->name),true).'\"</li>';
                }
                backup_flush(300);

                // restore the qualifiers
                if (!defined('RESTORE_SILENTLY')) {
                    echo '<li>'.get_string('restoringqualifiers','techproject').'</li><ul>';
                }
                techproject_restore_local_qualifier($info, $restore, 'strength', $newid);
                techproject_restore_local_qualifier($info, $restore, 'priority', $newid);
                techproject_restore_local_qualifier($info, $restore, 'severity', $newid);
                techproject_restore_local_qualifier($info, $restore, 'complexity', $newid);
                techproject_restore_local_qualifier($info, $restore, 'worktype', $newid);
                techproject_restore_local_qualifier($info, $restore, 'risk', $newid);

                // restore criteria
                if (!defined('RESTORE_SILENTLY')) {
                    echo '</ul><li>'.get_string('restoringcriteria','techproject').'</li><ul>';
                }
                techproject_restore_criteria($info, $restore, $newid);
                
                // restore the default project
                if (!defined('RESTORE_SILENTLY')) {
                    echo '</ul><li>'.get_string('restoringdefaultprojectdata','techproject').'</li><ul>';
                }
                techproject_restore_heading($info, $restore, $newid, true);
                techproject_restore_entity($info, $restore, 'requirement', $newid, true, $SITE->TECHPROJECT_BACKUP_FIELDS['requirement']);
                techproject_restore_entity($info, $restore, 'specification', $newid, true, $SITE->TECHPROJECT_BACKUP_FIELDS['specification']);
                // milestone must go first as it drives foreign keys in tasks and deliverables 
                techproject_restore_entity($info, $restore, 'milestone', $newid, true, $SITE->TECHPROJECT_BACKUP_FIELDS['milestone']);
                techproject_restore_entity($info, $restore, 'task', $newid, true, $SITE->TECHPROJECT_BACKUP_FIELDS['task']);
                techproject_restore_entity($info, $restore, 'deliverable', $newid, true, $SITE->TECHPROJECT_BACKUP_FIELDS['deliverable']);
    
                techproject_restore_association($info, $restore, 'spec_to_req', $newid, true);
                techproject_restore_association($info, $restore, 'task_to_spec', $newid, true);
                techproject_restore_association($info, $restore, 'task_to_deliv', $newid, true);
                techproject_restore_association($info, $restore, 'task_dependency', $newid, true);

                if (!defined('RESTORE_SILENTLY')) {
                    echo '</ul>';
                }

                //Now check if want to restore user data and do it.
                if (restore_userdata_selected($restore,'techproject',$mod->id)) { 
                    if (!defined('RESTORE_SILENTLY')) {
                        echo '<li>'.get_string('restoringuserprojectdata','techproject').'</li><ul>';
                    }
                    $oldgroups = techproject_restore_heading($info, $restore, $newid, false);
                    techproject_restore_entity($info, $restore, 'requirement', $newid, false, $SITE->TECHPROJECT_BACKUP_FIELDS['requirement']);
                    techproject_restore_entity($info, $restore, 'specification', $newid, false, $SITE->TECHPROJECT_BACKUP_FIELDS['specification']);
                    // milestone must go first as it drives foreign keys in tasks and deliverables 
                    techproject_restore_entity($info, $restore, 'milestone', $newid, false, $SITE->TECHPROJECT_BACKUP_FIELDS['milestone']);
                    techproject_restore_entity($info, $restore, 'task', $newid, false, $SITE->TECHPROJECT_BACKUP_FIELDS['task']);
                    techproject_restore_entity($info, $restore, 'deliverable', $newid, false, $SITE->TECHPROJECT_BACKUP_FIELDS['deliverable']);
        
                    techproject_restore_association($info, $restore, 'spec_to_req', $newid, false);
                    techproject_restore_association($info, $restore, 'task_to_spec', $newid, false);
                    techproject_restore_association($info, $restore, 'task_to_deliv', $newid, false);
                    techproject_restore_association($info, $restore, 'task_dependency', $newid, false);
                    techproject_restore_assessments($info, $restore, $newid);
                    if (!defined('RESTORE_SILENTLY')) {
                        echo '</ul>';
                    }
                }
                
                // Finally we must reencode the physical file structure
                // 1 : renumber the techproject storing repository to new projectid
                // 2 : rehash the group numbers using new group ids
                // 3 : eventually do some cleanup
 
                if (!defined('RESTORE_SILENTLY')) {
                    echo '</ul><li>'.get_string('restoringandfixingfiles','techproject').'</li><ul>';
                }
               
                $projectrelroot = $restore->course_id.'/techprojects';
                $projectdirroot = str_replace("\\", "/", $CFG->dataroot); 
                $projectdirroot .= '/'.$projectrelroot; 

                // a small hack to avoid the "File too long" error
                // rename("{$projectdirroot}/{$oldid}", "{$projectdirroot}/{$newid}");
                $oldWD = getcwd();
                if (!filesystem_is_dir($projectrelroot)){
                    filesystem_create_dir($projectrelroot, FS_RECURSIVE);
                }
                chdir($projectdirroot);
                @rename($oldid, $newid);
                chdir($oldWD); 

                
                if (restore_userdata_selected($restore,'techproject',$mod->id)) { 
                    if (!is_dir("{$projectdirroot}/{$newid}")){
                    	mkdir("{$projectdirroot}/{$newid}", 0777, true);
                    }
                    foreach($oldgroups as $anOldGroup){
                        $oldHash = md5("techproject{$oldid}_{$anOldGroup}");
                        $aNewGroup = backup_get_new_id($restore->backup_unique_code, 'groups', $anOldGroup);
                        $newHash = md5("techproject{$newid}_{$aNewGroup}");
                        if (file_exists("{$projectdirroot}/{$newid}/{$oldHash}")){
                            if ($CFG->debug > 5) echo "renaming folder $anOldGroup({$oldHash}) to $aNewGroup({$newHash})<br/>";

                            // @rename("{$projectdirroot}/{$newid}/{$oldHash}", "{$projectdirroot}/{$newid}/{$newHash}");
                            $oldWD = getcwd();
                            chdir("{$projectdirroot}/{$newid}");
                            rename($oldHash, $newHash);
                            chdir($oldWD); 
                        }
                    }
                } else {
                    // If we do not need files from users, do some clean up in files (excepting files in
                    // default project).
                    if (!is_dir("{$projectdirroot}/{$newid}")){
                    	mkdir("{$projectdirroot}/{$newid}", 0777, true);
                    }
                    $projectdir = opendir("{$projectdirroot}/{$newid}");
                    while($agroupdir = readdir($projectdir)){
                        if (preg_match('/^\./', $agroupdir)) continue;
                        if ($agroupdir == md5(0)) continue;
                        fulldelete("{$projectdirroot}/{$newid}/{$agroupdir}");
                    }
                }
                
                if (!defined('RESTORE_SILENTLY')) {
                    echo '</ul>';
                }
            } 
            else {
                $status = false;
            }
        } 
        else {
            $status = false;
        }
        return $status;
    }
    
    /**
    * checks if global qualifiers in the backup are compatible with default qualifiers in database
    */    
    function techproject_check_global_qualifiers(&$info, &$restore){
        foreach(array_keys($info['MOD']['#']['QUALIFIERS']['0']['#']['DEFAULT']['0']['#']) as $aXmlQualifierId){
            preg_match("/(.*)_QUALIFIER/", $aXmlQualifierId, $matches);
            $qualifierName = $matches[1];
            foreach(array_values($info['MOD']['#']['QUALIFIERS']['0']['#']['DEFAULT']['0']['#'][$aXmlQualifierId]['0']['#'][$qualifierName]) as $aXmlQualifierSet){
                $aQualifier->code = $aXmlQualifierSet['#']['CODE']['0']['#'];
                // $aQualifier->label = $aXmlQualifierSet['#']['LABEL']['0']['#'];
                // $aQualifier->description = $aXmlQualifierSet['#']['DESCRIPTION']['0']['#'];
                $qualifiers[strtolower($qualifierName)][] = $aQualifier->code;
            }
        }
        foreach(array_keys($qualifiers) as $aQualifierName){
            $aQualifierSet = get_records("techproject_{$aQualifierName}", 'projectid', 0);
            if (!$aQualifierSet) return array(false,"no $aQualifierName");
            foreach($aQualifierSet as $aQualifier){
                if (! in_array($aQualifier->{$aQualifierName}, $qualifiers[$aQualifierName])) return array(false, $aQualifierName);
            }
        }
        return array(true, null);
    }

    /**
    * This function restores a qualifier
    *
    */
    function techproject_restore_local_qualifier(&$info, &$restore, $qualifier, $newProjectId){
        global $CFG;
        
        // ensures there where records for that
        if (is_array($info['MOD']['#']['QUALIFIERS']['0']['#']['LOCAL']['0']['#'])){
            // ensures project qualifier set is empty
            delete_records("techproject_{$qualifier}", 'projectid', $newProjectId);
            foreach(array_values($info['MOD']['#']['QUALIFIERS']['0']['#']['LOCAL']['0']['#'][strtoupper($qualifier).'_QUALIFIER']['0']['#'][strtoupper($qualifier)]) as $aQualifierSet){
                $record->projectid = $newProjectId;
                $record->domain = $qualifier;
                $record->code = backup_todb($aQualifierSet['#']['CODE']['0']['#']);
                $record->label = backup_todb($aQualifierSet['#']['LABEL']['0']['#']);
                $record->description = backup_todb($aQualifierSet['#']['DESCRIPTION']['0']['#']);
                $returnid = insert_record("techproject_qualifier", $record);
                if ($CFG->debug > 5) echo "inserting local qualifier '$qualifier'<br/>";
            }
        }
    }

    /**
    * This function restores criteria
    *
    */
    function techproject_restore_criteria(&$info, &$restore, $newProjectId){
        global $CFG;
        
        // ensures there where records for that
        if (@isset($info['MOD']['#']['CRITERIA']['0']['#']['CRITERION']) and is_array($info['MOD']['#']['CRITERIA']['0']['#']['CRITERION'])){
            $xmlbase = $info['MOD']['#']['CRITERIA']['0']['#']['CRITERION'];
            // ensures project qualifier set is empty
            delete_records('techproject_criterion', 'projectid', $newProjectId);
            foreach(array_values($xmlbase) as $anXmlCriterion){
                $record->projectid = $newProjectId;
                $record->criterion = backup_todb($anXmlCriterion['#']['CRITERION']['0']['#']);
                $record->label = backup_todb($anXmlCriterion['#']['LABEL']['0']['#']);
                $record->weight = backup_todb($anXmlCriterion['#']['WEIGHT']['0']['#']);
                $record->isfree = backup_todb($anXmlCriterion['#']['ISFREE']['0']['#']);
                $returnid = insert_record('techproject_criterion', $record);
                backup_putid($restore->backup_unique_code, 'techproject_criterion', backup_todb($anXmlCriterion['#']['ID']['0']['#']), $returnid);
                $freetxt = ($record->isfree) ? 'free ' : '' ;
                if ($CFG->debug > 5) echo "inserting {$freetxt}criterion '{$record->criterion}'<br/>";
            }
        }
    }

    /**
    * This function restores criteria
    * NOTE : must be called after restore_criteria (foreign key rebinds) and entities
    *
    */
    function techproject_restore_assessments(&$info, &$restore, $newProjectId){
        global $CFG;
        
        // ensures there where records for that
        if (@isset($info['MOD']['#']['ASSESSMENTS']['0']['#']['GRADE']) and is_array($info['MOD']['#']['ASSESSMENTS']['0']['#']['GRADE'])){
            $xmlbase = $info['MOD']['#']['ASSESSMENTS']['0']['#']['GRADE'];
            // ensures project qualifier set is empty
            delete_records('techproject_assessment', 'projectid', $newProjectId);
            foreach(array_values($xmlbase) as $anXmlGrade){
                $record->projectid = $newProjectId;
                $record->groupid = backup_get_new_id($restore->backup_unique_code, 'groups', $anXmlGrade['#']['GROUP']['0']['#']);
                $record->userid = backup_get_new_id($restore->backup_unique_code, 'user', $anXmlGrade['#']['USER']['0']['#']);
                $record->itemclass = backup_todb($anXmlGrade['#']['ITEMCLASS']['0']['#']);
                $record->item = backup_get_new_id($restore->backup_unique_code, "techproject_{$record->itemclass}", $anXmlGrade['#']['ITEM']['0']['#']);
                $record->criterion = backup_get_new_id($restore->backup_unique_code, 'techproject_criterion', $anXmlGrade['#']['CRITERION']['0']['#']);
                $record->grade = backup_todb($anXmlGrade['#']['GRADE']['0']['#']);
                $returnid = insert_record('techproject_assessment', $record);
                if ($CFG->debug > 5) echo "inserting grade for user '{$record->userid}' for item '{$record->item}'<br/>";
            }
        }
    }

    /**
    * Restores headings
    *
    */
    function techproject_restore_heading(&$info, &$restore, $newProjectId, $default = false){
        global $CFG;
        
        if ($default){
            // ensures project heading set is empty
            delete_records("techproject_heading", 'projectid', $newProjectId, 'groupid', 0);
            $aHeadingSet = $info['MOD']['#']['DEFAULTGROUP']['0']['#']['HEADING']['0']['#'];
            // $record->id = 0; // new record
            $record->projectid = $newProjectId;
            $record->groupid = 0;
            $record->title = backup_todb($aHeadingSet['TITLE']['0']['#']);
            $record->abstract = backup_todb($aHeadingSet['ABSTRACT']['0']['#']);
            $record->rationale = backup_todb($aHeadingSet['RATIONALE']['0']['#']);
            $record->environment = backup_todb($aHeadingSet['ENVIRONMENT']['0']['#']);
            $record->organisation = backup_todb($aHeadingSet['ORGANISATION']['0']['#']);
            $record->department = backup_todb($aHeadingSet['DEPARTMENT']['0']['#']);
            $returnid = insert_record('techproject_heading', $record);
            if ($CFG->debug > 5) echo "inserting default heading : $record->title (as $returnid)<br/>";
        } else {
            $oldGroups = array();
            $xmlgroupbases = @array_values($info['MOD']['#']['GROUP']);
            if (!empty($xmlgroupbases)){
                $entityIds = array();
                // ensures project heading set is empty by deleting all other records than default
                delete_records_select("techproject_heading", "projectid = $newProjectId AND groupid != 0");
                foreach(array_values($xmlgroupbases) as $aGroupBase){
                    $groupOldId = $aGroupBase['#']['ID']['0']['#'];
                    $oldGroups[] = $groupOldId;
                    $groupid = backup_get_new_id($restore->backup_unique_code, 'groups', $groupOldId);
                    if (array_key_exists('HEADING', $aGroupBase['#'])){
                        $aHeadingSet = $aGroupBase['#']['HEADING']['0']['#'];
                        // $record->id = 0; // new record
                        $record->projectid = $newProjectId;
                        $record->groupid = $groupid;
                        $record->title = backup_todb($aHeadingSet['TITLE']['0']['#']);
                        $record->abstract = backup_todb($aHeadingSet['ABSTRACT']['0']['#']);
                        $record->rationale = backup_todb($aHeadingSet['RATIONALE']['0']['#']);
                        $record->environment = backup_todb($aHeadingSet['ENVIRONMENT']['0']['#']);
                        $record->organisation = backup_todb($aHeadingSet['ORGANISATION']['0']['#']);
                        $record->department = backup_todb($aHeadingSet['DEPARTMENT']['0']['#']);
                        $returnid = insert_record('techproject_heading', $record);
                        if ($CFG->debug > 5) echo "inserting heading : $record->title (as $returnid)<br/>";
                    }
                }
            }
            return $oldGroups;
        }
        return false;
    }

    /**
    * Restores an entity
    * Entity can be missing if there where no data
    */
    function techproject_restore_entity(&$info, &$restore, $entity, $newProjectId, $default = false, $fieldset){
        // ensures project entity is empty
        if ($default){ // restore default project only
            if (array_key_exists(strtoupper($entity).'S', $info['MOD']['#']['DEFAULTGROUP']['0']['#'])){
                delete_records("techproject_{$entity}", 'projectid', $newProjectId, 'groupid', 0);
                $xmlbase = array_values($info['MOD']['#']['DEFAULTGROUP']['0']['#'][strtoupper($entity).'S']['0']['#'][strtoupper($entity)]);
                techproject_restore_entity_node($xmlbase, $restore, $entity, $newProjectId, 0, 0, $fieldset);
            }
        } else { // restore all groups
            delete_records_select("techproject_{$entity}", "projectid = $newProjectId AND groupid != 0");
            $xmlgroupbases = @array_values($info['MOD']['#']['GROUP']);
            if (!empty($xmlgroupbases)){
                foreach(array_values($xmlgroupbases) as $aGroupBase){
                    if (array_key_exists(strtoupper($entity).'S', $aGroupBase['#'])){
                        $groupOldId = $aGroupBase['#']['ID']['0']['#'];
                        $groupid = backup_get_new_id($restore->backup_unique_code, 'groups', $groupOldId);
                        $xmlbase = array_values($aGroupBase['#'][strtoupper($entity).'S']['0']['#'][strtoupper($entity)]);
                        techproject_restore_entity_node($xmlbase, $restore, $entity, $newProjectId, $groupid, 0, $fieldset);
                    }
                }
            }
        }
    }
    
    /**
    * recursive function to restore a single entity node, and subentities
    *
    */
    function techproject_restore_entity_node(&$xmlbase, &$restore, $entity, $newProjectId, $groupid, $parentId, $fieldset){
        global $CFG;
        
        foreach(array_values($xmlbase) as $anXmlEntity){
            // $record->id = 0; // new record
            $anEntityRecord->projectid = $newProjectId;
            $anEntityRecord->groupid = $groupid;
            $anEntityRecord->fatherid = $parentId;
            $anEntityRecord->ordering = backup_todb($anXmlEntity['#']['ORDERING']['0']['#']);
            $anEntityRecord->userid = backup_todb($anXmlEntity['#']['USERID']['0']['#']);
            $anEntityRecord->created = backup_todb($anXmlEntity['#']['CREATED']['0']['#']);
            $anEntityRecord->modified = backup_todb($anXmlEntity['#']['MODIFIED']['0']['#']);
            $anEntityRecord->lastuserid = backup_todb($anXmlEntity['#']['LASTUSERID']['0']['#']);
            $oldEntityId = $anXmlEntity['#']['ID']['0']['#'];

            // get entity proper fields
            $fields = explode(",", $fieldset);
            foreach($fields as $aField){
                if (empty($aField)) continue; // protects known bug
                // special case for foreign keys in entities
                if ($aField == 'milestoneid'){
                    $anEntityRecord->milestoneid = backup_get_new_id($restore->backup_unique_code,'techproject_milestone', $anXmlEntity['#']['MILESTONEID']['0']['#']);
                } else {
                    $anEntityRecord->{$aField} = backup_todb($anXmlEntity['#'][strtoupper($aField)]['0']['#']);
                }
            }
            $returnid = insert_record("techproject_{$entity}", $anEntityRecord);
            if ($CFG->debug > 5) echo "inserting $entity($oldEntityId)($returnid) child of $parentId (as $returnid):<br>";
            // stores into array for future id conversions
            backup_putid($restore->backup_unique_code, "techproject_{$entity}", backup_todb($anXmlEntity['#']['ID']['0']['#']), $returnid);

            // check for sub-entities
            if (isset($anXmlEntity['#']) and is_array($anXmlEntity['#']) and array_key_exists(strtoupper($entity), $anXmlEntity['#']) and is_array($anXmlEntity['#'][strtoupper($entity)])){
                $subxmlbase = array_values($anXmlEntity['#'][strtoupper($entity)]);
                techproject_restore_entity_node($subxmlbase, $restore, $entity, $newProjectId, $groupid, $returnid, $fieldset);
            }
        }
    }        

    // Restores an association
    function techproject_restore_association(&$info, &$restore, $association, $newProjectId, $default = false){
        global $CFG;
        global $SITE;

        if ($default){
            if (@isset($info['MOD']['#']['DEFAULTGROUP']['0']['#'][strtoupper($association)]['0']['#']['MAP'])){
                $xmlassocbase = $info['MOD']['#']['DEFAULTGROUP']['0']['#'][strtoupper($association)]['0']['#'];
                $key1 = $xmlassocbase['KEY1']['0']['#'];
                $key2 = $xmlassocbase['KEY2']['0']['#'];
                $xmlmapbase = $info['MOD']['#']['DEFAULTGROUP']['0']['#'][strtoupper($association)]['0']['#']['MAP'];
                foreach(array_values($xmlmapbase) as $aMapBase){
                    unset($record);
                    $record->projectid = $newProjectId;
                    $record->groupid = 0;
                    $value1 = $aMapBase['#']['FROM']['0']['#'];
                    $value2 = $aMapBase['#']['TO']['0']['#'];
                    $record->{$key1} = backup_get_new_id($restore->backup_unique_code, $SITE->TECHPROJECT_ASSOC_TABLES[$key1], $value1);
                    $record->{$key2} = backup_get_new_id($restore->backup_unique_code, $SITE->TECHPROJECT_ASSOC_TABLES[$key2], $value2);
                    $returnid = insert_record("techproject_{$association}", $record);
                    if ($CFG->debug > 5) echo "inserting default $association({$value1}=>{$value2})(".$record->{$key1}.",".$record->{$key2}.") mapping (as $returnid)<br/>";
                }
            }
        } else {
            // ensures project entity is empty
            delete_records("techproject_{$association}", 'projectid', $newProjectId, 'groupid', 0);
            $xmlgroupbases = @array_values($info['MOD']['#']['GROUP']);
            if (!empty($xmlgroupbases)){
                foreach(array_values($xmlgroupbases) as $aGroupBase){
                    $xmlassocbase = $aGroupBase['#'][strtoupper($association)]['0']['#'];
                    $key1 = $xmlassocbase['KEY1']['0']['#'];
                    $key2 = $xmlassocbase['KEY2']['0']['#'];
                    $groupOldId = $aGroupBase['#']['ID']['0']['#'];
                    $groupid = backup_get_new_id($restore->backup_unique_code, 'groups', $groupOldId);
                    if (@isset($aGroupBase['#'][strtoupper($association)]['0']['#']['MAP'])){
                        $xmlmapbase = $aGroupBase['#'][strtoupper($association)]['0']['#']['MAP'];
                        foreach(array_values($xmlmapbase) as $aMapBase){
                            unset($record);
                            $record->projectid = $newProjectId;
                            $record->groupid = $groupid;
                            $value1 = $aMapBase['#']['FROM']['0']['#'];
                            $value2 = $aMapBase['#']['TO']['0']['#'];
                            $record->{$key1} = backup_get_new_id($restore->backup_unique_code, $SITE->TECHPROJECT_ASSOC_TABLES[$key1], $value1);
                            $record->{$key2} = backup_get_new_id($restore->backup_unique_code, $SITE->TECHPROJECT_ASSOC_TABLES[$key2], $value2);
                            $returnid = insert_record("techproject_{$association}", $record);
                            if ($CFG->debug > 5) echo "inserting $association({$value1}=>{$value2})(".$record->{$key1}.",".$record->{$key2}.") mapping (as $returnid)<br/>";
                        }
                    }
                }
            }
        }
    }
        
    //This function copies the techproject related info from backup temp dir to course moddata folder,
    //creating it if needed and recoding everything (techproject id and user id) 
    function techproject_restore_files ($oldassid, $newassid, $olduserid, $newuserid, &$restore) {

        global $CFG;

        $status = true;
        $todo = false;
        $moddata_path = "";
        $techproject_path = "";
        $temp_path = "";

        //First, we check to "course_id" exists and create is as necessary
        //in CFG->dataroot
        $dest_dir = $CFG->dataroot."/".$restore->course_id;
        $status = check_dir_exists($dest_dir,true);

        //Now, locate course's moddata directory
        $moddata_path = $CFG->dataroot."/".$restore->course_id."/".$CFG->moddata;
   
        //Check it exists and create it
        $status = check_dir_exists($moddata_path,true);

        //Now, locate assignment directory
        if ($status) {
            $assignment_path = $moddata_path."/techproject";
            //Check it exists and create it
            $status = check_dir_exists($techproject_path,true);
        }

        //Now locate the temp dir we are gong to restore
        if ($status) {
            $temp_path = $CFG->dataroot."/temp/backup/".$restore->backup_unique_code.
                         "/moddata/techproject/".$oldassid."/".$olduserid;
            //Check it exists
            if (is_dir($temp_path)) {
                $todo = true;
            }
        }

        //If todo, we create the neccesary dirs in course moddata/techproject
        if ($status and $todo) {
            //First this assignment id
            $this_techproject_path = $techproject_path."/".$newassid;
            $status = check_dir_exists($this_techproject_path,true);
            //Now this user id
            $user_techproject_path = $this_techproject_path."/".$newuserid;
            //And now, copy temp_path to user_techproject_path
            $status = backup_copy_file($temp_path, $user_techproject_path); 
        }
       
        return $status;
    }

    //Return a content decoded to support interactivities linking. Every module
    //should have its own. They are called automatically from
    //techproject_decode_content_links_caller() function in each module
    //in the restore process
    function techproject_decode_content_links ($content, $restore) {
        global $CFG;
            
        $result = $content;
                
        //Link to the list of assignments
                
        $searchstring='/\$@(TECHPROJECTINDEX)\*([0-9]+)@\$/';
        //We look for it
        preg_match_all($searchstring,$content,$foundset);
        //If found, then we are going to look for its new id (in backup tables)
        if ($foundset[0]) {
            //print_object($foundset);                                     //Debug
            //Iterate over foundset[2]. They are the old_ids
            foreach($foundset[2] as $old_id) {
                //We get the needed variables here (course id)
                $recId = backup_get_new_id($restore->backup_unique_code,'course', $old_id);
                //Personalize the searchstring
                $searchstring='/\$@(TECHPROJECTINDEX)\*('.$old_id.')@\$/';
                //If it is a link to this course, update the link to its new location
                if($recId) {
                    //Now replace it
                    $result= preg_replace($searchstring,$CFG->wwwroot.'/mod/techproject/index.php?id='.$recId, $result);
                } else { 
                    //It's a foreign link so leave it as original
                    $result= preg_replace($searchstring,$restore->original_wwwroot.'/mod/techproject/index.php?id='.$old_id,$result);
                }
            }
        }

        //Link to assignment view by moduleid

        $searchstring='/\$@(TECHPROJECTVIEWBYID)\*([0-9]+)@\$/';
        //We look for it
        preg_match_all($searchstring,$result,$foundset);
        //If found, then we are going to look for its new id (in backup tables)
        if ($foundset[0]) {
            //print_object($foundset);                                     //Debug
            //Iterate over foundset[2]. They are the old_ids
            foreach($foundset[2] as $old_id) {
                //We get the needed variables here (course_modules id)
                $recId = backup_get_new_id($restore->backup_unique_code,"course_modules",$old_id);
                //Personalize the searchstring
                $searchstring='/\$@(TECHPROJECTVIEWBYID)\*('.$old_id.')@\$/';
                //If it is a link to this course, update the link to its new location
                if($recId) {
                    //Now replace it
                    $result= preg_replace($searchstring,$CFG->wwwroot.'/mod/techproject/view.php?id='.$recId,$result);
                } else {
                    //It's a foreign link so leave it as original
                    $result= preg_replace($searchstring,$restore->original_wwwroot.'/mod/techproject/view.php?id='.$old_id,$result);
                }
            }
        }

        return $result;
    }

    //This function makes all the necessary calls to xxxx_decode_content_links()
    //function in each module, passing them the desired contents to be decoded
    //from backup format to destination site/course in order to mantain inter-activities
    //working in the backup/restore process. It's called from restore_decode_content_links()
    //function in restore process
    function techproject_decode_content_links_caller($restore) {
        global $CFG;
        $status = true;

        $query = "
            SELECT 
                a.id, 
                a.description
            FROM 
                {$CFG->prefix}techproject a
            WHERE 
                a.course = $restore->course_id
        ";
        if ($techprojects = get_records_sql($query)) {
            //Iterate over each assignment->description
            $i = 0;   //Counter to send some output to the browser to avoid timeouts
            foreach ($techprojects as $aProject) {
                //Increment counter
                $i++;
                $content = $aProject->description;
                $result = restore_decode_content_links_worker($content, $restore);
                if ($result != $content) {
                    //Update record
                    $aProject->description = addslashes($result);
                    $status = update_record('techproject',$aProject);
                    if (debugging()) {
                        if (!defined('RESTORE_SILENTLY')) {
                            echo '<br /><hr />'.s($content).'<br />changed to<br />'.s($result).'<hr /><br />';
                        }
                    }
                }
                //Do some output
                if (($i+1) % 5 == 0) {
                    if (!defined('RESTORE_SILENTLY')) {
                        echo ".";
                        if (($i+1) % 100 == 0) {
                            echo "<br />";
                        }
                    }
                    backup_flush(300);
                }
            }
        }
        return $status;
    }

    //This function returns a log record with all the necessay transformations
    //done. It's used by restore_log_module() to restore modules log.
    function techproject_restore_logs($restore,$log) {
        $status = false;

        // we get the group that commes in all urls
        preg_match('/group=(\d+)/', $log->url, $matches);
        $oldGroup = $matches[1];
        $groupid = ($oldGroup) ? backup_get_new_id($restore->backup_unique_code,'groups',$oldGroup) : 0 ;
                    
        //Depending of the action, we recode different things
        switch ($log->action) {
        case "changerequirement":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $modId = backup_get_new_id($restore->backup_unique_code,$log->module,$log->info);
                if ($modId) {
                    $log->url = "view.php?id=".$log->cmid."&view=requirements&group=$groupid";
                    $log->info = $modId;
                    $status = true;
                }
            }
            break;
        case "changespecification":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $modId = backup_get_new_id($restore->backup_unique_code,$log->module,$log->info);
                if ($modId) {
                    $log->url = "view.php?id=".$log->cmid."&view=specifications&group=$groupid";
                    $log->info = $modId;
                    $status = true;
                }
            }
            break;
        case "changetask":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $modId = backup_get_new_id($restore->backup_unique_code,$log->module,$log->info);
                if ($modId) {
                    $log->url = "view.php?id=".$log->cmid."&view=tasks&group=$groupid";
                    $log->info = $modId;
                    $status = true;
                }
            }
            break;
        case "changemilestone":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $modId = backup_get_new_id($restore->backup_unique_code,$log->module,$log->info);
                if ($modId) {
                    $log->url = "view.php?id=".$log->cmid."&view=milestones&group=$groupid";
                    $log->info = $modId;
                    $status = true;
                }
            }
            break;
        case "changedeliverable":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $modId = backup_get_new_id($restore->backup_unique_code,$log->module,$log->info);
                if ($modId) {
                    $log->url = "view.php?id=".$log->cmid."&view=deliverables&group={$groupid}";
                    $log->info = $modId;
                    $status = true;
                }
            }
            break;
        case "submit":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $modId = backup_get_new_id($restore->backup_unique_code,$log->module,$log->info);
                if ($modId) {
                    // we get the original deliverable that the file was linked to
                    preg_match('/objectId=(\d+)/', $log->url, $matches);
                    $oldDeliverable = $matches[1];
                    $newDeliverable = backup_get_new_id($restore->backup_unique_code,'groups',$oldDeliverable);
                    $log->url = "view.php?id=".$modId."&view=view_detail&objectId={$newDeliverable}&objectClass=deliverable&group={$groupid}";
                    $log->info = $modId;
                    $status = true;
                }
            }
            break;
        case "grade":
            if ($log->cmid) {
                //Extract the techproject id from the url field                             
                $assid = substr(strrchr($log->url,"="),1);
                //Get the new_id of the module (to recode the info field)
                $modId = backup_get_new_id($restore->backup_unique_code,$log->module,$assid);
                if ($modId) {
                    $log->url = "view.php?id=".$modId."&view=summary&group={$groupid}";
                    $status = true;
                }
            }
            break;
        default:
            if (!defined('RESTORE_SILENTLY')) {
                echo "action (".$log->module."-".$log->action.") unknown. Not restored<br />";                 //Debug
            }
            break;
        }

        if ($status) {
            $status = $log;
        }
        return $status;
    }
?>
