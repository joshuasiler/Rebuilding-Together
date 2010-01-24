class HousesController < ApplicationController
  layout 'mainsite'
  
  def view
    @house = House.find(params[:id])
  end
  def new
    @house = House.new
  end
  def edit
    @house = House.find(params[:id])
  end
  def update
    @house = House.update(params[:house][:id], params[:house])
    render :view
  end
end
