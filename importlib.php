<?php

if (!defined('MOODLE_INTERNAL')) die("You cannot access this script directly");

function techproject_import_entity($techprojectid, $cmid, $data, $type, $groupid){
	global $USER, $CFG;
	
	// normalise to unix
	$data = str_replace("\r\n", "\n", $data);
	$data = explode("\n", $data);

	$errors = 0;
	$errors_no_parent = 0;
	$errors_insert = 0;
	$errors_bad_count = 0;

	switch($type){
		case 'requs':
			$tablename = 'techproject_requirement';
			$view = 'requirements';
		break;
		case 'specs':
			$tablename = 'techproject_specification';
			$view = 'specifications';
		break;
		case 'tasks':
			$tablename = 'techproject_task';
			$view = 'tasks';
		break;
		case 'deliv':
			$tablename = 'techproject_deliverable';
			$view = 'deliverables';
		break;
		default:
		 error ("Unknown import type");
	}
	
	if (!empty($data)){
		$columns = $data[0];
		$columnnames = explode(';', $columns);
		
		if (!in_array('id', $columnnames)){
		 	error ("Bad file format. Missing column \"id\"");
		}
		if (!in_array('id', $columnnames)){
		 	error ("Bad file format. Missing column \"parent\"");
		}
		
		// removing title column
		$titleline = true;
		
		$i = 2;
		
		echo "<pre>";
		$errors_bad_count = 0;
		foreach($data as $line){

			if ($titleline == true){
				$titleline = false;
				continue;
			}

			$recordarr = explode(';', $line);
			if (count($recordarr) != count($columnnames)) {
				$errors_bad_count++;
				mtrace("\nBad count at line : $i");
				$i++;
				continue;
			} else {
				$checkedrecords[] = $line;
			}
			$i++;
		}
		echo '</pre>';
	} else {
		error("No records");
	}

	if (!empty($checkedrecords)){
		
		// test insertability on first record before deleting everything
		$recobject = (object)array_combine($columnnames, explode(';', $checkedrecords[0]));
		$recobject = addslashes_object($recobject);
		unset($recobject->id);
		unset($recobject->parent);

		$recobject->userid = $USER->id;
		$recobject->created = time();
		$recobject->modified = time();
		$recobject->lastuserid = $USER->id;
		$recobject->groupid = $groupid;
		$recobject->format = 0;
		$recobject->abstract = '';

		if (insert_record($tablename, $recobject)){
				
			delete_records($tablename, 'projectid', $techprojectid);
	
			// purge crossmappings
			switch($type){
				case 'requs':
					delete_records('techproject_spec_to_req', 'projectid', $techprojectid);
				break;
				case 'specs':
					delete_records('techproject_spec_to_req', 'projectid', $techprojectid);
					delete_records('techproject_task_to_spec', 'projectid', $techprojectid);
				break;
				case 'tasks':
					delete_records('techproject_task_to_spec', 'projectid', $techprojectid);
					delete_records('techproject_task_to_deliv', 'projectid', $techprojectid);
					delete_records('techproject_task_dependency', 'projectid', $techprojectid);
				break;
				case 'deliv':
					delete_records('techproject_task_to_deliv', 'projectid', $techprojectid);
				break;
			}
			
			$ID_MAP = array();
			$PARENT_ORDERING = array();
			$ordering = 1;
	
			foreach($checkedrecords as $record){
				$recobject = (object)array_combine($columnnames, explode(';', $record));
				$recobject = addslashes_object($recobject);
				
				$oldid = $recobject->id;
				$parent = $recobject->parent;
				unset($recobject->id);
				unset($recobject->parent);
				
				if (!isset($TREE_ORDERING[$parent])){
					$TREE_ORDERING[$parent] = 1;
				} else {
					$TREE_ORDERING[$parent]++;
				}
				$recobject->ordering = $TREE_ORDERING[$parent];
				
				if ($parent != 0){
					if (empty($ID_MAP[$parent])){
						$errors++;
						$errors_no_parent++;
						continue;
					}
					$recobject->fatherid = $ID_MAP[$parent];
				} else {
					$recobject->fatherid = 0;
				}

				$recobject->projectid = $techprojectid;
				$recobject->format = 0;
				$recobject->created = time();
				$recobject->modified = time();
				$recobject->userid = $USER->id;
				$recobject->lastuserid = $USER->id;
				if(empty($recobject->abstract)){
					$recobject->abstract = shorten_text($recobject->description, 100);
				}

				// prepare record
				switch($type){
					case 'requs':
					break;
					case 'specs':
					break;
					case 'tasks':
					break;
					case 'deliv':
					break;
				}

				if (!($ID_MAP["$oldid"] = insert_record($tablename, $recobject))){
					$errors++;
					$errors_insert++;
				}
			}
		} else {
			notice("Could not insert records. Maybe file column names are not compatible. ". mysql_error());
		}
	}
	
	if($errors){
		echo "Errors : $errors<br/>";
		echo "Errors in tree : $errors_no_parent<br/>";
		echo "Insertion Errors : $errors_insert<br/>";
		echo "Insertion Errors : $errors_bad_counts<br/>";
	}
	
	print_continue($CFG->wwwroot."/mod/techproject/view.php?view=$view&id=$cmid");
	print_footer();
	exit();
}

?>