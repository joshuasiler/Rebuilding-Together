class ContactsController < ApplicationController
  
  def new
    @contact = Contact.new(params[:contact])
    @skills = Skill.find(:all)
    @ctypes = ContactContacttype.find(:all)
  end
  
  def create
    @contact = Contact.new(params[:contact])
    if @contact.save
      #congrats registered
      params[:skills].each { |value|
	  s = ContactSkill.new
	  s.contact_id = @contact.id
	  s.skill_id = value
	  s.save
	} unless params[:skills].blank?
        params[:ctypes].each { |value|
	  s = ContactContacttype.new
	  s.contact_id = @contact.id
	  s.contacttype_id = value
	  s.save
	} unless params[:ctypes].blank?
      redirect_to "/contacts/thanks/"+@contact.id.to_s
    else
      # collect errors in flash and rerender
      
      @skills = Skill.find(:all)
      @ctypes = ContactContacttype.find(:all)
      render :new
    end
  end
  
  def thanks
    @contact = Contact.find(params[:id])
  end
end

