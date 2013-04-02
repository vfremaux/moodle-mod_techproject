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
 * Post-install code for the quiz module.
 *
 * @package    mod
 * @subpackage techproject
 * @copyright  2009 Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Code run after the quiz module database tables have been created.
 */
function xmldb_techproject_install() {
    global $DB;

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'complexity';
    $record->code 		 = '*';
    $record->label 		 = 'evident';
    $record->description = 'evidentdesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'complexity';
    $record->code 		 = '**';
    $record->label 		 = 'simple';
    $record->description = 'simpledesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'complexity';
    $record->code 		 = '***';
    $record->label 		 = 'medium';
    $record->description = 'mediumdesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'complexity';
    $record->code 		 = '****';
    $record->label 		 = 'hard';
    $record->description = 'harddesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'complexity';
    $record->code 		 = '*****';
    $record->label 		 = 'verycomplex';
    $record->description = 'verycomplexdesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'delivstatus';
    $record->code 		 = 'CRE';
    $record->label 		 = 'created';
    $record->description = 'createddesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'delivstatus';
    $record->code 		 = 'TST';
    $record->label 		 = 'testing';
    $record->description = 'testingdesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'delivstatus';
    $record->code 		 = 'PCK';
    $record->label 		 = 'packaging';
    $record->description = 'packagingdesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'delivstatus';
    $record->code 		 = 'BET';
    $record->label 		 = 'beta';
    $record->description = 'betadesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'delivstatus';
    $record->code 		 = 'REV';
    $record->label 		 = 'reviewing';
    $record->description = 'reviewingdesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'delivstatus';
    $record->code 		 = 'STB';
    $record->label 		 = 'stable';
    $record->description = 'stabledesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'delivstatus';
    $record->code 		 = 'FIX';
    $record->label 		 = 'fix';
    $record->description = 'fixdesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'delivstatus';
    $record->code 		 = 'OBS';
    $record->label 		 = 'obsolete';
    $record->description = 'obsoletedesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'delivstatus';
    $record->code 		 = ' ';
    $record->label 		 = 'unassigned';
    $record->description = 'unassigneddesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'priority';
    $record->code 		 = ' ';
    $record->label 		 = 'unassigned';
    $record->description = 'unassigneddesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'priority';
    $record->code 		 = '1';
    $record->label 		 = 'canwait';
    $record->description = 'canwaitdesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'priority';
    $record->code 		 = '2';
    $record->label 		 = 'notprioritary';
    $record->description = 'notprioritarydesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'priority';
    $record->code 		 = '3';
    $record->label 		 = 'asap';
    $record->description = 'asapdesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'priority';
    $record->code 		 = '4';
    $record->label 		 = 'urgent';
    $record->description = 'urgentdesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'priority';
    $record->code 		 = '5';
    $record->label 		 = 'prioritary';
    $record->description = 'prioritarydesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'severity';
    $record->code 		 = ' ';
    $record->label 		 = 'unassigned';
    $record->description = 'unassigneddesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'severity';
    $record->code 		 = '*';
    $record->label 		 = 'goodie';
    $record->description = 'goodiedesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'severity';
    $record->code 		 = '**';
    $record->label 		 = 'optional';
    $record->description = 'optionaldesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'severity';
    $record->code 		 = '***';
    $record->label 		 = 'useful';
    $record->description = 'usefuldesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'severity';
    $record->code 		 = '****';
    $record->label 		 = 'essential';
    $record->description = 'essentialdesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'severity';
    $record->code 		 = '*****';
    $record->label 		 = 'mandatory';
    $record->description = 'mandatorydesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'strength';
    $record->code 		 = ' ';
    $record->label 		 = 'unassigned';
    $record->description = 'unassigneddesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'strength';
    $record->code 		 = '*';
    $record->label 		 = 'plus';
    $record->description = 'plusdesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'strength';
    $record->code 		 = '**';
    $record->label 		 = 'implicit';
    $record->description = 'implicitdesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'strength';
    $record->code 		 = '***';
    $record->label 		 = 'wished';
    $record->description = 'wisheddesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'strength';
    $record->code 		 = '****';
    $record->label 		 = 'should';
    $record->description = 'shoulddesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'strength';
    $record->code 		 = '*****';
    $record->label 		 = 'will';
    $record->description = 'willdesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'strength';
    $record->code 		 = '******';
    $record->label 		 = 'must';
    $record->description = 'mustdesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'taskstatus';
    $record->code 		 = 'PLANNED';
    $record->label 		 = 'planned';
    $record->description = 'planneddesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'taskstatus';
    $record->code 		 = 'STARTED';
    $record->label 		 = 'started';
    $record->description = 'starteddesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'taskstatus';
    $record->code 		 = 'BLOCKED';
    $record->label 		 = 'blocked';
    $record->description = 'blockeddesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'taskstatus';
    $record->code 		 = 'DELAYED';
    $record->label 		 = 'delayed';
    $record->description = 'delayeddesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'taskstatus';
    $record->code 		 = 'COMPLETE';
    $record->label 		 = 'complete';
    $record->description = 'completedesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'taskstatus';
    $record->code 		 = 'ABANDONED';
    $record->label 		 = 'abandoned';
    $record->description = 'abandoneddesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'worktype';
    $record->code 		 = 'OTH';
    $record->label 		 = 'other';
    $record->description = 'otherdesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'worktype';
    $record->code 		 = 'PRE';
    $record->label 		 = 'investigating';
    $record->description = 'investigatingdesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'worktype';
    $record->code 		 = 'ANA';
    $record->label 		 = 'analysing';
    $record->description = 'analysingdesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'worktype';
    $record->code 		 = 'COD';
    $record->label 		 = 'coding';
    $record->description = 'codingdesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'worktype';
    $record->code 		 = 'REV';
    $record->label 		 = 'reviewing';
    $record->description = 'reviewingdesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'worktype';
    $record->code 		 = 'PAK';
    $record->label 		 = 'packaging';
    $record->description = 'packagingdesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'worktype';
    $record->code 		 = 'DOC';
    $record->label 		 = 'documenting';
    $record->description = 'documentingdesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'worktype';
    $record->code 		 = 'SPE';
    $record->label 		 = 'specifying';
    $record->description = 'specifyingdesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'risk';
    $record->code 		 = '0';
    $record->label 		 = 'none';
    $record->description = 'nonedesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'risk';
    $record->code 		 = '10';
    $record->label 		 = 'light';
    $record->description = 'lightdesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'risk';
    $record->code 		 = '30';
    $record->label 		 = 'medium';
    $record->description = 'mediumdesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'risk';
    $record->code 		 = '80';
    $record->label 		 = 'high';
    $record->description = 'highdesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'heavyness';
    $record->code 		 = '0';
    $record->label 		 = 'needsmoreinfo';
    $record->description = 'needsmoreinfodesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'heavyness';
    $record->code 		 = '10';
    $record->label 		 = 'wehave';
    $record->description = 'wehavedesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'heavyness';
    $record->code 		 = '20';
    $record->label 		 = 'wecanhave';
    $record->description = 'wecanhavedesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'heavyness';
    $record->code 		 = '30';
    $record->label 		 = 'needswork';
    $record->description = 'needsworkdesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'heavyness';
    $record->code 		 = '40';
    $record->label 		 = 'difficult';
    $record->description = 'difficultdesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'heavyness';
    $record->code 		 = '50';
    $record->label 		 = 'heavy';
    $record->description = 'heavydesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'heavyness';
    $record->code 		 = '100';
    $record->label 		 = 'outofreason';
    $record->description = 'outofreasondesc';
    $DB->insert_record('techproject_qualifier', $record);

    $record = new stdClass();
    $record->projectid	 = 0;
    $record->domain 	 = 'heavyness';
    $record->code 		 = '10000';
    $record->label 		 = 'impossible';
    $record->description = 'impossibledesc';
    $DB->insert_record('techproject_qualifier', $record);
}
