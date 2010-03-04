class Notification < ActionMailer::Base
  
  def volunteer_notification(contact,pin)
    @from = "Rebuilding Together <donotreply@rebuildingtogetherportland.org>"
    @recipients = contact.email 
#    @recipients = contact.first + " " + contact.last + "<" + contact.email  + ">"
    @subject = "Call for Volunteers - Rebuilding Together 2010"
    @pin = pin
		@content_type = "text/html"
    @email = contact.email
    unless contact.first_name.blank?
      @first = contact.first_name
    else
      @first = "Volunteer"
    end
    
  end

end
