<?php
/**
* Global Search Engine for Moodle
*
* @package search
* @category mod
* @subpackage document_wrappers
* @author Valery Fremaux [valery.fremaux@club-internet.fr] > 1.8
* @date 2008/03/31
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
*
* document handling for techproject activity module
*/

/**
* requires and includes
*/
require_once("$CFG->dirroot/search/documents/document.php");
require_once("$CFG->dirroot/mod/techproject/lib.php");

/**
* constants for document definition
*/
define('X_SEARCH_TYPE_TECHPROJECT', 'techproject');

/**
* a class for representing searchable information
*
*/
class TechprojectEntrySearchDocument extends SearchDocument {

    /**
    * constructor
    *
    */
    public function __construct(&$entry, $course_id, $context_id) {
        // generic information
        $doc->docid     = $entry['id'];
        $doc->documenttype  = X_SEARCH_TYPE_TECHPROJECT;
        $doc->itemtype      = $entry['entry_type'];
        $doc->contextid     = $context_id;

        $doc->title     = $entry['abstract'];
        $doc->author    = ($entry['userid']) ? @$entry['author'] : '';
        $doc->contents  = strip_tags($entry['description']);
        $doc->date      = '';
        $doc->url       = techproject_make_link($entry['projectid'], $entry['id'], $entry['entry_type'], $entry['groupid']);
        // module specific information
        $data->techproject = $entry['projectid'];
        parent::__construct($doc, $data, $course_id, $entry['groupid'], $entry['userid'], "mod/".X_SEARCH_TYPE_TECHPROJECT);
    }
}

/**
* constructs a valid link to a description detail
*
*/
function techproject_make_link($techproject_id, $entry_id, $entry_type, $group_id) {
    global $CFG;
    return $CFG->wwwroot.'/mod/techproject/view.php?view=view_detail&amp;id='.$techproject_id.'&amp;objectId='.$entry_id.'&amp;objectClass='.$entry_type.'&amp;group='.$group_id;
}

/**
* search standard API
*
*/
function techproject_iterator() {
    $techprojects = $DB->get_records('techproject');
    return $techprojects;    
}

/**
* search standard API
* @param techproject a techproject instance
* @return an array of collected searchable documents
*/
function techproject_get_content_for_index(&$techproject) {
    $documents = array();
    if (!$techproject) return $documents;

    $coursemodule = $DB->get_field('modules', 'id', array('name' => 'techproject'));
    if (!$cm = $DB->get_record('course_modules', array('course' => $techproject->course, 'module' => $coursemodule, 'instance' => $techproject->id))) return $documents;
    $context = context_module::instance($cm->id);

    $requirements = techproject_get_entries($techproject->id, 'requirement');
    $specifications = techproject_get_entries($techproject->id, 'specification');
    $tasks = techproject_get_tasks($techproject->id);
    $milestones = techproject_get_entries($techproject->id, 'milestone');
    $deliverables = techproject_get_entries($techproject->id, 'deliverable');

    // handle all but tasks
    $entries = @array_merge($requirements, $specifications, $milestones, $deliverables);
    if ($entries){
        foreach($entries as $anEntry) {
            if ($anEntry) {
                if (strlen($anEntry->description) > 0) {
                    $anEntry->author = '';
                    $documents[] = new TechprojectEntrySearchDocument(get_object_vars($anEntry), $techproject->course, $context->id);
                } 
            } 
        } 
    }
    // handle tasks separately
    if ($tasks){
        foreach($tasks as $aTask) {
            if ($aTask) {
                if (strlen($aTask->description) > 0) {
                    if ($aTask->assignee){
                        $user = $DB->get_record('user', array('id' => $aTask->assignee));
                        $aTask->author = $user->firstname.' '.$user->lastname;
                    }
                    $documents[] = new TechprojectEntrySearchDocument(get_object_vars($aTask), $techproject->course, $context->id);
                } 
            } 
        } 
    }
    return $documents;
}

/**
* returns a single techproject search document based on a techproject_entry id and itemtype
*
*/
function techproject_single_document($id, $itemtype) {
    switch ($itemtype){
        case 'requirement':{
            $entry = $DB->get_record('techproject_requirement', array('id' => $id));
            $entry->author = '';
            break;
        }
        case 'specification':{
            $entry = $DB->get_record('techproject_specification', array('id' => $id));
            $entry->author = '';
            break;
        }
        case 'milestone':{
            $entry = $DB->get_record('techproject_milestone', array('id' => $id));
            $entry->author = '';
            break;
        }
        case 'deliverable':{
            $entry = $DB->get_record('techproject_deliverable', array('id' => $id));
            $entry->author = '';
            break;
        }
        case 'task':{
            $entry = $DB->get_record('techproject_task', array('id' => $id));
            if ($entry->assignee){
                $user = $DB->get_record('user', array('id' => $entry->assignee));
                $entry->author = $user->firstname.' '.$user->lastname;
            }
            break;
        }
    }
    $techproject_course = $DB->get_field('techproject', 'course', array('id' => $entry->projectid));
    $coursemodule = $DB->get_field('modules', 'id', array('name' => 'techproject'));
    $cm = $DB->get_record('course_modules', array('course' => $techproject_course, 'module' => $coursemodule, 'instance' => $entry->projectid));
    $context = context_module::instance($cm->id);
    $entry->type = $itemtype;
    $techproject = $DB->get_record('techproject', array('id' => $requirement->projectid));
    return new TechprojectEntrySearchDocument(get_object_vars($anEntry), $techproject->course, $context->id);
}

/**
* dummy delete function that packs id with itemtype.
* this was here for a reason, but I can't remember it at the moment.
*
*/
function techproject_delete($info, $itemtype) {
    $object->id = $info;
    $object->itemtype = $itemtype;
    return $object;
}

/**
* returns the var names needed to build a sql query for addition/deletions
*
*/
// TODO : what should we do there ?
function techproject_db_names() {
    //[primary id], [table name], [time created field name], [time modified field name]
    return array(
        array('id', 'techproject_requirement', 'created', 'modified', 'requirement'),
        array('id', 'techproject_specification', 'created', 'modified', 'specification'),
        array('id', 'techproject_task', 'created', 'modified', 'task'),
        array('id', 'techproject_milestone', 'created', 'modified', 'milestone'),
        array('id', 'techproject_deliverable', 'created', 'modified', 'deliverable')
    );
}

/**
* get a complete list of entries of one particular type
* @param projectid the project instance
* @param type the entity type
* @return an array of records
*/
function techproject_get_entries($projectid, $type) {
    global $CFG;
    $query = "
        SELECT 
            e.id, 
            e.abstract, 
            e.description,
            e.projectid,
            e.groupid,
            e.userid,
            '$type' AS entry_type
        FROM 
            {techproject_{$type}} AS e
        WHERE 
            e.projectid = '{$projectid}'
    ";
    return $DB->get_records_sql($query);
}

/**
* get the task list for a project instance
* @param int $projectid the project
* @return an array of records that represent tasks
*/
function techproject_get_tasks($projectid) {
    global $CFG;
    $query = "
        SELECT 
            t.id, 
            t.assignee, 
            t.abstract, 
            t.description,
            t.projectid,
            t.groupid,
            t.owner as userid,
            u.firstname,
            u.lastname,
            'task' as entry_type
        FROM 
            {techproject_task} AS t
        LEFT JOIN
            {user} AS u
        ON
            t.owner = u.id            
        WHERE 
            t.projectid = '{$projectid}'
        ORDER BY 
            t.taskstart ASC
    ";
    return $DB->get_records_sql($query);
}

/**
*
*/
function techproject_search_get_objectinfo($itemtype, $this_id, $context_id = null){

    if (!$entry = $DB->get_record("techproject_{$itemtype}", array('id' => $this_id))) return false;
    if (!$techproject = $DB->get_record('techproject', array('id' => $entry->projectid))) return false;
    $techproject->entry = $entry;

    if ($context_id){
        $info->context = $DB->get_record('context', array('id' => $context_id));
        $info->cm = $DB->get_record('course_modules', array('id' => $info->context->instanceid));
    } else {
        $module = $DB->get_record('modules', array('name' => 'techproject'));
        $info->cm = $DB->get_record('course_modules', array('instance' => $techproject->id, 'module' => $module->id));
        $info->context = context_module::instance($info->cm->id);
    }
    $info->instance = $techproject;
    $info->type = 'mod';
    $info->mediatype = 'composite';
    $info->contenttype = 'html';

    return $info;
}

/**
* this function handles the access policy to contents indexed as searchable documents. If this 
* function does not exist, the search engine assumes access is allowed.
* When this point is reached, we already know that : 
* - user is legitimate in the surrounding context
* - user may be guest and guest access is allowed to the module
* - the function may perform local checks within the module information logic
* @param path the access path to the module script code
* @param entry_type the information subclassing (usefull for complex modules, defaults to 'standard')
* @param this_id the item id within the information class denoted by entry_type. In techprojects, this id 
* points to the techproject instance in which all resources are indexed.
* @param user the user record denoting the user who searches
* @param group_id the current group used by the user when searching
* @return true if access is allowed, false elsewhere
*/
function techproject_check_text_access($path, $entry_type, $this_id, $user, $group_id, $context_id){
    global $CFG;
    include_once("{$CFG->dirroot}/{$path}/lib.php");

    // get the techproject object and all related stuff
    /*
    $entry = $DB->get_record("techproject_{$entry_type}", array('id' => $this_id));
    $techproject = $DB->get_record('techproject', array('id' => $entry->projectid));
    $course = $DB->get_record('course', array('id' => $techproject->course));
    $module_context = $DB->get_record('context', array('id' => $context_id));
    $cm = $DB->get_record('course_modules', array('id' => $module_context->instanceid));
    */
    if (!$info = techproject_search_get_objectinfo($entry_type, $this_id, $context_id)) return false;
    $cm = $info->cm;
    $context = $info->context;
    $intance = $info->instance;

    $course = $DB->get_record('course', array('id' => $instance->course));
    if (!$cm->visible and !has_capability('moodle/course:viewhiddenactivities', $context)) return false;
    //group consistency check : checks the following situations about groups
    // if user is guest check access capabilities for guests :
    // guests can see default project, and other records if groups are liberal
    // TODO : change guestsallowed in a capability
    if (isguestuser() && $instance->guestsallowed){
        if ($group_id && groupmode($course, $cm) == SEPARATEGROUPS)
            return false;
        return true;
    }
    // trap if user is not same group and groups are separated
    $current_group = get_current_group($course->id);
    if ((groupmode($course) == SEPARATEGROUPS) && $group_id != $current_group && $group_id) return false;
    //trap if ungroupedsees is off in strict access mode and user is not teacher
    if ((groupmode($course) == SEPARATEGROUPS) && !$instance->ungroupedsees && !$group_id && has_capability('mod/techproject:manage', $context)) return false;
    return true;
}

/**
* this call back is called when displaying the link for some last post processing
*
*/
function techproject_link_post_processing($title){
    global $CFG;
    if ($CFG->block_search_utf8dir){
        return mb_convert_encoding($title, 'UTF-8', 'auto');
    }
    return mb_convert_encoding($title, 'auto', 'UTF-8');
}

?>