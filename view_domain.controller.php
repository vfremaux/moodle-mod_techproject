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
 * @package mod_techproject
 * @category mod
 * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
 * @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
defined('MOODLE_INTERNAL') || die();

/*
 * Master Controler for all domains
 */

switch ($action) {

    case 'add': {
        // Adds a new domain ***************************.
        require_once('forms/form_domain.class.php');
        if ($data = data_submitted()) {

            $params = array('id' => $id, 'view' => 'domains_'.$domain, 'what' => 'add', 'view' => 'domains_'.$domain);
            $returnurl = new moodle_url('/mod/techproject/view.php', $params);

            // If there is some error.
            if ($data->code == '') {
                print_error('err_code', 'techproject', $returnurl);
            } else if ($data->label == '') {
                print_error('err_value', 'techproject', $returnurl);
            } else {
                // Data was submitted from this form, process it.
                $domainrec->projectid = $scope;
                $domainrec->domain = $domain;
                $domainrec->code = clean_param($data->code, PARAM_ALPHANUM);
                $domainrec->label = clean_param($data->label, PARAM_CLEANHTML);
                $domainrec->description = clean_param($data->description, PARAM_CLEANHTML);

                $params = array('domain' => $domain, 'code' => $data->code, 'projectid' => $scope);
                if ($DB->get_record('techproject_qualifier', $params)) {
                    print_error('err_codeexists', 'techproject', '', $returnurl);
                } else {
                    $DB->insert_record('techproject_qualifier', $domainrec);
                    redirect(new moodle_url('/mod/techproject/view.php', array('id' => $id, 'view' => 'domains_'.$domain)));
                }
            }
        } else {
            $default->code = 'XXX';
            $default->label = '';
            $default->description = '';
            $params = array('id' => $id, 'what' => 'add');
            $newdomain = new Domain_Form($domain, $default, new moodle_url('/mod/techproject/view.php', $params));
            $newdomain->display();
            return -1;
        }
        break;
    }

    case 'update': {
        // Updates a domain value **************************************.
        $domainid = required_param('domainid', PARAM_INT);

        require_once('forms/form_domain.class.php');

        if (!$domainrec = $DB->get_record('techproject_qualifier', array('id' => $domainid))) {
            print_error('errorinvalidedoomainid', 'techproject');
        }

        // Data was submitted from this form, process it.
        if ($data = data_submitted()) {
            $domainrec->id = $domainid;
            $domainrec->domain = $domain;
            $domainrec->code = clean_param($data->code, PARAM_ALPHANUM);
            $domainrec->label = addslashes(clean_param($data->label, PARAM_CLEANHTML));
            $domainrec->description = addslashes(clean_param($data->description, PARAM_CLEANHTML));
            $DB->update_record('techproject_qualifier', $domainrec);
            redirect(new moodle_url('/mod/techproject/view.php', array('id' => $id, 'view' => 'domains_'.$domain)));
        } else {
            // No data submitted : print the form.
            $params = array('id' => $id, 'what' => 'update');
            $newdomain = new Domain_Form($domain, $domainrec, new moodle_url('/mod/techproject/view.php', $params));
            $newdomain->display();
            return -1;
        }
        break;
    }

    case 'delete': {
        // Deletes domain value **************************************.
        $domainid = required_param('domainid', PARAM_INT);
        $DB->delete_records('techproject_qualifier', array('id' => $domainid));
        break;
    }
}