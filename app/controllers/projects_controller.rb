class ProjectsController < ApplicationController
  layout 'mainsite'
    
  def view
    @project = Project.find(params[:id])
  end
  def new
    @project = Project.new
  end
  def edit
    @project = Project.find(params[:id])
  end
  def update
    @project = Project.update(params[:project][:id], params[:project])
    render :view
  end
  
end
