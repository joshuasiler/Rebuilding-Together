class Notification < ActionMailer::Base
  
  def volunteer_notification(contact)
    @from "donotreply@rebuildingtogetherportland.org"
    @recipients contact.email
    @subject = "Call for Volunteers - Rebuilding Together 2010"
    
    @email = contact.email
    unless contact.first_name.blank?
      @first = contact.first_name
    else
      @first = "Volunteer"
    end
    
  end

end
