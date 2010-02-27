class HousesController < ApplicationController
  layout 'mainsite'
  
  def initialize
    @display_columns = [[:address, lambda { |r| r.contact.address_1 }],
                        [:homeowner, lambda { |r| r.contact.last_name + ", " + r.contact.first_name }],
                        [:house_captain_1, lambda { |r| "" }],
                        [:house_captain_2, lambda { |r| "" }]]
  end

  def index
    @grid = DataGrid.new :grid
    @grid.configure do |g|
      g.get_data do |state|
        House.find(:all, :include => [:project, :contact])
      end

      g.get_columns do |state, house|
        @display_columns.collect do |col, fn|
          [col.to_s, fn.call(house)]
        end
      end
    end
  end
  
  def view
    @house = House.find(params[:id])
    @contact = Contact.find(@house.contact_id)
  end
end
