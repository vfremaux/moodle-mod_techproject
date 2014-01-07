
function selectall(formobj){
	for (i = 0 ; i < formobj.elements.length ; i++){
		if (formobj.elements[i].id && formobj.elements[i].id.match(/^sel/)){
			formobj.elements[i].checked = true;
		}
	}
}

function unselectall(formobj){
	for (i = 0 ; i < formobj.elements.length ; i++){
		if (formobj.elements[i].id && formobj.elements[i].id.match(/^sel/)){
			formobj.elements[i].checked = false;
		}
	}
}

function task_update(elementname){
    if (elementname == 'quoted'){
        $('#quoted').html(document.forms['mform1'].costrate.value * document.forms['mform1'].planned.value);
    }
    if (elementname == 'spent'){
        $('#spent').html(document.forms['mform1'].costrate.value * document.forms['mform1'].used.value);
    }
};

function toggle(i, n, ajax, wwwroot) {
	if ($('#'+n).css('display') == 'none') {
		$('#'+n).css('display', 'block');
		document.images['img'+i].src = wwwroot + '/mod/techproject/pix/p/switch_minus.gif';
    	if (ajax){
            var sUrl = wwwroot+'/mod/techproject/ajax/updatecollapse.php?id='+moodlevars.cmid+'&entity='+moodlevars.view+'&userid='+moodlevars.userid+'&state=0&entryid='+i;
            
            $.get(sUrl, function(data, status){
            	$('#nodediv').html(data);
            });
            
		  	$('#nodediv').html('<center><img src="'+wwwroot+'/pix/i/ajaxloader.gif"></center>');
        }
	} else {
		$('#'+n).css('display', 'none');
		document.images['img' + i].src = wwwroot+'/mod/techproject/pix/p/switch_plus.gif';
		if (ajax){
            var sUrl = wwwroot+'/mod/techproject/ajax/updatecollapse.php?id='+moodlevars.cmid+'&entity='+moodlevars.view+'&userid='+moodlevars.userid+'&state=1&entryid='+i;
            $.get(sUrl, function(data, status){
            	// $('#nodediv').html(data);
            });
        }
	}
}

function toggle_show(i,n,wwwroot) {

	if ($('#'+n).css('display') = 'none') {
		$('#'+n).css('display', 'block');
		document.images['eye' + i].src = wwwroot+'/mod/techproject/pix/p/show.gif';
	} else {
		$('#'+n).css('display', 'none');
		document.images['eye' + i].src = wwwroot+'/mod/techproject/pix/p/hide.gif';
	}
}