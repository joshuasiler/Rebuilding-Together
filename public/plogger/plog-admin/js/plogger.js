function checkAll(form) {
	for (var i=0;i<form.elements.length;i++) {
		var e = form.elements[i];
		if ( (e.name != 'allbox') && (e.type=='checkbox') && (!e.disabled) && (e.name.substr(0,14) != 'allow_comments')) {
			e.checked = form.allbox.checked;
		}
	}
}

function checkToggle(form) {
	for (var i=0;i<form.elements.length;i++) {
		var e = form.elements[i];
		if ( (e.name != 'allbox') && (e.type=='checkbox') && (!e.disabled) && (e.name.substr(0,14) != 'allow_comments')) {
			if (e.checked == true) {
				e.checked = false;
			} else {
				e.checked = true;
			}
		}
	}
}

function ThumbPreviewPopup(page) {
	var winl = (screen.width-400)/2;
	var wint = (screen.height-400)/2;
	var settings  ='height='+'400'+',';
	settings +='width='+'410'+',';
	settings +='top='+wint+',';
	settings +='left='+winl+',';
	settings +='scrollbars=no,';
	settings +='location=no,';
	settings +='menubar=no,';
	settings +='toolbar=no,';
	settings +='resizable=yes';
	OpenWin = this.open(page, "Preview", settings);
}

function focus_first_input() {
	fields = document.getElementsByTagName('input');
	if (fields.length > 0) {
		fields[0].focus();
	}
}

function updateThumbPreview(selectObj) {
	var thumb = selectObj.options[selectObj.selectedIndex].style.backgroundImage;
	selectObj.style.backgroundImage = thumb;
}

var importThumbCounter = 0;

function onImportThumbComplete(request) {
	var picDic = 'pic_' + importThumbs[importThumbCounter];
	Element.update(picDic,request.responseText);
	var progress = (importThumbCounter + 1)/ importThumbs.length * 100;
	Element.update('progress',Math.round(progress) + '%');
	if (importThumbCounter < importThumbs.length) {
		importThumbCounter++;
		requestImportThumb();
	}
};

function requestImportThumb() {
	new Ajax.Request('plog-thumb.php', {method: 'get', onComplete: onImportThumbComplete, parameters: 'img=' + importThumbs[importThumbCounter]});
};

function checkArchive(fileInput) {

	// check the extension of the chosen file, if it is a zip file
	// we want to disable the caption and description fields because
	// these are going to be set on the import page.

	var filePath = fileInput.value;
	var fileParts = new Array();
	var zipAlert = document.getElementById('zip-alert');

	fileParts = filePath.split('.');
	var fileExtension = fileParts[fileParts.length-1];

	if (fileExtension.toLowerCase() == 'zip') {
		document.getElementById('caption').value = '';
		document.getElementById('description').value = '';
		document.getElementById('caption').disabled = true;
		document.getElementById('description').disabled = true;
		document.getElementById('caption').style.background = "#fafafa";
		document.getElementById('description').style.background = "#fafafa";
		if (zipAlert != null) {
			zipAlert.style.display = '';
		}
	} else {
		document.getElementById('caption').disabled = false;
		document.getElementById('description').disabled = false;
		document.getElementById('caption').style.background = "#fff";
		document.getElementById('description').style.background = "#fff";
		if (zipAlert != null) {
			zipAlert.style.display = 'none';
		}
	}

}

function toggle(obj) {
	var objarray = obj.split(', ');
	while (objarray.length > 0) {
		var el = document.getElementById(objarray.shift());
		if ( el.style.display != 'none' ) {
			el.style.display = 'none';
		} else {
			el.style.display = '';
		}
	}

};