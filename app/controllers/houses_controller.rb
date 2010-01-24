class HousesController < ApplicationController
  layout 'mainsite'
  
  def initialize
    @display_columns = %w(address homeowner house_captain_1 house_captain_2)
  end

  def index
    @grid = DataGrid.new :grid
    @grid.configure do |g|
      g.get_data do |state|
        House.find(:all, :include => [:project, :contact])
      end

      g.get_columns do |state, house|
        @display_columns.collect do |col|
          [col] << case col 
                   when "address"
                     house.contact.address_1
                   when "homeowner"
                     house.contact.last_name + ", " + house.contact.first_name
                   when "house_captain_1"
                     ""
                   when "house_captain_2"
                     ""
                   end
        end
      end
    end
  end
  
  def view
    @house = House.find(params[:id])
    @contact = Contact.find(@house.contact_id)
  end
  def new
    @house = House.new
  end
  def edit
    @house = House.find(params[:id])
    @contact = Contact.find(@house.contact_id)
  end
  def update
    @house = House.update(params[:house][:id], params[:house])
    @contact = Contact.update(params[:contact][:id], params[:contact])
    redirect_to "/houses/view/" + params[:house][:id].to_s
  end
end
