class ContactsController < ApplicationController
  layout 'mainsite'
  
  def new
    @contact = Contact.new(params[:contact])
    @contact.is_active = true
    load_skills_and_types()
  end
  
  def create
    @contact = Contact.new(params[:contact])
    if @contact.save
      #congrats registered
      
      (params[:skills] || {}).each { |value|
        s = ContactSkill.new
        s.contact_id = @contact.id
        s.skill_id = value
        s.save
	    }
        
      (params[:ctypes] || {}).each { |value|
        s = ContactContacttype.new
        s.contact_id = @contact.id
        s.contacttype_id = value
        s.save
    	}

      redirect_to "/contacts/thanks/"+@contact.id.to_s
    else
      # collect errors in flash and rerender
      load_skills_and_types()
      render :new
    end
  end
  
  def thanks
    @contact = Contact.find(params[:id])
  end

private
  def load_skills_and_types()
    @skills = Skill.find(:all)
    @ctypes = Contacttype.find(:all, :conditions => "signup_form_display_order > 0", :order => "signup_form_display_order ASC")
  end

end

