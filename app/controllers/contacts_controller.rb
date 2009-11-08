class ContactsController < ApplicationController
  
  def new
    @contact = Contact.new(params[:contact])
    @skills = Skill.find(:all)
    @ctypes = ContactType.find(:all)
  end
  
  def create
    @contact = Contact.new(params[:contact])
    if @contact.save
      #congrats registered
      redirect_to "/contacts/thanks/"+@contact.id.to_s
    else
      # collect errors in flash and rerender
      @skills = Skill.find(:all)
      @ctypes = ContactType.find(:all)
      render :new
    end
  end
  
  def thanks
    @contact = Contact.find(params[:id])
  end
end

