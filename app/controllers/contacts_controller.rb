class ContactsController < ApplicationController

  USER_ID, PASSWORD_MD5 = "admin", "5528a49e2109b087566829ddd2c2e295"
  # Require authentication only for edit and delete operation
  before_filter :authenticate, :only => [ :edit, :update ]

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

  def edit
    @contact = Contact.find(params[:id], :include => [ :skills, :contacttypes ])
    load_skills_and_types()
    @skills_checked_ids = @contact.skills.map {|s| s.id}
    @ctypes_checked_ids = @contact.contacttypes.map {|t| t.id }
  end
  
  def update
    @contact = Contact.find(params[:id], :include => [ :skills, :contacttypes ])
    if @contact.update_attributes(params[:contact])
      redirect_to "/contacts/thanks/"+@contact.id.to_s
    else
      # collect errors in flash and rerender
      load_skills_and_types(params)
      render :edit
    end    
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
    end
  end

  def authenticate
    require 'digest/md5'
    authenticate_or_request_with_http_basic do |id, password| 
      id == USER_ID && Digest::MD5.hexdigest(password) == PASSWORD_MD5
    end
  end

end

