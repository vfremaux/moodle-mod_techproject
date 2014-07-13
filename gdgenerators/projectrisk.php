<?php
// This generator may be included directly by an include call, or invoked
// through an HTTP request.

define('GRAPHWIDTH', 250);
define('GRAPHEIGHT', 250);
define('XOFFSET', 47);
define('YOFFSET', 8);

if (@$wasIncluded == 0){
    require_once('../../../config.php');
    $projectid = required_param('projectid', PARAM_INT);    // project id
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

// get requirements information
$requirements = $DB->get_records_select('techproject_requirement', "projectid = ? AND groupid = ? ", array($project->id, $currentgroupid));

$heavyness['WEHAVE'] = 0;
$heavyness[''] = 1;
$heavyness['*'] = 2;
$heavyness['**'] = 3;
$heavyness['***'] = 4;
$heavyness['IMPOSSIBLE'] = 5;
$heavyness['OUTOFREASON'] = 1000;

$strength['*'] = 0;
$strength['**'] = 1;
$strength['***'] = 2;
$strength['****'] = 3;
$strength['*****'] = 4;
$strength['******'] = 5;

$count = 0;
$strengthsum = 0;
$heavynesssum = 0;
if ($requirements){
    foreach($requirements as $r){
    	if ($r->heavyness == 'NEEDMOREINFO'){
    		continue;
    	}
    	$count++;
    	$strengthsum += (array_key_exists($r->strength, $strength)) ? $strength[$r->strength] : 2.5 ;
    	$heavynesssum += (array_key_exists($r->heavyness, $heavyness)) ? $heavyness[$r->heavyness] : 2.5 ;
    }
}

header("Content-type: image/png");

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

if ($count){

	$strengthmean = $strengthsum / $count;
	$heavynessmean = $heavynesssum / $count;

	$projectposx = XOFFSET + GRAPHWIDTH * $strengthmean / 5; // from left edge
	$projectposy = YOFFSET + GRAPHWIDTH * (1 - $heavynessmean / 5); // from bottom edge

	$lang = current_language();
	$background = $CFG->dirroot."/mod/techproject/pix/risk_bg_{$lang}.png";
	if (!file_exists($background)){
		$background = $CFG->dirroot."/mod/techproject/pix/risk_bg_en.png";
	}
	$image = imagecreatefrompng($background);
	
    $colors['blue'] = imagecolorallocate($image, 0, 0, 180);

	imageellipse ($image , $projectposx , $projectposy , 19 , 19 , $colors['blue'] );
	imageellipse ($image , $projectposx , $projectposy , 18 , 18 , $colors['blue'] );
	imageellipse ($image , $projectposx , $projectposy , 13 , 13 , $colors['blue'] );
	imageellipse ($image , $projectposx , $projectposy , 12 , 12 , $colors['blue'] );
	imageellipse ($image , $projectposx , $projectposy , 11 , 11 , $colors['blue'] );
	imageellipse ($image , $projectposx , $projectposy , 10 , 10 , $colors['blue'] );
	imageellipse ($image , $projectposx , $projectposy , 9 , 9 , $colors['blue'] );
	imageellipse ($image , $projectposx , $projectposy , 8 , 8 , $colors['blue'] );
	
    // delivering image
    imagepng($image);
    imagedestroy($image);
} else {
	$background = $CFG->dirroot.'/mod/techproject/pix/risk_uncalculable.png';
	$image = imagecreatefrompng($background);
    imagepng($image);
    imagedestroy($image);
}