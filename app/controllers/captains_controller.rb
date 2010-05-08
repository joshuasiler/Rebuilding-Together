require 'data_grid'
require 'cgi'
require 'faster_csv'

# "customer support lookup" could be name, company, email, phone number
class CaptainsController < ApplicationController
  layout "captains"
  
  def index
    redirect_to :action => :list_houses  
  end
  
  def list_houses
    offset = 0 
    unless params[:page].blank?
      offset = (params[:page].to_i-1) * 20
    end
    @houses_count = House.count(:conditions => "project_id = #{Project.latest.id}", :include => :contact)
    @houses = House.find(:all, {:conditions => "project_id = #{Project.latest.id}", :include => :contact, :order => "house_number asc", :limit => 20, :offset => offset})
  end
  
  def view_house
    @house = House.find(params[:id])
    @contact = Contact.find(@house.contact_id)
    @volunteers = Volunteer.find(:all,{:conditions => "house_id = #{@house.id}",:include => :contact, :order => "is_housecaptain desc"})
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
