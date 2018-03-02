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

// Master controller.

$result = 0;

$scope = optional_param('scope', 0, PARAM_INT);
$domain = str_replace('domains_', '', $view);

if (!empty($action)) {
    $result = include_once($CFG->dirroot.'/mod/techproject/view_domain.controller.php');
}

if ($result == -1) {
    // If controller already output the screen we might jump.
    return -1;
}

echo $pagebuffer;

echo '<table width="100%" class="generaltable">';
echo '<tr>';
echo '<td align="left">';
// Print the scopechanging.
echo '<form name="changescopeform">';
echo '<input type="hidden" name="view" value="domains_'.$domain.'" />';
echo '<input type="hidden" name="id" value="'.$cm->id.'" />';
$scopeoptions[0] = get_string('sharedscope', 'techproject');
$scopeoptions[$project->id] = get_string('projectscope', 'techproject');
echo html_writer::select($scopeoptions, 'scope', $scope, array(), array('onchange' => 'forms[\'changescopeform\'].submit();'));
echo '</from></td><td align="right">';
$addurl = new moodle_url('/mod/techproject/view.php', array('view' => 'domains_'.$domain, 'id' => $id, 'what' => 'add'));
echo '<a href="'.$addurl.'">'.get_string('addvalue', 'techproject').'</a>';
echo '</td>';
echo '</tr>';
echo '</table>';

$domainvalues = techproject_get_domain($domain, null, 'all', $scope, 'code');

if (!empty($domainvalues)) {
    $table = new html_table();
    $table->head = array(get_string('code', 'techproject'),
                         get_string('label', 'techproject'),
                         get_string('description'),
                         '');
    $table->style = array('', '', '', '');
    $table->width = "100%";
    $table->align = array('left', 'left', 'left', 'right');
    $table->size = array('10%', '20%', '50%', '20%');
    $table->data = array();

    foreach ($domainvalues as $value) {
        $view = array();
        $view[] = $value->code;
        $view[] = format_string($value->label);
        $view[] = format_string($value->description);

        $cmdicon = $OUTPUT->pix_icon('t/edit', get_string('edit'));
        $params = array('view' => 'domains_'.$domain, 'id' => $id, 'what' => 'update', 'domainid' => $value->id);
        $cmdurl = new moodle_url('/mod/techproject/view.php', $params);
        $commands = '<a href="'.$cmdurl.'">'.$cmdicon.'</a>';

        $cmdicon = $OUTPUT->pix_icon('t/delete', get_string('delete'));
        $params = array('view' => 'domains_'.$domain, 'id' => $id, 'what' => 'delete', 'domainid' => $value->id);
        $cmdurl = new moodle_url('/mod/techproject/view.php', $params);
        $commands .= ' <a href="'.$cmdurl.'">'.$cmdicon.'</a>';

        $view[] = $commands;
        $table->data[] = $view;
    }
    techproject_print_project_table($table);
} else {
    echo $OUTPUT->notification(get_string('novaluesindomain', 'techproject'));
}
