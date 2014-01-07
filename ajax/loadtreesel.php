<?php

	include '../../../config.php';
	include_once '../locallib.php';
	require_once '../treelib.php';

	$id = required_param('id', PARAM_INT); // course id
	$projectid = required_param('project', PARAM_INT);
	$groupid = required_param('group', PARAM_INT);
	$target = required_param('target', PARAM_TEXT);

	if (!$course = $DB->get_record('course', array('id' => $id))) die("Error : Invalid Course ID");

	$cm = get_coursemodule_from_instance('techproject', $projectid, $id);

	require_login($course, $cm);

	$parent = 0;
	
	switch($target){
		case 'reqs':
		case 'reqswb':
			$targettable = 'requirement';
			break;
		case 'specs':
		case 'specswb':
			$targettable = 'specification';
			break;
		case 'tasks':
		case 'taskswb':
			$targettable = 'task';
			break;
		case 'deliv':
		case 'delivwb':
			$targettable = 'deliverable';
			break;
	}
	
	// echo "techproject_$targettable";
	
	$targettree = techproject_get_tree_options("techproject_$targettable", $projectid, $groupid);

	echo '<select name="parent">';
	echo '<option value="0">'.get_string('rootnode', 'techproject').'</option>';
	foreach($targettree as $anode){
		echo "<option value=\"{$anode->id}\">{$anode->ordering} - ".shorten_text($anode->abstract, 90)."</option>";
	}
	echo "</select>";
