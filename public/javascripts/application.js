// Place your application-specific JavaScript functions and classes here
// This file is automatically included by javascript_include_tag :defaults
$(document).ready(function() {
	var tform = $('#new_inquiry');
	if ( tform.find('.message_field').length != 0 ) {
		// found inquiry form
		tform.find('.message_field').css('display', 'none');

		tform.find('.message_field').before('<div class="field"><label for="formadd_group">Group</label><input id="formadd_group" name="group" size="30" type="text" /></div>')
		tform.find('.message_field').before('<div class="field"><label for="formadd_skills">Skills</label><input id="formadd_skills" name="skills" size="60" type="text" /></div>')
		tform.find('.message_field').before('<div class="field"><label for="formadd_past">Past Participation?</label><input id="formadd_past" name="past" size="60" type="text" /></div>')
		tform.find('.message_field').before('<div class="field" style="clear:left; margin-top: 20px;"><label for="formadd_message">Message</label><textarea cols="40" id="formadd_message" name="message" rows="8"></textarea></div>')
		// put all these new fields into the one message on submit
		tform.submit( function() { 
			$('textarea#inquiry_message').val("Group:" + $('#formadd_group').val() +
					"\nSkills:" + $('#formadd_skills').val() +
					"\nPast Participation:" + $('#formadd_past').val() +
					"\nMessage:" + $('#formadd_message').val()
					);
		 } );
	} 
});