<?php
// This generator may be included directly by an include call, or invoked
// through an HTTP request.
if (@$wasIncluded == 0){
    require_once('../../../config.php');
    $projectid = required_param('projectid', PARAM_INT);    // project id
    $s = optional_param('s', 0, PARAM_INT);    // start offset
    $w = optional_param('w', 600, PARAM_INT);    // graphic width
    $z = optional_param('z', 1, PARAM_INT);    // zoom factor
    $id = optional_param('id', 0, PARAM_INT);    // Course Module ID
    $outputType = optional_param('outputType', '', PARAM_CLEAN);    // Course Module ID

    $project = $DB->get_record('techproject', array('id' => $projectid));
    $cm = $DB->get_record('course_modules', array('id' => $id));

    require_login($project->course);
    // check current group and change, for anyone who could
    $course = $DB->get_record('course', array('id' => $project->course));
	if (!$groupmode = groups_get_activity_groupmode($cm, $course)){ // groups are being used ?
		$currentgroupid = 0;
	} else {
        $changegroup = isset($_GET['group']) ? $_GET['group'] : -1;  // Group change requested?
        if (isguestuser()){ // for guests, use session
            if ($changegroup >= 0){
                $_SESSION['guestgroup'] = $changegroup;
            }
            $currentgroupid = 0 + @$_SESSION['guestgroup'];
        } else { // for normal users, change current group
            $currentgroupid = 0 + get_and_set_current_group($course, $groupmode, $changegroup);    
        }
    }
}
$trace = 0;

//graphical inits
$CFG->systemfonts = "C:/WINNT/fonts/";
$ttffont = "Arial";

//tracing output for debugging
$TRACE = fopen("outputgs.txt", "a");
if ($trace) fwrite($TRACE, "GRAPH $r\n" );

// get project info
$timeRangeEnd = ($project->projectend - $project->projectstart);
$zoomedTimeRange = ($project->projectend - $project->projectstart) * $z;
$secsPerPixel = $zoomedTimeRange / $w;
$daysInRange = $zoomedTimeRange / (3600 * 24);
$pixPerDay = $w / $daysInRange;

// get first day (being $project->projectstart + $s)
$jdStart = unixtojd($project->projectstart + $s);

// draw calendar grid for monthes
$cal = cal_from_jd($jdStart, CAL_GREGORIAN);
$startmonth = $cal['month'];
$startyear = $cal['year'];

$today = unixtojd();

// get milestones information
$milestones = $DB->get_records_select('techproject_milestone', "projectid = $project->id AND groupid = $currentgroupid AND deadlineenable = 1", 'deadline');
$milemarks = array();
if ($milestones){
    foreach($milestones as $aMilestone){
        $milestonejd = unixtojd($aMilestone->deadline);
        $milemarks[$milestonejd] = $aMilestone;
    }
}
/*
*
* This is the HTML Table non graphic output generation
*
*/
if (@$outputType == 'HTML'){
?>
<h2>Debugging only</h2>
<?php
    echo $outputType."<br>";
    echo "timeRangeEnd;    $timeRangeEnd<br/>";
    echo "zoomedTimeRange; $zoomedTimeRange<br/>";
    echo "secsPerPixel;    $secsPerPixel<br/>";
    echo "daysInRange;     $daysInRange<br/>";
    echo "pixPerDay;       $pixPerDay<br/>";
    $startRange = 0;
    $color = 'white';
    $toggle = true;
    for($i = 0 ; $i < $daysInRange ; ){
        $cal = cal_from_jd($jdStart + $i, CAL_GREGORIAN);
        $mdays = cal_days_in_month(CAL_GREGORIAN, $cal['month'], $cal['year']);    
        $daysToComplete = ($i == 0) ? $mdays - $cal['day'] + 1 : $mdays ;
        $range = min($startRange + $daysToComplete * $pixPerDay, $w);
        echo "[ $startRange - $range ]<br/>";
        // imagefilledrectangle($im, 0, $startRange, $range, 1, $color);
        $toggle = !$toggle;
        $color = ($toggle) ? 'white' : 'lightgray' ;
        echo "($color)" ;
        $startRange = $range;
        $i += $daysToComplete;
    }
    phpinfo();

/*
*
* This is the GD generation alternative
*
*/
} else {
    // Searching for font files
    $fontFile = $CFG->dirroot . "/mod/techproject/fonts/arial.ttf";
    if (!file_exists($fontFile))
        $fontFile = $CFG->dirroot . "/fonts/{$ttffont}.ttf";
    // if fonts where not given by project, try with system fonts.
    if (!file_exists($fontFile))
        $fontFile = @$CFG->systemfonts . "/{$ttffont}.ttf";
    if (!file_exists($fontFile)){
        echo "no font file for generator";
        exit(0);
    }

    header("Content-type: image/png");

    $height = (@$outputType == 'HEADING') ? 22 : 1 ;
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

    // draw calendar grid for days
    // heading displays Gantt assignee section header with month names
    if (@$outputType == 'HEADING'){
        $startRange = 0;
        $color = $colors['white'];
        $toggle = true;
        for($i = 0 ; $i < $daysInRange ; ){
            $cal = cal_from_jd($jdStart + $i, CAL_GREGORIAN);
            $mdays = cal_days_in_month(CAL_GREGORIAN, $cal['month'], $cal['year']);    
            $daysToComplete = ($i == 0) ? $mdays - $cal['day'] + 1 : $mdays ;
            $range = min($startRange + $daysToComplete * $pixPerDay, $w);
            imagefilledrectangle($im, $startRange, 0, $range, 22, $color);
            if ($range - $startRange > 40){
                imagestring($im, 2, 1 + $startRange, 3, mb_convert_encoding(get_string(strtolower(jdmonthname($jdStart + $i, 1)), 'techproject'), 'auto', 'utf8'), $colors['quiteblack']);
            }
            $toggle = !$toggle;
            $color = ($toggle) ? $colors['white'] : $colors['lightgray'] ;
            $startRange = $range;
            $i += $daysToComplete;
        }
        for ($i = 0; $i <= $daysInRange ; $i++){
            // prints special marks on calendar
            if ($jdStart + $i == $today){
                imagefilledrectangle($im, $i * $pixPerDay, 0, ($i + 1) * $pixPerDay - 1, 22, $colors['lightblue']);
            } elseif(isset($milemarks[$jdStart + $i])){
                imagefilledrectangle($im, $i * $pixPerDay, 0, ($i + 1) * $pixPerDay - 1, 22, $colors['goldyellow']);
            }
        }
    // heading displays Gantt task grid background
    } else {
        $startRange = 0;
        $color = $colors['white'];
        $toggle = true;
        for($i = 0 ; $i < $daysInRange ; ){
            $cal = cal_from_jd($jdStart + $i, CAL_GREGORIAN);
            $mdays = cal_days_in_month(CAL_GREGORIAN, $cal['month'], $cal['year']);    
            $daysToComplete = ($i == 0) ? $mdays - $cal['day'] + 1 : $mdays ;
            $range = min($startRange + $daysToComplete * $pixPerDay, $w);
            imagefilledrectangle($im, $startRange, 0, $range, 0, $color);
            $toggle = !$toggle;
            $color = ($toggle) ? $colors['white'] : $colors['lightgray'] ;
            $startRange = $range;
            $i += $daysToComplete;
        }
        for ($i = 0; $i <= $daysInRange ; $i++){
            // prints special marks on calendar
            if ($jdStart + $i == $today){
                imagefilledrectangle($im, $i * $pixPerDay, 0, ($i + 1) * $pixPerDay - 1, 0, $colors['lightblue']);
            } elseif(isset($milemarks[$jdStart + $i])){
                imagefilledrectangle($im, $i * $pixPerDay, 0, ($i + 1) * $pixPerDay - 1, 0, $colors['goldyellow']);
            }
            // prints day line on calendar
            switch (jddayofweek($jdStart + $i)){
                case 0 : $color = $colors['black']; break;
                case 6 : $color = $colors['quiteblack']; break;
                default : $color = $colors['gray']; break;
            }
            imagesetpixel($im, $i * $pixPerDay, 0, $color);
        }
    }

    // delivering image
    imagepng($im);
    imagedestroy($im);
}
?>