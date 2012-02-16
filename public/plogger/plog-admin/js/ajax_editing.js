function makeEditable(id){
	Event.observe(id, 'click', function(){edit($(id))}, false);
	Event.observe(id, 'mouseover', function(){showAsEditable($(id))}, false);
	Event.observe(id, 'mouseout', function(){showAsEditable($(id), true)}, false);
}

function showAsEditable(obj, clear){
	if (!clear){
		Element.addClassName(obj, 'editable');
	}else{
		Element.removeClassName(obj, 'editable');
	}
}

function edit(obj){
	Element.hide(obj);

	var textarea ='<div id="' + obj.id + '_editor"><textarea id="' + obj.id + '_edit" name="' + obj.id + '" rows="4" cols="60">'+ obj.innerHTML.replace(/&nbsp;/gi,' ') + '</textarea>';

	var button = '<br /><input id="' + obj.id + '_save" type="button" value="Save" /> <input id="' + obj.id + '_cancel" type="button" value="Cancel" /></div>';

	new Insertion.After(obj, textarea+button);

	Event.observe(obj.id+'_save', 'click', function(){saveChanges(obj)}, false);
	Event.observe(obj.id+'_cancel', 'click', function(){cleanUp(obj)}, false);

	document.getElementById(obj.id+'_edit').focus();

}

function cleanUp(obj, keepEditable){
	Element.remove(obj.id+'_editor');
	Element.show(obj);
	if (!keepEditable) showAsEditable(obj, true);
}

function saveChanges(obj){
	var new_content = encodeURIComponent($F(obj.id+'_edit'));
	new_content = new_content.replace(/%C2%A0/gi, '%20');

	obj.innerHTML = '<img src="images/loading.gif" alt="loading" height="32" width="32" />';
	cleanUp(obj, true);

	var success = function(t){editComplete(t, obj);}
	var failure = function(t){editFailed(t, obj);}

	var url = 'plog-rpc.php';
	var pars = 'action=update&field=' + obj.id + '&content=' + new_content;
	var myAjax = new Ajax.Request(url, {method:'post',
		postBody:pars, onSuccess:success, onFailure:failure});
}

function editComplete(t, obj){
	obj.innerHTML = t.responseText;
	showAsEditable(obj, true);
}

function editFailed(t, obj){
	obj.innerHTML = 'Sorry, the update failed.';
	cleanUp(obj);
}
