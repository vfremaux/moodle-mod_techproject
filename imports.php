<?php

/**
 * Project : Technical Project Manager (IEEE like)
 *
 * @package mod-techproject
 * @category mod
 * @author Valery Fremaux (France) (admin@www.ethnoinformatique.fr)
 * @contributors LUU Tao Meng, So Gerard (parts of treelib.php), Guillaume Magnien, Olivier Petit
 * @version Moodle 2.x
 * @date 2012/09/01
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

require_once($CFG->dirroot.'/mod/techproject/importlib.php');

if (!has_capability('mod/techproject:viewprojectcontrols', $context) && !has_capability('mod/techproject:manage', $context)) {
    print_error(get_string('notateacher','techproject'));
    return;
}

// perform local use cases

/******************************* exports as XML a full project description **************************/

include_once($CFG->libdir.'/uploadlib.php');

echo $pagebuffer;

if ($work == 'doexportall'){
    $xml = techproject_get_full_xml($project, $currentgroupid);
    echo $OUTPUT->heading(get_string('xmlexport', 'techproject'));
    $xml = str_replace('<', '&lt;', $xml);
    $xml = str_replace('>', '&gt;', $xml);
    echo $OUPTUT->box("<pre>$xml</pre>");
    echo $OUTPUT->continue_button("view.php?id={$cm->id}");    
    return;
}
/************************************ clears an existing XSL sheet *******************************/
if ($work == 'loadxsl') {
    $uploader = new upload_manager('xslfilter', false, false, $course->id, true, 0, true);
    $uploader->preprocess_files();
    $project->xslfilter = $uploader->get_new_filename();
    $DB->update_record('techproject', $project);
    if (!empty($project->xslfilter)) {
        $uploader->save_files("{$course->id}/moddata/techproject/{$project->id}");
    }
}
/************************************ clears an existing XSL sheet *******************************/
if ($work == 'clearxsl'){
    include_once "filesystemlib.php";
    $xslsheetname = $DB->get_field('techproject', 'xslfilter', array('id' => $project->id));    
    filesystem_delete_file("{$course->id}/moddata/techproject/{$project->id}/$xslsheetname");
    $DB->set_field('techproject', 'xslfilter', '', array('id' => $project->id));
    $project->xslfilter = '';
}
/************************************ clears an existing XSL sheet *******************************/
if ($work == 'loadcss'){
    $uploader = new upload_manager('cssfilter', false, false, $course->id, true, 0, true);
    $uploader->preprocess_files();
    $project->cssfilter = $uploader->get_new_filename();
    $DB->update_record('techproject', $project);
    if (!empty($project->cssfilter)){
        $uploader->save_files("{$course->id}/moddata/techproject/{$project->id}");
    }
}
/************************************ clears an existing XSL sheet *******************************/
if ($work == 'clearcss'){
    include_once "filesystemlib.php";
    $csssheetname = $DB->get_field('techproject', 'cssfilter', array('id' => $project->id));    
    filesystem_delete_file("{$course->id}/moddata/techproject/{$project->id}/$csssheetname");
    $DB->set_field('techproject', 'cssfilter', '', array('id' => $project->id));
    $project->cssfilter = '';
}

if ($work == 'importdata'){
	$entitytype = required_param('entitytype', PARAM_ALPHA);
    $uploader = new upload_manager('entityfile', true, false, $course->id, false, 0, false);
    $uploader->preprocess_files();
    $uploader->process_file_uploads($CFG->dataroot.'/tmp');
    $file = $uploader->get_new_filepath();
    $data = implode('', file($file));
    techproject_import_entity($project->id, $id, $data, $entitytype, $currentgroupid);
}
/// write output view
echo $OUTPUT->heading(get_string('importsexports', 'techproject'));
echo $OUTPUT->heading(get_string('imports', 'techproject'), '3');
echo $OUTPUT->box_start();
?>    
<form name="importdata" method="post" enctype="multipart/form-data" style="display:block">
<input type="hidden" name="id" value="<?php p($cm->id) ?>" />
<input type="hidden" name="view" value="teacher_load" />
<input type="hidden" name="work" value="importdata" />
<select name="entitytype" />
	<option value="requs"><?php print_string('requirements', 'techproject') ?></option>
	<option value="specs"><?php print_string('specifications', 'techproject') ?></option>
	<option value="tasks"><?php print_string('tasks', 'techproject') ?></option>
	<option value="deliv"><?php print_string('deliverables', 'techproject') ?></option>
</select>
<?php echo $OUTPUT->help_icon('importdata', 'techproject') ?>
<input type="file" name="entityfile" />
<input type="submit" name="go_btn" value="<?php print_string('import', 'techproject') ?>" />
</form>
<?php  
echo $OUTPUT->box_end();
echo $OUTPUT->heading(get_string('exports', 'techproject'), '3');
echo $OUTPUT->box_start();
?>
<ul>
<li><a href="?work=doexportall&amp;id=<?php p($cm->id) ?>"><?php print_string('exportallforcurrentgroup', 'techproject') ?></a></li>
<?php
if (has_capability('mod/techproject:manage', $context)){
?>
<li><a href="Javascript:document.forms['export'].submit()"><?php print_string('loadcustomxslsheet', 'techproject') ?></a>
<form name="export" method="post" enctype="multipart/form-data" style="display:inline">
<input type="hidden" name="id" value="<?php p($cm->id) ?>" />
<input type="hidden" name="view" value="teacher_load" />
<input type="hidden" name="work" value="loadxsl" />
<?php
    if (@$project->xslfilter){
        echo '('.get_string('xslloaded', 'techproject').": {$project->xslfilter}) ";
    }
    else{
        echo '('.get_string('xslloaded', 'techproject').': '.get_string('default', 'techproject').') ';
    }
?>
<input type="file" name="xslfilter" />
</form>
<a href="view.php?id=<?php p($cm->id)?>&amp;work=clearxsl"><?php print_string('clearcustomxslsheet', 'techproject') ?></a>
</li>
<?php
}
?>
<?php
if (has_capability('mod/techproject:manage', $context)){
?>
<li><a href="Javascript:document.forms['exportcss'].submit()"><?php print_string('loadcustomcsssheet', 'techproject') ?></a>
<form name="exportcss" method="post" enctype="multipart/form-data" style="display:inline">
<input type="hidden" name="id" value="<?php p($cm->id) ?>" />
<input type="hidden" name="view" value="teacher_load" />
<input type="hidden" name="work" value="loadcss" />
<?php
    if (@$project->cssfilter){
        echo '('.get_string('cssloaded', 'techproject').": {$project->cssfilter}) ";
    }
    else{
        echo '('.get_string('cssloaded', 'techproject').': '.get_string('default', 'techproject').') ';
    }
?>
<input type="file" name="cssfilter" />
</form>
<a href="view.php?id=<?php p($cm->id)?>&amp;work=clearcss"><?php print_string('clearcustomcsssheet', 'techproject') ?></a>
</li>
<?php
}
?>
<li><a href="xmlview.php?id=<?php p($cm->id) ?>" target="_blank"><?php print_string('makedocument', 'techproject') ?></a></li>
</ul>
<?php
echo $OUTPUT->box_end();
?>
