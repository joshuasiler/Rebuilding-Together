require 'data_grid'
require 'cgi'
require 'faster_csv'

# "customer support lookup" could be name, company, email, phone number
class ManageController < ApplicationController
  layout "manage"
  
  def add_edit_house
    if params[:id].blank?
      @house = House.new
      @contact = Contact.new
    else
      @house = House.find(params[:id])
      @contact = Contact.find(@house.contact_id)
    end
  end
  
  def save_update_house
    if params[:house][:id].blank?
      @house = House.new(params[:house])
			params[:contact][:is_homecontact] = 1
      dup = Contact.new(params[:contact]).find_duplicates
      if dup.blank?
				@contact = Contact.new(params[:contact])
				test = @contact.save
      else
				test = Contact.update(dup,params[:contact])
				@contact = Contact.find(dup)
      end
      if test
				@house.contact_id = @contact.id
				if @house.save
					flash[:message] = "House successfully added to project."
					redirect_to "/manage/index"
				else
					render :add_edit_house
				end
      else
				render :add_edit_house
      end
    else
      @house = House.update(params[:house][:id], params[:house])
      @contact = Contact.update(params[:contact][:id], params[:contact])
      @house.save
      if @contact.save
				flash[:message] = "House updated"
				redirect_to "/manage/index"
      else
				puts @contact.inspect
				render :add_edit_house
      end
    end
  end
  
  def list_houses
    @houses = House.find(:all, {:conditions => "project_id = #{Project.latest.id}", :include => :contact, :order => "house_number asc"})
  end
  
  def list_volunteers
    myconditions = "volunteers.project_id = #{Project.latest.id}"
    if params[:id] == "not_assigned"
      myconditions += " and isnull(house_id)"
    elsif params[:id] == "assigned"
      myconditions += " and not isnull(house_id)"
    elsif !params[:search].blank?
      # not safe, but admins won't hack their own site (hopfeully)
      myconditions += " and (contacts.first_name like '%#{params[:search]}%' or contacts.last_name like '%#{params[:search]}%' or contacts.email like '%#{params[:search]}%' or contacts.company_name like '%#{params[:search]}%' )"
    end
    offset = 0 
    unless params[:page].blank?
      offset = (params[:page].to_i-1) * 10
    end
    @volunteer_count = Volunteer.count(:conditions => myconditions, :include => [{:contact => :skills},:house])
    @volunteers = Volunteer.find(:all, {:conditions => myconditions, :include => [{:contact => :skills},:house], :order => "contacts.last_name asc", :limit => 10, :offset => offset})
  end
  
  def assign_volunteer
      v = Volunteer.find(params[:id])
      v.house_id = House.find_by_house_number(params[:house][:id]).id
      v.save
      render :partial => "assign", :object => v
  end
  
  def view_house
    @house = House.find(params[:id])
    @contact = Contact.find(@house.contact_id)
    @volunteers = Volunteer.find(:all,{:conditions => "house_id = #{@house.id}",:include => :contact})
  end
  
  def remove_house
    house = House.find(params[:id])
    house.destroy
    flash[:message] = "House removed from project."
    redirect_to "/manage/index"
  end
  
  def index
    @unassigned = Volunteer.count_by_sql("select count(*) from volunteers where project_id = #{Project.latest.id} and isnull(house_id)")
    @assigned = Volunteer.count_by_sql("select count(*) from volunteers where project_id = #{Project.latest.id} and not isnull(house_id)")
    @history = Volunteer.find_by_sql("SELECT substring(created_at, 1,10) AS dd, COUNT(id) as cnt FROM Volunteers GROUP BY dd")
  end
  
end
