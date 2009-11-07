require 'datagrid'

class ManageController < ApplicationController
  class Group < Struct.new(:id, :name)
    include Enumerable

    class << self
      def column_names 
        ["id", "name"]
      end
    end

    def attribute_names
      ["id", "name"]
    end

    def each
      yield ["id", id]
      yield ["name", name]
    end
  end

  def index
    @grid = DataGrid.new
    @display_columns = %w(first_name email is_active)
    
    @grid.configure do |g|
      g.model = Contact

      # g.page = 1
      # g.per_page = 10

      g.get_data do |exec, model|
        model.all
      end

      g.get_columns do |exec, model, record|
        @display_columns.collect { |col| [col, record[col]] }
      end
    end
  end

  def add
  end

  def update
  end

end
