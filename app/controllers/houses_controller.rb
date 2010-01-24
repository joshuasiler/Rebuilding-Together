class HousesController < ApplicationController
  def view
    @house = House.find(params[:id])
  end
  def new
    @house = House.new
  end
  def edit
    @house = House.find(params[:id])
  end
end
