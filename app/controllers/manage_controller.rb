require 'data_grid'

class ManageController < ApplicationController
  def index
    @grid = DataGrid.new
    @display_columns = %w(name email company skills house)
    
    @grid.configure do |g|
      g.model = Contact

      # g.page = 1
      # g.per_page = 10

      # Restore query form state if "filter" button
      # pressed.
      @query = 
        if request["query"] && request["query"]["filter"] 
          request["query"]
        else
          Hash.new
        end

      g.get_data do |state, model|
        cond = Conditions.new
        if @query["filter"]
          if @query["any_skills"]
            cond.any_skills = true
          elsif @query["skills"]
            cond.skills = @query["skills"].split(",")
          end

          case @query["assigned"]
          when "1"
            cond.assigned = true
            cond.unassigned = false
          when "2"
            cond.assigned = false
            cond.unassigned = true
          else
            cond.assigned = true
            cond.unassigned = true
          end

          cond.include_inactive = !! @query["inactive"]
        end

        model.find(:all, :conditions => cond.conditions)
      end

      g.get_columns do |state, model, contact|
        @display_columns.collect do |col| 
          case col
          when "name"
            [col, "#{contact.first_name} #{contact.last_name}".strip]
          when "skills"
            [col, contact.skills.inject { |acc, skill| acc + ", " + skill }.to_s]
          when "company"
            [col, contact.company_name]
          else
            [col, contact[col].to_s]
          end
        end
      end
    end
  end

  def add
  end

  def update
  end

  # Helps build conditions statement for
  # contact query on this page.
  class Conditions
    include ::ActiveRecord::ConnectionAdapters::Quoting

    # An array of skill IDs. Defaults to empty.
    attr_accessor :skills
    # A boolean, defaults to false
    attr_accessor :assigned
    # A boolean, defaults to false
    attr_accessor :unassigned
    # A boolean, defaults to false.
    attr_accessor :include_inactive
    # A boolean, defaults to false. Overrides skills list.
    attr_accessor :any_skills

    def initialize
      @skills = []
      @assigned = false
      @unassigned = false
      @include_inactive = false
      @any_skills = false
    end

    def conditions
      cond = []
      if @any_skills
        cond << "id in (select contact_id from contact_skills)"
      elsif @skills && @skills.length > 0
        cond << %(id in (select contact_id from contact_skills where skill_id in (#{@skills.collect { |s| "'#{quote_string((s || "").to_s)}'"}.join(",")})))
      end

      assigned_contacts = <<-SQL
      (select v.contact_id 
       from volunteers v inner join
            projects p on v.project_id = p.id
       where p.ends_on is null OR p.ends_on > now())
SQL

      if @assigned && ! @unassigned
        cond << "id in (#{assigned_contacts})"
      elsif ! @assigned && @unassigned
        cond << "id not in (#{assigned_contacts})"
      end

      if @include_inactive
        cond << "is_active = 1"
      end

      cond.join " AND "
    end
    
    def clear
      @skills = nil
      @not_assigned = false
      @include_inactive = false
      @any_skills = false
    end

  end
end
