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

define('GRAPHWIDTH', 250);
define('GRAPHEIGHT', 250);
define('XOFFSET', 47);
define('YOFFSET', 8);

if (!defined('MOODLE_INTERNAL')) {
    require('../../../config.php');

    include_once($CFG->dirroot.'/mod/techproject/gdgenerators/lib.php');

    $outputtype = optional_param('outputType', '', PARAM_CLEAN); // Course Module ID.
    $projectid = required_param('projectid', PARAM_INT); // Project id.
    $id = optional_param('id', 0, PARAM_INT); // Course Module ID.

    $project = $DB->get_record('techproject', array('id' => $projectid));
    $cm = $DB->get_record('course_modules', array('id' => $id));

    require_login($project->course);

    // Check current group and change, for anyone who could.
    $course = $DB->get_record('course', array('id' => $project->course));
    $currentgroupid = techproject_resolve_group($course, $cm);
}
$trace = 0;

// Get requirements information.
$select = "
    projectid = ? AND
    groupid = ?
";
$requirements = $DB->get_records_select('techproject_requirement', $select, array($project->id, $currentgroupid));

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
if ($requirements) {
    foreach ($requirements as $r) {
        if ($r->heavyness == 'NEEDMOREINFO') {
            continue;
        }
        $count++;
        $strengthsum += (array_key_exists($r->strength, $strength)) ? $strength[$r->strength] : 2.5;
        $heavynesssum += (array_key_exists($r->heavyness, $heavyness)) ? $heavyness[$r->heavyness] : 2.5;
    }
}

header("Content-type: image/png");

// Searching for font files.
$fontfile = $CFG->dirroot.'/mod/techproject/fonts/arial.ttf';
if (!file_exists($fontfile)) {
    $fontfile = $CFG->dirroot . '/fonts/'.$ttffont.'.ttf';
}
// If fonts where not given by project, try with system fonts.
if (!file_exists($fontfile)) {
    $fontfile = @$CFG->systemfonts.'/'.$ttffont.'.ttf';
}
if (!file_exists($fontfile)) {
    echo "No font file for generator";
    exit(0);
}

if ($count) {
    $strengthmean = $strengthsum / $count;
    $heavynessmean = $heavynesssum / $count;

    $projectposx = XOFFSET + GRAPHWIDTH * $strengthmean / 5; // From left edge.
    $projectposy = YOFFSET + GRAPHWIDTH * (1 - $heavynessmean / 5); // From bottom edge.

    $lang = current_language();
    $background = $CFG->dirroot.'/mod/techproject/pix/risk_bg_'.$lang.'.png';
    if (!file_exists($background)) {
        $background = $CFG->dirroot.'/mod/techproject/pix/risk_bg_en.png';
    }
    $image = imagecreatefrompng($background);

    $colors['blue'] = imagecolorallocate($image, 0, 0, 180);

    imageellipse ($image , $projectposx , $projectposy , 19 , 19 , $colors['blue']);
    imageellipse ($image , $projectposx , $projectposy , 18 , 18 , $colors['blue']);
    imageellipse ($image , $projectposx , $projectposy , 13 , 13 , $colors['blue']);
    imageellipse ($image , $projectposx , $projectposy , 12 , 12 , $colors['blue']);
    imageellipse ($image , $projectposx , $projectposy , 11 , 11 , $colors['blue']);
    imageellipse ($image , $projectposx , $projectposy , 10 , 10 , $colors['blue']);
    imageellipse ($image , $projectposx , $projectposy , 9 , 9 , $colors['blue']);
    imageellipse ($image , $projectposx , $projectposy , 8 , 8 , $colors['blue']);

    // Delivering image.
    imagepng($image);
    imagedestroy($image);
} else {
    $background = $CFG->dirroot.'/mod/techproject/pix/risk_uncalculable.png';
    $image = imagecreatefrompng($background);
    imagepng($image);
    imagedestroy($image);
}