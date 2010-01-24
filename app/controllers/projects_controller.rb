class ProjectsController < ApplicationController
  layout 'mainsite'
  
  def initialize
    @display_columns = %w(project_name starts_on ends_on number_of_volunteers_needed)
    
  end

  def index
    @grid = DataGrid.new :grid
    @grid.configure do |g|
      g.get_data do |state|
        Project.find(:all)
      end

      g.get_columns do |state, house|
        @display_columns.collect do |col|
          [col, house[col]]
        end
      end
    end
  end

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
