<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

// This generator may be included directly by an include call, or invoked
// through an HTTP request.

if (!defined('MOODLE_INTERNAL')) {
    require('../../../config.php');
    $projectid = required_param('projectid', PARAM_INT);    // Project id.
    $s = optional_param('s', 0, PARAM_INT);    // Start offset.
    $w = optional_param('w', 600, PARAM_INT);    // Graphic width.
    $z = optional_param('z', 1, PARAM_INT);    // Zoom factor.
    $id = optional_param('id', 0, PARAM_INT);    // Course Module ID.
    $outputtype = optional_param('outputType', '', PARAM_CLEAN);    // Course Module ID.

    $project = $DB->get_record('techproject', array('id' => $projectid));
    $cm = $DB->get_record('course_modules', array('id' => $id));

    require_login($project->course);
    // Check current group and change, for anyone who could.
    $course = $DB->get_record('course', array('id' => $project->course));
    if (!$groupmode = groups_get_activity_groupmode($cm, $course)){ // Groups are being used ?
        $currentgroupid = 0;
    } else {
        $changegroup = isset($_GET['group']) ? $_GET['group'] : -1;
        // Group change requested ?
        if (isguestuser()) {
            // For guests, use session.
            if ($changegroup >= 0) {
                $_SESSION['guestgroup'] = $changegroup;
            }
            $currentgroupid = 0 + @$_SESSION['guestgroup'];
        } else {
            // For normal users, change current group.
            $currentgroupid = 0 + get_and_set_current_group($course, $groupmode, $changegroup);
        }
    }
}
$trace = 0;

// Graphical inits.
$CFG->systemfonts = "C:/WINNT/fonts/";
$ttffont = "Arial";

// Tracing output for debugging.
$TRACE = fopen("outputgs.txt", "a");
if ($trace) fwrite($TRACE, "GRAPH $r\n" );

// Get project info.
$timerangeend = ($project->projectend - $project->projectstart);
$zoomedtimerange = ($project->projectend - $project->projectstart) * $z;
$secsperpixel = $zoomedtimerange / $w;
$daysinrange = $zoomedtimerange / (3600 * 24);
$pixperday = $w / $daysinrange;

// Get first day (being $project->projectstart + $s).
$jdStart = unixtojd($project->projectstart + $s);

// draw calendar grid for monthes
$cal = cal_from_jd($jdStart, CAL_GREGORIAN);
$startmonth = $cal['month'];
$startyear = $cal['year'];

$today = unixtojd();

// Get milestones information.
$select = "
    projectid = ? AND
    groupid = ? AND
    deadlineenable = 1
";
$milestones = $DB->get_records_select('techproject_milestone', $select, array($project->id, $currentgroupid), 'deadline');
$milemarks = array();
if ($milestones) {
    foreach ($milestones as $aMilestone) {
        $milestonejd = unixtojd($aMilestone->deadline);
        $milemarks[$milestonejd] = $aMilestone;
    }
}

/*
 * This is the HTML Table non graphic output generation
 */
if (@$outputtype == 'HTML') {

    echo '<h2>Debugging only</h2>';

    echo $outputtype."<br>";
    echo "timeRangeEnd;    $timerangeend<br/>";
    echo "zoomedTimeRange; $zoomedtimerange<br/>";
    echo "secsPerPixel;    $secsperpixel<br/>";
    echo "daysInRange;     $daysinrange<br/>";
    echo "pixPerDay;       $pixperday<br/>";
    $startrange = 0;
    $color = 'white';
    $toggle = true;
    for($i = 0 ; $i < $daysinrange ; ){
        $cal = cal_from_jd($jdStart + $i, CAL_GREGORIAN);
        $mdays = cal_days_in_month(CAL_GREGORIAN, $cal['month'], $cal['year']);    
        $daystocomplete = ($i == 0) ? $mdays - $cal['day'] + 1 : $mdays ;
        $range = min($startrange + $daystocomplete * $pixperday, $w);
        echo "[ $startrange - $range ]<br/>";
        $toggle = !$toggle;
        $color = ($toggle) ? 'white' : 'lightgray' ;
        echo "($color)" ;
        $startrange = $range;
        $i += $daystocomplete;
    }
    phpinfo();

/*
 *
 * This is the GD generation alternative
 *
 */
} else {
    // Searching for font files
    $fontfile = $CFG->dirroot . "/mod/techproject/fonts/arial.ttf";
    if (!file_exists($fontfile))
        $fontfile = $CFG->dirroot . "/fonts/{$ttffont}.ttf";
    // if fonts where not given by project, try with system fonts.
    if (!file_exists($fontfile))
        $fontfile = @$CFG->systemfonts . "/{$ttffont}.ttf";
    if (!file_exists($fontfile)){
        echo "no font file for generator";
        exit(0);
    }

    header("Content-type: image/png");

    $height = (@$outputtype == 'HEADING') ? 22 : 1 ;
    // output special situations messages 
    $im = imagecreatetruecolor($w, $height);

    // imageantialias($im, TRUE);
    // Assigning colors
    $colors['black'] = imagecolorallocate($im, 0, 0, 0);
    $colors['quiteblack'] = imagecolorallocate($im, 10, 10, 10);
    $colors['white'] = imagecolorallocate($im, 240, 240, 240);
    $colors['lightgray'] = imagecolorallocate($im, 200, 200, 200);
    $colors['gray'] = imagecolorallocate($im, 150, 150, 150);
    $colors['darkgray'] = imagecolorallocate($im, 100, 100, 100);
    $colors['blue'] = imagecolorallocate($im, 0, 0, 180);
    $colors['lightblue'] = imagecolorallocate($im, 128, 128, 200);
    $colors['goldyellow'] = imagecolorallocate($im, 210, 210, 0);

    // Draw calendar grid for days.
    // Heading displays Gantt assignee section header with month names.
    if (@$outputtype == 'HEADING') {
        $startrange = 0;
        $color = $colors['white'];
        $toggle = true;
        for ($i = 0; $i < $daysinrange;) {
            $cal = cal_from_jd($jdStart + $i, CAL_GREGORIAN);
            $mdays = cal_days_in_month(CAL_GREGORIAN, $cal['month'], $cal['year']);
            $daystocomplete = ($i == 0) ? $mdays - $cal['day'] + 1 : $mdays;
            $range = min($startrange + $daystocomplete * $pixperday, $w);
            imagefilledrectangle($im, $startrange, 0, $range, 22, $color);
            if ($range - $startrange > 40) {
                $monthname = mb_convert_encoding(get_string(strtolower(jdmonthname($jdStart + $i, 1)), 'techproject'), 'auto', 'utf8');
                imagestring($im, 2, 1 + $startrange, 3, $monthname, $colors['quiteblack']);
            }
            $toggle = !$toggle;
            $color = ($toggle) ? $colors['white'] : $colors['lightgray'];
            $startrange = $range;
            $i += $daystocomplete;
        }
        for ($i = 0; $i <= $daysinrange; $i++) {
            // Prints special marks on calendar.
            if ($jdStart + $i == $today) {
                imagefilledrectangle($im, $i * $pixperday, 0, ($i + 1) * $pixperday - 1, 22, $colors['lightblue']);
            } else if (isset($milemarks[$jdStart + $i])) {
                imagefilledrectangle($im, $i * $pixperday, 0, ($i + 1) * $pixperday - 1, 22, $colors['goldyellow']);
            }
        }
    // Heading displays Gantt task grid background.
    } else {
        $startrange = 0;
        $color = $colors['white'];
        $toggle = true;
        for ($i = 0; $i < $daysinrange;) {
            $cal = cal_from_jd($jdStart + $i, CAL_GREGORIAN);
            $mdays = cal_days_in_month(CAL_GREGORIAN, $cal['month'], $cal['year']);
            $daystocomplete = ($i == 0) ? $mdays - $cal['day'] + 1 : $mdays;
            $range = min($startrange + $daystocomplete * $pixperday, $w);
            imagefilledrectangle($im, $startrange, 0, $range, 0, $color);
            $toggle = !$toggle;
            $color = ($toggle) ? $colors['white'] : $colors['lightgray'];
            $startrange = $range;
            $i += $daystocomplete;
        }
        for ($i = 0; $i <= $daysinrange; $i++) {
            // Prints special marks on calendar.
            if ($jdStart + $i == $today) {
                imagefilledrectangle($im, $i * $pixperday, 0, ($i + 1) * $pixperday - 1, 0, $colors['lightblue']);
            } elseif(isset($milemarks[$jdStart + $i])) {
                imagefilledrectangle($im, $i * $pixperday, 0, ($i + 1) * $pixperday - 1, 0, $colors['goldyellow']);
            }
            // Prints day line on calendar.
            switch (jddayofweek($jdStart + $i)) {
                case 0: {
                    $color = $colors['black'];
                    break;
                }
                case 6: {
                    $color = $colors['quiteblack'];
                    break;
                }
                default:
                    $color = $colors['gray'];
            }
            imagesetpixel($im, $i * $pixperday, 0, $color);
        }
    }

    // Delivering image.
    imagepng($im);
    imagedestroy($im);
}
