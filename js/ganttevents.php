<?php

include '../../../config.php';

$id = required_param('id', PARAM_INT); // course
$lastmodified = filemtime("ganttevents.php");
$lifetime = 1800;

require_course_login($id);

$projectid = required_param('projectid', PARAM_INT);

header("Content-type: application/x-javascript; charset: utf-8");  // Correct MIME type
header("Last-Modified: " . gmdate("D, d M Y H:i:s", $lastmodified) . " GMT");
header("Expires: " . gmdate("D, d M Y H:i:s", time() + $lifetime) . " GMT");
header("Cache-control: max_age = $lifetime");
header("Pragma: ");

?>

// Gant events provide Ajax triggers to transmit task changes to the techproject storage

gantt_handler_onTaskChangeBounds = function(task){

	var sdate = task.getEST();
	var syear = date.getFullYear();
	var smonth = date.getMonth();
	var sday = date.getDate();

	var edate = task.getFinishDate();
	var eyear = date.getFullYear();
	var emonth = date.getMonth();
	var eday = date.getDate();

	var courseid = '<?php echo $id ?>';
	var projectid = '<?php echo $projectid ?>';
	var taskid = task.getId();
    
	var params = "id="+courseid+
				 "&projectid="+projectid+
				 "&taskid="+taskid+
				 "&event=taskchangebounds"+
				 "&arg1="+syear+
				 ","+smonth+
				 ","+sday+
				 "&arg2="+eyear+
				 ","+emonth+
				 ","+eday;
    var url = "<?php echo $CFG->wwwroot; ?>/mod/techproject/ajax/ganttevents.php?"+params;

	$.get(url, function(data, status){
	});        
};

gantt_handler_onTaskChangeAttributes = function(task){

	var newname = task.getName();
	var completion = task.getPercentCompleted();
	var courseid = '<?php echo $id ?>';
	var projectid = '<?php echo $projectid ?>';
	var taskid = task.getId();

	var params = "id="+courseid+
				 "&projectid="+projectid+
				 "&taskid="+taskid+
				 "&event=taskupdateattributes"+
				 "&arg1="+encodeURIComponent(newname)+
				 "&arg2="+completion;
    var url = "<?php echo $CFG->wwwroot; ?>/mod/techproject/ajax/ganttevents.php?"+params;

	$.get(url, function(data, status){
		if (task.parentTask){
			gantt_handler_onTaskRefresh(task.parentTask);
		}
	});
	
};

gantt_handler_onTaskDelete = function(task){

	taskid = task.getId();
	var courseid = '<?php echo $id ?>';
	var projectid = '<?php echo $projectid ?>';
    
	var params = "id="+courseid+"&projectid="+projectid+"&taskid="+taskid+"&event=taskdelete";
    var url = "<?php echo $CFG->wwwroot; ?>/mod/techproject/ajax/ganttevents.php?"+params;

	$.get(url, function(data, status){
	});
};

gantt_handler_onTaskRefresh = function(task){

	var courseid = '<?php echo $id ?>';
	var projectid = '<?php echo $projectid ?>';
	var taskid = task.getId();

	var params = "id="+courseid+
				 "&projectid="+projectid+
				 "&taskid="+taskid+
				 "&event=taskget"
    var url = "<?php echo $CFG->wwwroot; ?>/mod/techproject/ajax/ganttevents.php?"+params;

	$.get(url, function(data,status){
		eval(data);
		task.setPercentCompleted(obj.done);
	});
};

gantt_handler_onTaskInsert = function(task){

	var name = task.getName();
	var date = task.getEST();
	var y = date.getFullYear();
	var m = date.getMonth();
	var d = date.getDate();
	var duration = task.getDuration();
	var parentid = task.getParentTaskId();
	var completion = task.getPercentCompleted();
	var courseid = '<?php echo $id ?>';
	var projectid = '<?php echo $projectid ?>';
	var taskid = 0;

	var params = "id="+courseid+
				 "&projectid="+projectid+
				 "&taskid="+taskid+
				 "&event=taskinsert"+
				 "&arg1="+encodeURIComponent(name)+
				 "&arg2="+y+','+m+','+d+
				 "&arg3="+duration+
				 "&arg4="+completion+
				 "&arg5="+parentid;
    var url = "<?php echo $CFG->wwwroot; ?>/mod/techproject/ajax/ganttevents.php?"+params;

	$.get(url, function(data, status){
		location.reload();
	});
};
