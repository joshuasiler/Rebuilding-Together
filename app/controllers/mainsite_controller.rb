class MainsiteController < ApplicationController
  def index
  end
  
  def pages
    render params[:id]
  end
end

