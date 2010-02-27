require 'data_grid'
require 'cgi'
require 'faster_csv'

# "customer support lookup" could be name, company, email, phone number
class ManageController < ApplicationController
  layout "manage"

  def house_captains

  end
  
  def volunteer_search 
   
  end

  def assign_volunteers
   
  end

  def index
  end

  def add
  end

  def update
  end
  
  def download
    
    list = contact_data(Conditions.from_param(request["cond"]))

    csv_string = FasterCSV.generate do |csv|
      columns = Contact.content_columns.collect { |c| c.name } + @csv_columns
      csv << Contact.content_columns.collect { |c| c.human_name } + @csv_columns

      list.each do |record|
        csv << columns.collect { |col| contact_column(record, col) }
      end
    end

    send_data(csv_string,
              :type => 'text/csv; charset=utf-8; header=present',
              :filename => "contacts.csv")
  end
  
  def add_edit_house
    if params[:id].blank?
      @house = House.new
    else
      @house = House.find(params[:id])
      @contact = Contact.find(@house.contact_id)
    end
  end
  
  def save_update_house
    if params[:house][:id].blank?
      @house = House.new(params[:house])
      @contact = Contact.new(params[:contact])
      if @contact.save
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
	render :add_edit_house
      end
    end
  end
  
  def list_houses
    @houses = House.find(:all, {:conditions => "project_id = #{Project.latest.id}", :include => :contact, :order => "created_at desc"})
  end
  
  def list_volunteers
    @volunteers = Volunteer.find(:all, {:conditions => "project_id = #{Project.latest.id}", :include => [:contact,:house], :order => "created_at desc"})
  end
  
  def assign_volunteer
      v = Volunteer.find(params[:id])
      v.house_id = House.find_by_house_number(params[:house][:id]).id
      v.save
      render :partial => "assign", :object => v
  end
  
end
