
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