<?PHP // $Id: index.php,v 1.1 2012-07-05 21:18:43 vf Exp $

    /**
    * Project : Technical Project Manager (IEEE like)
    *
    * This page lists all the instances of project in a particular course.
    *
    * @package mod-techproject
    * @subpackage framework
    * @category mod
    * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
    * @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
    * @date 2008/03/03
    * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
    */

    /**
    * Requires and includes
    */
    require_once("../../config.php");
    require_once($CFG->dirroot.'/mod/techproject/locallib.php');

/// Get context information

    $id = required_param('id', PARAM_INT);   // course id

    if (!$course = $DB->get_record('course', array('id' => $id))) {
        error("Course ID is incorrect");
    }

    require_login($course->id);

    add_to_log($course->id, 'techproject', 'view all', "index.php?id=$course->id", "");


/// Get all required strings

    $strprojects = get_string('modulenameplural', 'techproject');
    $strproject  = get_string('modulename', 'techproject');


/// Print the header

    if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id={$course->id}\">$course->shortname</a> ->";
    }

    $PAGE->set_title("$course->shortname: $strprojects");
    $PAGE->set_heading("$course->fullname");
    $PAGE->set_focuscontrol("");
    $PAGE->set_cacheable(true);
    $PAGE->set_button("");
    $PAGE->set_headingmenu(navmenu($course));
    echo $OUTPUT->header();

/// Get all the appropriate data

    if (! $projects = get_all_instances_in_course('techproject', $course)) {
        notice("There are no projects", "../../course/view.php?id=$course->id");
        die;
    }

/// Print the list of instances (your module will probably extend this)

    $timenow = time();
    $strname  = get_string('name');
    $strgrade  = get_string('grade');
    $strprojectend = get_string('projectend', 'techproject');
    $strweek  = get_string('week');
    $strtopic  = get_string('topic');
    $table = new stdClass;

    if ($course->format == "weeks") {
        $table->head  = array ($strweek, $strname, $strgrade, $strprojectend);
        $table->align = array ('center', 'left', 'center', 'center');
    } else if ($course->format == "topics") {
        $table->head  = array ($strtopic, $strname, $strgrade, $strprojectend);
        $table->align = array ('center', 'left', 'center', 'center');
    } else {
        $table->head  = array ($strname, $strgrade, $strprojectend);
        $table->align = array ('left', 'center', 'center');
    }

    foreach ($projects as $project) {
        if (!$project->visible) {
            //Show dimmed if the mod is hidden
            $link = "<a class=\"dimmed\" href=\"view.php?id=$project->coursemodule\">".format_string($project->name,true)."</a>";
        } else {
            //Show normal if the mod is visible
            $link = "<a href=\"view.php?id={$project->coursemodule}\">".format_string($project->name,true)."</a>";
        }

        if ($project->projectend > $timenow) {
            $due = userdate($project->projectend);
        } else {
            $due = "<font color=\"red\">".userdate($project->projectend)."</font>";
        }

        if ($course->format == 'weeks' or $course->format == 'topics') {
            if (isteacher($course->id)) {
                $grade_value = @$project->grade;
            } else {
                // it's a student, show their mean or maximum grade
                if ($project->usemaxgrade) {
                    $grade = $DB->get_record_sql("SELECT MAX(grade) as grade FROM {techproject_grades}
                            WHERE projectid = $project->id AND userid = $USER->id GROUP BY userid");
                } else {
                    $grade = $DB->get_record_sql("SELECT AVG(grade) as grade FROM {techproject_grades}
                            WHERE projectid = $project->id AND userid = $USER->id GROUP BY userid");
                }
                if ($grade) {
                    // grades are stored as percentages
                    $grade_value = number_format($grade->grade * $project->grade / 100, 1);
                } else {
                    $grade_value = 0;
                }
            }
            $table->data[] = array ($project->section, $link, $grade_value, $due);
        } else {
            $table->data[] = array ($link, $grade_value, $due);
        }
    }

    echo "<br />";

    echo html_writer::table($table);

/// Finish the page

    echo $OUTPUT->footer($course);

?>
