class ProjectsController < ApplicationController
  def view
    @project = Project.find(params[:id])
  end
  def new
    @project = Project.new
  end
  def edit
    @project = Project.find(params[:id])
  end
end
