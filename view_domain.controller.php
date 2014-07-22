<?php

/**
* Master Controler for all domains
*/

// Security.

if (!defined('MOODLE_INTERNAL')) {
    die("You cannot directly invoke this script");
}

switch($action){
    /*************************************** adds a new company ***************************/
    case 'add':
        if ($data = data_submitted()) {
        // if there is some error
            if ($data->code == '') {
                $returnurl = new moodle_url('/mod/techproject/view.php', array('id' => $id, 'view' => 'domains_'.$domain, 'action' => 'add', 'view' => 'domains_'.$domain));
                print_error('err_code', 'techproject', '', $returnurl);
            } elseif ($data->label == '') {
                $returnurl = new moodle_url('/mod/techproject/view.php', array('id' => $id, 'view' => 'domains_'.$domain, 'action' => 'add', 'view' => 'domains_'.$domain));
                print_error('err_value', 'techproject', '', $returnurl);
            } else {
                //data was submitted from this form, process it
                $domainrec->projectid = $scope;
                $domainrec->domain = $domain;
                $domainrec->code = clean_param($data->code, PARAM_ALPHANUM);
                $domainrec->label = clean_param($data->label, PARAM_CLEANHTML);
                $domainrec->description = clean_param($data->description, PARAM_CLEANHTML);

                if ($DB->get_record('techproject_qualifier', array('domain' => $domain, 'code' => $data->code, 'projectid' => $scope))){
                    $returnurl = new moodle_url('/mod/techproject/view.php', array('id' => $id, 'action' => 'add', 'view' => 'domains_'.$domain));
                    print_error('err_codeexists', 'techproject', '', $returnurl);
                } else {
                    if (!$DB->insert_record('techproject_qualifier', $domainrec)){
                        print_error('errorinsertqualifier', 'techproject');
                    }
                    redirect(new moodle_url('/mod/techproject/view.php', array('id' => $id, 'view' => 'domains_'.$domain)));
                }
            }
        } else {
            $default->code = 'XXX';
            $default->label = '';
            $default->description = '';
            $newdomain = new Domain_Form($domain, $default, "{$CFG->wwwroot}/mod/techproject/view.php?id={$id}&what=add");    
            $newdomain->display();
            return -1;
        }
        break;
    /********************************** Updates a domain value **************************************/
    case 'update':
        $domainid = required_param('domainid', PARAM_INT);

        // Check the company
        if (!$domainrec = $DB->get_record('techproject_qualifier', array('id' => $domainid))) {
            print_error('errorinvalidedoomainid', 'techproject');
        }

        // data was submitted from this form, process it
        if ($data = data_submitted()){
            $domainrec->id = $domainid;
            // $domainrec->projectid = 0;
            $domainrec->domain = $domain;
            $domainrec->code = clean_param($data->code, PARAM_ALPHANUM);
            $domainrec->label = clean_param($data->label, PARAM_CLEANHTML);
            $domainrec->description = clean_param($data->description, PARAM_CLEANHTML);
            if (!$DB->update_record('techproject_qualifier', $domainrec)){
                print_error('errorupdatedomainvalue', 'techproject');
            }
            redirect(new moodle_url('/mod/techproject/view.php', array('id' => $id, 'view' = 'domains_'.$domain)));
        } else {
            //no data submitted : print the form
            $newdomain = new Domain_Form($domain, $domainrec, "{$CFG->wwwroot}/mod/techproject/view.php?id={$id}&what=update");
            $newdomain->display();
            return -1;
        }
        break;
    /********************************** deletes domain value **************************************/
    case 'dodelete':
        $domainid = required_param('domainid', PARAM_INT);
        if (!$DB->delete_records('techproject_qualifier', array('id' => $domainid))){
            print_error('errordeletedomainvalue', 'techproject');
        }

        break;
}    