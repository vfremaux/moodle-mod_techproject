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

/**
 * @package vodeclic
 * @subpackage backup-moodle2
 * @copyright 2010 onwards Valery Fremaux (valery.freamux@club-internet.fr)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_url_activity_task
 */

/**
 * Structure step to restore one vodeclic activity
 */
class restore_vodeclic_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $vodeclic = new restore_path_element('vodeclic', '/activity/vodeclic');
        $paths[] = $vodeclic;
        
        if ($userinfo){
	        $paths[] = new restore_path_element('vodeclic_userdata', '/activity/vodeclic/userdata/datum');
	        $paths[] = new restore_path_element('vodeclic_userlog', '/activity/vodeclic/userlog/log');
        }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_vodeclic($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // insert the label record
        $newitemid = $DB->insert_record('vodeclic', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }

    protected function after_execute() {
        // Add vodeclic related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_vodeclic', 'intro', null);
    }

    protected function process_vodeclic_userdata($data) {
    	global $DB;
    	
        $data = (object)$data;

        $data->vodeclicid = $this->get_new_parentid('vodeclic');

        $data->userid = $this->get_mappingid('user', $data->userid);

        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('vodeclic_userdata', $data);
        $this->set_mapping('vodeclic_userdata', $oldid, $newitemid, false); // Has no related files
    }

    protected function process_vodeclic_userlog($data) {
    	global $DB;
    	
        $data = (object)$data;

        $data->vodeclicid = $this->get_new_parentid('vodeclic');

        $data->userid = $this->get_mappingid('user', $data->userid);

        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // The data is actually inserted into the database later in inform_new_usage_id.
        $newitemid = $DB->insert_record('vodeclic_userlog', $data);
        $this->set_mapping('vodeclic_userlog', $oldid, $newitemid, false); // Has no related files
    }

}
