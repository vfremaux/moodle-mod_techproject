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
 * mod_techproject specification mutated event.
 *
 * @package    mod_techproject
 * @copyright  2013 Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_techproject\event;
defined('MOODLE_INTERNAL') || die();

class specification_updated extends \core\event\base {

    /**
     * Create instance of event.
     *
     * @since Moodle 2.7
     *
     * @param \stdClass $techproject
     * @param \context_module $context
     * @param \stdClass $specification
     * @param int $groupid
     * @return specification_updated
     */
    public static function create_from_specification(\stdClass $techproject, \context_module $context, \stdClass $specification, $groupid) {
        global $DB;

        $data = array(
            'contextid' => $context->id,
            'objectid' => $specification->id,
            'other' => $groupid
        );
        /** @var specification_updated $event */
        $event = self::create($data);
        $event->add_record_snapshot('techproject', $techproject);
        if (!empty($data->id)) {
            $event->add_record_snapshot('techproject_specification', $DB->get_record('techproject_specification', array('id' => $data->id)));
        }
        return $event;
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The specification $this->objectid in techproject " . $this->contextinstanceid . " has been updated.";
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_spec_updated', 'mod_techproject');
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/techproject/view.php', array(
            'id' => $this->contextinstanceid,
            'view' => 'specifications',
            'itemid' => $this->objectid,
            'group' => $this->other
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
        $this->data['objecttable'] = 'techproject_specification';
    }
}
