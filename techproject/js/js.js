
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
        spanelm = document.getElementById('quoted');
        spanelm.innerHTML = document.forms['mform1'].costrate.value * document.forms['mform1'].planned.value;
    }
    if (elementname == 'spent'){
        spanelm = document.getElementById('spent');
        spanelm.innerHTML = document.forms['mform1'].costrate.value * document.forms['mform1'].used.value;
    }
};

function toggle(i, n, ajax, wwwroot) {

	var toggle_callback = {
	  success: function(o) {
	  	nodediv = document.getElementById('sub'+i);
	  	nodediv.innerHTML = o.responseText;
	  },
	  failure: function(o) {},	  
	  argument:[i,n]
	};

	e = document.getElementById(n);

	if (e.style.display == 'none') {
		e.style.display = 'block';
		document.images['img'+i].src = wwwroot + '/mod/techproject/pix/p/switch_minus.gif';
    	if (ajax){
            var sUrl = wwwroot+'/mod/techproject/ajax/updatecollapse.php?id='+moodlevars.cmid+'&entity='+moodlevars.view+'&userid='+moodlevars.userid+'&state=0&entryid='+i;
            var transaction = YAHOO.util.Connect.asyncRequest('GET', sUrl, toggle_callback, null);
		  	nodediv = document.getElementById('sub'+i);
		  	nodediv.innerHTML = '<center><img src="'+wwwroot+'/pix/i/ajaxloader.gif"></center>';
        }
	} else {
		e.style.display = 'none';
		document.images['img' + i].src = wwwroot+'/mod/techproject/pix/p/switch_plus.gif';
		if (ajax){
            var sUrl = wwwroot+'/mod/techproject/ajax/updatecollapse.php?id='+moodlevars.cmid+'&entity='+moodlevars.view+'&userid='+moodlevars.userid+'&state=1&entryid='+i;
            var transaction = YAHOO.util.Connect.asyncRequest('GET', sUrl, null, null);
        }
	}
}

function toggle_show(i,n,wwwroot) {

	e = document.getElementById(n);
	if (e.style.display == 'none') {
		e.style.display = 'block';
		document.images['eye' + i].src = wwwroot+'/mod/techproject/pix/p/show.gif';
	} else {
		e.style.display = 'none';
		document.images['eye' + i].src = wwwroot+'/mod/techproject/pix/p/hide.gif';
	}
}