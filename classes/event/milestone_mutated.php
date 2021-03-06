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
 * mod_techproject milestone created event.
 *
 * @package    mod_techproject
 * @copyright  2013 Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_techproject\event;
defined('MOODLE_INTERNAL') || die();

class milestone_mutated extends \core\event\base {
    /**
     * Create instance of event.
     *
     * @since Moodle 2.7
     *
     * @param \stdClass $book
     * @param \context_module $context
     * @param \stdClass $chapter
     * @return milestone_updated
     */
    public static function create_from_milestone(\stdClass $techproject, \context_module $context, $idlist, $groupid) {
        $data = array(
            'contextid' => $context->id,
            'objectid' => $techproject->id,
            'other' => array('idlist' => $idlist,
                'groupid' => $groupid),
        );
        /** @var milestone_mutated $event */
        $event = self::create($data);
        $event->add_record_snapshot('techproject', $techproject);
        return $event;
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The milestones $this->idlist in techproject " . $this->contextinstanceid . " has been mut  ated (moved/copied).";
    }

    /**
     * Return the legacy event log data.
     *
     * @return array|null
     */
    protected function get_legacy_logdata() {
        return array($this->courseid, 'techproject', 'movecopymiles', $this->get_url(), 0, $this->contextinstanceid);
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_miles_mutated', 'mod_techproject');
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/techproject/view.php', array(
            'id' => $this->contextinstanceid,
            'view' => 'milestones',
            'group' => $this->other['groupid']
        ));
    }

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'techproject';
    }

}
