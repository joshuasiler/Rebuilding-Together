class ContactsController < ApplicationController

  layout 'mainsite'

  def new
    @contact = Contact.new(params[:contact])
    @contact.is_active = true
    load_skills_and_types()
  end
  
  def create
    dup = Contact.new(params[:contact]).find_duplicates
    if dup.blank?
      @contact = Contact.new(params[:contact])
      test = @contact.save
    else
      test = Contact.update(dup,params[:contact])
      @contact = Contact.find(dup)
    end
    if test
      v = Volunteer.find_by_sql(["select * from volunteers where contact_id = ? and project_id = ?", @contact.id, Project.latest.id])[0] 
      if v.nil?
	v = Volunteer.new
      end
      v.contact_id = @contact.id
      v.project_id = Project.latest.id
      v.group_name = @contact.company_name
      if @contact.est_group_size.blank?
	v.number_of_people = 1
      else
	v.number_of_people = @contact.est_group_size
      end
      v.save
      #congrats registered
      redirect_to "/contacts/thanks/"+@contact.id.to_s
    else
      # collect errors in flash and rerender
      load_skills_and_types(params)
      render :new
    end
  end
  
  def thanks
    @contact = Contact.find(params[:id])
  end
  
  def process_optout
    @contact = Contact.find_by_email(params[:email])
    if @contact.nil?
      flash[:message] = "That email is not found. Please reenter your address and try again."
    else
      @contact.optout = 1
      @contact.save
      flash[:message] = "You have been unsubscribed."
    end
    render :optout
  end

private
  def load_skills_and_types(params = nil)
    @skills = Skill.find(:all)
    @ctypes = Contacttype.find(:all, :conditions => "signup_form_display_order > 0", :order => "signup_form_display_order ASC")
    if (params)
      @skills_checked_ids = (params[:contact][:skill_ids] || []).map {|i| i.to_i}
      @ctypes_checked_ids = (params[:contact][:contacttype_ids] || []).map {|i| i.to_i}
    else
      @skills_checked_ids = []
      @ctypes_checked_ids = []
      #default "normal volunteer" contacttype for new records
      #todo -- don't hardcode this value! Add "is_default_for_new_contacts" flag to database or something like that.
      @ctypes_checked_ids.push 12
    end
  end

end

