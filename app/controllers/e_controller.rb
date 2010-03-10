class EController < ApplicationController
  def index
    # process email clicks
    
    s = Sentemail.find_by_pin(params["id"])
    if s.nil?
      redirect_to "/contacts/new"
    else
      s.responded_at = DateTime.now()
      s.save
      @contact = Contact.find(s.contact_id)
      
      v = Volunteer.find_by_sql(["select * from volunteers where contact_id = ? and project_id = ?", s.contact_id, Project.latest.id])[0] 
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
    end
    load_skills_and_types()
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
end
