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
 * mod_techproject task created event.
 *
 * @package    mod_techproject
 * @copyright  2013 Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_techproject\event;
defined('MOODLE_INTERNAL') || die();

class grade_erased extends \core\event\base {
    /**
     * Create instance of event.
     *
     * @since Moodle 2.7
     *
     * @param \stdClass $book
     * @param \context_module $context
     * @param \stdClass $chapter
     * @return task_updated
     */
    public static function create_from_assessment(\stdClass $techproject, \context_module $context, \stdClass $assessment, $studentid) {
        $data = array(
            'context' => $context,
            'objectid' => $assessment->id,
            'studentid' => $studentid
        );
        /** @var task_created $event */
        $event = self::create($data);
        $event->add_record_snapshot('techproject', $techproject);
        $event->add_record_snapshot('techproject_assessment', $assessment);
        return $event;
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The assessment of user $this->studentid in techproject " . $this->contextinstanceid . " has been erased.";
    }

    /**
     * Return the legacy event log data.
     *
     * @return array|null
     */
    protected function get_legacy_logdata() {
        return array($this->courseid, 'techproject', 'grade erased', $this->get_url(), $this->objectid, $this->contextinstanceid);
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_grade_erased', 'mod_techproject');
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/techproject/view.php', array(
            'id' => $this->contextinstanceid,
            'view' => 'teacher_assess',
            'itemid' => $this->objectid,
            'group' => $this->groupid
        ));
    }

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'techproject_assessment';
    }
}
