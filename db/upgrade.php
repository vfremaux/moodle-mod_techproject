<?php  //$Id: upgrade.php,v 1.4 2012-12-03 18:38:54 vf Exp $

/**
* This file keeps track of upgrades to 
* the techproject module
*
* Sometimes, changes between versions involve
* alterations to database structures and other
* major things that may break installations.
*
* The commands in here will all be database-neutral,
* using the functions defined in lib/ddllib.php
*
* @package mod-techproject
* @subpackage framework
* @category mod
* @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
* @date 2008/03/03
* @version phase1
* @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
*/

/**
* The upgrade function in this file will attempt
* to perform all the necessary actions to upgrade
* your older installtion to the current version.
*
* If there's something it cannot do itself, it
* will tell you what you need to do.
*/
function xmldb_techproject_upgrade($oldversion=0) {

    global $CFG, $THEME, $db;

    $result = true;

/// And upgrade begins here. For each one, you'll need one 
/// block of code similar to the next one. Please, delete 
/// this comment lines once this file start handling proper
/// upgrade code.

    if ($result && $oldversion < 2007081100) { //New version in version.php
        $result = $result && modify_database("", "ALTER TABLE `mdl_techproject` DROP `studentscanchangerequs` ; ");
        $result = $result && modify_database("", "ALTER TABLE `mdl_techproject` DROP `studentscanchangespecs` ; ");
        $result = $result && modify_database("", "ALTER TABLE `mdl_techproject` DROP `studentscanchangetasks` ; ");
        $result = $result && modify_database("", "ALTER TABLE `mdl_techproject` DROP `studentscanchangemiles` ; ");
        $result = $result && modify_database("", "ALTER TABLE `mdl_techproject` DROP `studentscanchangedeliv` ; ");
        $result = $result && modify_database("", "ALTER TABLE `mdl_techproject_assessment_criterion` RENAME `mdl_techproject_criterion` ; ");
        $result = $result && modify_database("", "ALTER TABLE `mdl_techproject_deliverable_status` RENAME `mdl_techproject_deliv_status` ; ");
    }

    if ($result && $oldversion < 2008030300) {

    /// Define field xslfilter to be added to techproject
        $table = new XMLDBTable('techproject');
        $field = new XMLDBField('xslfilter');
        $field->setAttributes(XMLDB_TYPE_CHAR, '32', null, null, null, null, null, null, 'enablecvs');

    /// Launch add field xslfilter
        $result = $result && add_field($table, $field);

    /// Define field cssfilter to be added to techproject
        $table = new XMLDBTable('techproject');
        $field = new XMLDBField('cssfilter');
        $field->setAttributes(XMLDB_TYPE_CHAR, '32', null, null, null, null, null, null, 'xslfilter');

    /// Launch add field cssfilter
        $result = $result && add_field($table, $field);
    }

    if ($result && $oldversion < 2008072100) {
    
    /// Define table techproject_collapse to be created
        $table = new XMLDBTable('techproject_collapse');

    /// Adding fields to table techproject_collapse
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('projectid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('entity', XMLDB_TYPE_CHAR, '24', null, null, null, null, null, null);
        $table->addFieldInfo('entryid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('userid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('collapsed', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '1');

    /// Adding keys to table techproject_collapse
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Launch create table for techproject_collapse
        $result = $result && create_table($table);
    }

    if ($result && $oldversion < 2009011000) {

    /// Changing the default of field owner on table techproject_task to 0
        $table = new XMLDBTable('techproject_task');
        $field = new XMLDBField('owner');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'ordering');

    /// Launch change of default for field owner
        $result = $result && change_field_default($table, $field);
    }

    if ($result && $oldversion < 2009011800) {

    /// Define field spent to be added to techproject_task
        $table = new XMLDBTable('techproject_task');

        $field = new XMLDBField('costrate');
        $field->setAttributes(XMLDB_TYPE_NUMBER, '10, 2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'status');
        $result = $result && change_field_type($table, $field);
        $result = $result && change_field_precision($table, $field);

        $field = new XMLDBField('used');
        $field->setAttributes(XMLDB_TYPE_NUMBER, '10, 2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'done');
        $result = $result && add_field($table, $field);

    /// Rename cost as quoted
        $field = new XMLDBField('cost');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'used');
        $result = $result && rename_field($table, $field, 'quoted');

    /// Change attrs and pass to number 10,2
        $field = new XMLDBField('quoted');
        $field->setAttributes(XMLDB_TYPE_NUMBER, '10, 2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'used');
        $result = $result && change_field_type($table, $field);
        $result = $result && change_field_precision($table, $field);    

        $field = new XMLDBField('spent');
        $field->setAttributes(XMLDB_TYPE_NUMBER, '10, 2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'quoted');
        $result = $result && add_field($table, $field);

    }
    if ($result && $oldversion < 2009011801) {

    /// Define table techproject_risk to be created
        $table = new XMLDBTable('techproject_risk');

    /// Adding fields to table techproject_risk
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('projectid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('risk', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('label', XMLDB_TYPE_CHAR, '64', null, null, null, null, null, null);
        $table->addFieldInfo('description', XMLDB_TYPE_CHAR, '255', null, null, null, null, null, null);

    /// Adding keys to table techproject_risk
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Launch create table for techproject_risk
        $result = $result && create_table($table);
    }

    if ($result && $oldversion < 2009011802) {

        $r = new StdClass;
        
        $r->projectid = 0;
        $r->risk = '0';
        $r->label = 'none';
        $r->description = 'noriskdesc';
        $risks[] = $r;
        unset($r);

        $r->projectid = 0;
        $r->risk = '10';
        $r->label = 'light';
        $r->description = 'naturalriskdesc';
        $risks[] = $r;
        unset($r);

        $r->projectid = 0;
        $r->risk = '30';
        $r->label = 'medium';
        $r->description = 'mediumriskdesc';
        $risks[] = $r;
        unset($r);

        $r->projectid = 0;
        $r->risk = '80';
        $r->label = 'high';
        $r->description = 'highriskdesc';
        $risks[] = $r;
        unset($r);
        
        foreach($risks as $risk){
            insert_record('techproject_risk', $risk);
        }

    }

    if ($result && $oldversion < 2009011803) {

    /// Define field costunit to be added to techproject
        $table = new XMLDBTable('techproject');
        $field = new XMLDBField('costunit');
        $field->setAttributes(XMLDB_TYPE_CHAR, '20', null, null, null, null, null, null, 'timeunit');

    /// Launch add field costunit
        $result = $result && add_field($table, $field);

    /// Define field useriskcorrection to be added to techproject
        $field = new XMLDBField('useriskcorrection');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'enablecvs');

    /// Launch add field useriskcorrection
        $result = $result && add_field($table, $field);
    }

    if ($result && $oldversion < 2009011804) {

    /// Define field risk to be added to techproject_task
        $table = new XMLDBTable('techproject_task');
        $field = new XMLDBField('risk');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '6', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'spent');

    /// Launch add field risk
        $result = $result && add_field($table, $field);
    }

    if ($result && $oldversion < 2010030500) {

    /// Rename field worktype on table techproject_worktype to code
        $table = new XMLDBTable('techproject_worktype');
        $field = new XMLDBField('worktype');
        $field->setAttributes(XMLDB_TYPE_CHAR, '10', null, null, null, null, null, null, 'projectid');

    /// Launch rename field code
        $result = $result && rename_field($table, $field, 'code');

    /// Rename field taskstatus on table techproject_taskstatus to code
        $table = new XMLDBTable('techproject_taskstatus');
        $field = new XMLDBField('taskstatus');
        $field->setAttributes(XMLDB_TYPE_CHAR, '10', null, null, null, null, null, null, 'projectid');

    /// Launch rename field code
        $result = $result && rename_field($table, $field, 'code');

    /// Rename field status on table techproject_worktype to code
        $table = new XMLDBTable('techproject_deliv_status');
        $field = new XMLDBField('status');
        $field->setAttributes(XMLDB_TYPE_CHAR, '10', null, null, null, null, null, null, 'projectid');

    /// Launch rename field code
        $result = $result && rename_field($table, $field, 'code');

    /// Rename field risk on table techproject_risk to code
        $table = new XMLDBTable('techproject_risk');
        $field = new XMLDBField('risk');
        $field->setAttributes(XMLDB_TYPE_CHAR, '10', null, null, null, null, null, null, 'projectid');

    /// Launch rename field code
        $result = $result && rename_field($table, $field, 'code');

    /// Rename field priority on table techproject_priority to code
        $table = new XMLDBTable('techproject_priority');
        $field = new XMLDBField('priority');
        $field->setAttributes(XMLDB_TYPE_CHAR, '10', null, null, null, null, null, null, 'projectid');

    /// Launch rename field code
        $result = $result && rename_field($table, $field, 'code');

    /// Rename field complexity on table techproject_complexity to code
        $table = new XMLDBTable('techproject_complexity');
        $field = new XMLDBField('complexity');
        $field->setAttributes(XMLDB_TYPE_CHAR, '10', null, null, null, null, null, null, 'projectid');

    /// Launch rename field code
        $result = $result && rename_field($table, $field, 'code');

    /// Rename field severity on table techproject_severity to code
        $table = new XMLDBTable('techproject_severity');
        $field = new XMLDBField('severity');
        $field->setAttributes(XMLDB_TYPE_CHAR, '10', null, null, null, null, null, null, 'projectid');

    /// Launch rename field code
        $result = $result && rename_field($table, $field, 'code');
    }

    if ($result && $oldversion < 2010030502) {

    /// Rename field strengh on table techproject_strengh to code
        $table = new XMLDBTable('techproject_strength');
        $field = new XMLDBField('strength');
        $field->setAttributes(XMLDB_TYPE_CHAR, '10', null, null, null, null, null, null, 'projectid');

    /// Launch rename field code
        $result = $result && rename_field($table, $field, 'code');
    }

    if ($result && $oldversion < 2010030503) {

    /// Define table techproject_qualifier to be created
        $table = new XMLDBTable('techproject_qualifier');

    /// Adding fields to table techproject_qualifier
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('projectid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('domain', XMLDB_TYPE_CHAR, '45', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('code', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('label', XMLDB_TYPE_CHAR, '45', null, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('description', XMLDB_TYPE_TEXT, 'small', null, XMLDB_NOTNULL, null, null, null, null);

    /// Adding keys to table techproject_qualifier
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Launch create table for techproject_qualifier
        $result = $result && create_table($table);

    /// finally reloads table
        $record[] = (object)array('domain' => 'complexity', 'code' => '*', 'label' => 'evident', 'description' => 'evidentdesc');
        $record[] = (object)array('domain' => 'complexity', 'code' => '**', 'label' => 'simple', 'description' => 'simpledesc');
        $record[] = (object)array('domain' => 'complexity', 'code' => '***', 'label' => 'medium', 'description' => 'mediumspec');
        $record[] = (object)array('domain' => 'complexity', 'code' => '****', 'label' => 'hard', 'description' => 'hardspec');
        $record[] = (object)array('domain' => 'complexity', 'code' => '*****', 'label' => 'verycomplex', 'description' => 'verycomplexdesc');

        $record[] = (object)array('domain' => 'delivstatus', 'code' => 'CRE', 'label' => 'created', 'description' => 'createddesc');
        $record[] = (object)array('domain' => 'delivstatus', 'code' => 'TST', 'label' => 'test', 'description' => 'testdesc');
        $record[] = (object)array('domain' => 'delivstatus', 'code' => 'PCK', 'label' => 'packaging', 'description' => 'packagingdesc');
        $record[] = (object)array('domain' => 'delivstatus', 'code' => 'BET', 'label' => 'beta', 'description' => 'betadesc');
        $record[] = (object)array('domain' => 'delivstatus', 'code' => 'REV', 'label' => 'review', 'description' => 'reviewdesc');
        $record[] = (object)array('domain' => 'delivstatus', 'code' => 'STB', 'label' => 'stable',  'description' => 'stabledesc');
        $record[] = (object)array('domain' => 'delivstatus', 'code' => 'FIX', 'label' => 'fix', 'description' => 'fixdesc');
        $record[] = (object)array('domain' => 'delivstatus', 'code' => 'OBS', 'label' => 'obsolete', 'description' => 'obsoletedesc');
        $record[] = (object)array('domain' => 'delivstatus', 'code' => '', 'label' => 'unassigned', 'description' => 'unassigneddesc');

        $record[] = (object)array('domain' => 'priority', 'code' => '1', 'label' => 'canwait', 'description' => 'canwaitdesc');
        $record[] = (object)array('domain' => 'priority', 'code' => '2', 'label' => 'notprioritary', 'description' => 'notprioritarydesc');
        $record[] = (object)array('domain' => 'priority', 'code' => '3', 'label' => 'asap', 'description' => 'asapdesc');
        $record[] = (object)array('domain' => 'priority', 'code' => '4', 'label' => 'urgent', 'description' => 'urgentdesc');
        $record[] = (object)array('domain' => 'priority', 'code' => '5', 'label' => 'prioritary', 'description' => 'prioritarydesc');

        $record[] = (object)array('domain' => 'severity', 'code' => '*', 'label' => 'goodie', 'description' => 'goodiedesc');
        $record[] = (object)array('domain' => 'severity', 'code' => '**', 'label' => 'optional', 'description' => 'optionaldesc');
        $record[] = (object)array('domain' => 'severity', 'code' => '***', 'label' => 'useful', 'description' => 'usefuldesc');
        $record[] = (object)array('domain' => 'severity', 'code' => '****', 'label' => 'essential', 'description' => 'essentialdesc');
        $record[] = (object)array('domain' => 'severity', 'code' => '*****', 'label' => 'mandatory' , 'description' => 'mandatorydesc');

        $record[] = (object)array('domain' => 'strength', 'code' => '*', 'label' => 'plus', 'description' => 'plusdesc');
        $record[] = (object)array('domain' => 'strength', 'code' => '**', 'label' => 'implicit', 'description' => 'implicitdesc');
        $record[] = (object)array('domain' => 'strength', 'code' => '***', 'label' => 'wished', 'description' => 'wisheddesc');
        $record[] = (object)array('domain' => 'strength', 'code' => '****', 'label' => 'should', 'description' => 'shoulddesc');
        $record[] = (object)array('domain' => 'strength', 'code' => '*****', 'label' => 'will', 'description' => 'willdesc');
        $record[] = (object)array('domain' => 'strength', 'code' => '******', 'label' => 'must', 'description' => 'mustdesc');

        $record[] = (object)array('domain' => 'taskstatus', 'code' => 'PLANNED', 'label' => 'planned', 'description' => 'planneddesc');
        $record[] = (object)array('domain' => 'taskstatus', 'code' => 'STARTED', 'label' => 'started', 'description' => 'starteddesc');
        $record[] = (object)array('domain' => 'taskstatus', 'code' => 'BLOCKED', 'label' => 'blocked', 'description' => 'blockeddesc');
        $record[] = (object)array('domain' => 'taskstatus', 'code' => 'DELAYED', 'label' => 'delayed', 'description' => 'delayeddesc');
        $record[] = (object)array('domain' => 'taskstatus', 'code' => 'COMPLETE', 'label' => 'complete', 'description' => 'completedesc');
        $record[] = (object)array('domain' => 'taskstatus', 'code' => 'ABANDONED', 'label' => 'abandoned', 'description' => 'abandoneddesc');

        $record[] = (object)array('domain' => 'worktype', 'code' => 'PRE', 'label' => 'investigating', 'description' => 'investigatingdesc');
        $record[] = (object)array('domain' => 'worktype', 'code' => 'ANA', 'label' => 'analysing', 'description' => 'analysingdesc');
        $record[] = (object)array('domain' => 'worktype', 'code' => 'COD', 'label' => 'coding', 'description' => 'codingdesc');
        $record[] = (object)array('domain' => 'worktype', 'code' => 'REV', 'label' => 'reviewing', 'description' => 'reviewingdesc');
        $record[] = (object)array('domain' => 'worktype', 'code' => 'PAK', 'label' => 'packaging', 'description' => 'packagingdesc');
        $record[] = (object)array('domain' => 'worktype', 'code' => 'DOC', 'label' => 'documenting', 'description' => 'documentingdesc');
        $record[] = (object)array('domain' => 'worktype', 'code' => 'INS', 'label' => 'installing', 'description' => 'installingdesc');
        $record[] = (object)array('domain' => 'worktype', 'code' => 'TUN', 'label' => 'tuning', 'description' => 'tuningdesc');
        $record[] = (object)array('domain' => 'worktype', 'code' => 'CON', 'label' => 'configuring', 'description' => 'configuringdesc');
        $record[] = (object)array('domain' => 'worktype', 'code' => 'QUO', 'label' => 'quoting', 'description' => 'quotingdesc');
        $record[] = (object)array('domain' => 'worktype', 'code' => 'SPE', 'label' => 'specifying', 'description' => 'specifyingdesc');
        $record[] = (object)array('domain' => 'worktype', 'code' => 'ADM', 'label' => 'administrating', 'description' => 'administratingdesc');

        $record[] = (object)array('domain' => 'risk', 'code' => '0', 'label' => 'none', 'description' => 'nonedesc');
        $record[] = (object)array('domain' => 'risk', 'code' => '10', 'label' => 'light', 'description' => 'lightdesc');
        $record[] = (object)array('domain' => 'risk', 'code' => '30', 'label' => 'medium', 'description' => 'mediumdesc');
        $record[] = (object)array('domain' => 'risk', 'code' => '80', 'label' => 'high', 'description' => 'highdesc');

        foreach($record as $rec){                  
            $rec->projectid = 0;
            insert_record('techproject_qualifier', $rec);
        }
        
        // remap all previous entities
        $entities = array('requirement' => array('strength'),
                          'specification' => array('priority', 'complexity', 'severity'),
                          'task' => array('worktype', 'taskstatus', 'risk'),
                          'deliverable' => 'deliv_status' );
        $errors = 0;
        foreach(array_keys($entities) as $entity){
            if($recs = get_records("techproject_$entity")){
                foreach($entities[$entity] as $qualifier){
                    $qualifiername = $qualifier;
                    $qualifiernewname = $qualifier;
                    if ($qualifier == 'deliv_status'){
                        $qualifiername = 'status'; //special case.
                        $qualifiernewname = 'delivstatus'; //special case.
                    }

                    foreach($recs as $rec){
                        if ($qualrec = get_record('techproject_qualifier', 'id', $rec->$qualifiername, 'projectid', 0)){
                            if ($rec->$qualifier = get_field('techproject_qualifier', 'id', 'domain', $qualifiernewname, 'code' , $qualrec->code)){
                                update_record("techproject_$entity", $entity);
                            } else {
                                $errors = 1;
                                mtrace("error in remapping $entity for $qualifier at record id {$rec->id}");
                            }
                        }
                    }
                } 
            }
        }
        
        //finaly drop all old tables

        /// Define table techproject_complexity to be dropped
            $table = new XMLDBTable('techproject_complexity');
    
        /// Launch drop table for techproject_complexity
            $result = $result && drop_table($table);
    
        /// Define table techproject_complexity to be dropped
            $table = new XMLDBTable('techproject_severity');
    
        /// Launch drop table for techproject_severity
            $result = $result && drop_table($table);
    
        /// Define table techproject_complexity to be dropped
            $table = new XMLDBTable('techproject_priority');
    
        /// Launch drop table for techproject_priority
            $result = $result && drop_table($table);
    
        /// Define table techproject_complexity to be dropped
            $table = new XMLDBTable('techproject_strength');
    
        /// Launch drop table for techproject_strength
            $result = $result && drop_table($table);
    
        /// Define table techproject_complexity to be dropped
            $table = new XMLDBTable('techproject_worktype');
    
        /// Launch drop table for techproject_worktype
            $result = $result && drop_table($table);
    
        /// Define table techproject_complexity to be dropped
            $table = new XMLDBTable('techproject_taskstatus');
    
        /// Launch drop table for techproject_taskstatus
            $result = $result && drop_table($table);
    
        /// Define table techproject_complexity to be dropped
            $table = new XMLDBTable('techproject_risk');
    
        /// Launch drop table for techproject_risk
            $result = $result && drop_table($table);
    
        /// Define table techproject_complexity to be dropped
            $table = new XMLDBTable('techproject_deliv_status');
    
        /// Launch drop table for techproject_delivstatus
            $result = $result && drop_table($table);
    }


    if ($result && $oldversion < 2011010900) {

    /// Define field xslfilter to be added to techproject
        $table = new XMLDBTable('techproject_requirement');
        $field = new XMLDBField('heaviness');
        $field->setAttributes(XMLDB_TYPE_CHAR, '10', null, null, null, null, null, null, 'strength');

    /// Launch add field xslfilter
        $result = $result && add_field($table, $field);
    }

    if ($result && $oldversion < 2011010901) {
    	$record = array();
        $record[] = (object)array('domain' => 'heaviness', 'code' => '10000', 'label' => 'impossible', 'description' => 'impossibledesc');
        $record[] = (object)array('domain' => 'heaviness', 'code' => '100', 'label' => 'outofreason', 'description' => 'outofreasondesc');
        $record[] = (object)array('domain' => 'heaviness', 'code' => '50', 'label' => 'heavy', 'description' => 'heavydesc');
        $record[] = (object)array('domain' => 'heaviness', 'code' => '40', 'label' => 'difficult', 'description' => 'difficultdesc');
        $record[] = (object)array('domain' => 'heaviness', 'code' => '30', 'label' => 'needswork', 'description' => 'needsworkdesc');
        $record[] = (object)array('domain' => 'heaviness', 'code' => '20', 'label' => 'wecanhave', 'description' => 'wecanhavedesc');
        $record[] = (object)array('domain' => 'heaviness', 'code' => '10', 'label' => 'wehave', 'description' => 'wehavedesc');
        $record[] = (object)array('domain' => 'heaviness', 'code' => '0', 'label' => 'needsmoreinfo', 'description' => 'needsmoreinfodesc');

        foreach($record as $rec){                  
            $rec->projectid = 0;
            insert_record('techproject_qualifier', $rec);
        }
    }

    if ($result && $oldversion < 2011112000) {

    /// Define table techproject_valid_session to be created
        $table = new XMLDBTable('techproject_valid_session');

    /// Adding fields to table techproject_valid_session
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('projectid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('groupid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('datecreated', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('dateclosed', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('createdby', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('untracked', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('missing', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('buggy', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('toenhance', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('refused', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('accepted', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');

    /// Adding keys to table techproject_valid_session
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Launch create table for techproject_valid_session
        $result = $result && create_table($table);

    /// Define table techproject_valid_state to be created
        $table = new XMLDBTable('techproject_valid_state');

    /// Adding fields to table techproject_valid_state
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('projectid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('groupid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('reqid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('validatorid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('validationsessionid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('lastchangedate', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0');
        $table->addFieldInfo('status', XMLDB_TYPE_CHAR, '16', null, XMLDB_NOTNULL, null, XMLDB_ENUM, array('UNTRACKED', 'MISSING', 'REFUSED', 'BUGGY', 'TOENHANCE', 'ACCEPTED', 'REGRESSION'), 'UNTRACKED');
        $table->addFieldInfo('comment', XMLDB_TYPE_TEXT, 'small', null, null, null, null, null, null);

    /// Adding keys to table techproject_valid_state
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Launch create table for techproject_valid_state
        $result = $result && create_table($table);
    }

    if ($result && $oldversion < 2011120200) {

    /// Define field projectusesrequs to be added to techproject
        $table = new XMLDBTable('techproject');
        $field = new XMLDBField('projectusesrequs');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'useriskcorrection');

    /// Launch add field projectusesrequs
        $result = $result && add_field($table, $field);

        $field = new XMLDBField('projectusesspecs');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'projectusesrequs');

    /// Launch add field projectusesspecs
        $result = $result && add_field($table, $field);

        $field = new XMLDBField('projectusesdelivs');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'projectusesspecs');

    /// Launch add field projectusesdelivs
        $result = $result && add_field($table, $field);

        $field = new XMLDBField('projectusesvalidations');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'projectusesdelivs');

    /// Launch add field projectusesvalidations
        $result = $result && add_field($table, $field);
    }

    if ($result && $oldversion < 2012120200) {
    /// Rename field heaviness on table techproject_requirement to heavyniess
        $table = new XMLDBTable('techproject_requirement');
        $field = new XMLDBField('heaviness');
        $field->setAttributes(XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, null, null, null, 'strength');

    /// Launch rename field heaviness
        $result = $result && rename_field($table, $field, 'heavyness');
        
        $sql = "
        	UPDATE
        		{$CFG->prefix}techproject_qualifier
        	SET
        		domain = 'heavyness'
        	WHERE
        		domain = 'heaviness'
        ";
        execute_sql($sql, false);
    }

    if ($result && $oldversion < 2012120201) {
    /// Define field accesskey to be added to techproject
        $table = new XMLDBTable('techproject');
        $field = new XMLDBField('accesskey');
        $field->setAttributes(XMLDB_TYPE_CHAR, '32', null, null, null, null, null, null, 'cssfilter');

    /// Launch add field cssfilter
        $result = $result && add_field($table, $field);
	}
    return $result;
}

?>