require 'datagrid'

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

      g.get_data do |exec, model|
        if @query["filter"]
          cond = []
          if @query["any_skills"]
            cond << "id in (select contact_id from contact_skills)"
          elsif @query["skills"]
            cond << "" 
          end
            model.find(:all, :conditions => cond.join(" AND "))
        else
            model.all
        end
      end

      g.get_columns do |exec, model, contact|
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

end
