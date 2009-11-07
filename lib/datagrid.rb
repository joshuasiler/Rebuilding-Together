class DataGrid 
  class Executor
    attr_reader :data
  end

  class Configurator
    attr_reader :get_data_fn, :get_columns_fn
    attr_reader :model
    attr_accessor :table, :row, :cell

    # Assign a block to get data for the grid. 
    #
    # The block given should take two argument, which will
    # be the model and an Executor (with runtime grid properties).
    def get_data(&blk)
      raise "get_data must be given a block" if blk.nil?
      @get_data_fn = blk
    end

    def get_columns(&blk)
      raise "get_colmns must be given a block" if blk.nil?
      @get_columns_fn = blk
    end
    
    def model=(m)
      @model = m
    end

    def records
      @table = Wrap.new
      yield @table if block_given?
    end
    
    def record
      @row = Wrap.new
      yield @row if block_given?
    end

    def attribute
      @cell = Wrap.new
      yield @cell if block_given?
    end
  end

  class Wrap
    attr_accessor :start_fn, :end_fn

    def initialize
      @start_fn = proc { "" }
      @end_fn = proc { "" }
    end

    def start(&blk)
      @start_fn = blk
    end

    def end(&blk)
      @end_fn = blk
    end
  end

  # Configure overall grid - model, paging, etc.
  #
  # A Configurator object is yielded.
  def configure # :yields Configurator:
    @conf ||= Configurator.new
    yield @conf if block_given?
  end

  def rendering 
    @conf ||= Configurator.new
    yield @conf if block_given?
  end

  def render
    exec = Executor.new
    data =
      if @conf.get_data_fn && @conf.model
        @conf.get_data_fn.call(exec, @conf.model)
      else
        # render nothing?
        raise "bar"
      end

    
    @conf.table.start_fn.call(data) +
      data.inject("") { |acc, record|
      acc + @conf.row.start_fn.call(data, record) +
        @conf.get_columns_fn.call(exec, @conf.model, record).
          inject("") { |acc, attr|
            key, value = attr
            acc + @conf.cell.start_fn.call(record, key, value) + 
              @conf.cell.end_fn.call(record, key, value)
          } + @conf.row.end_fn.call(data, record)
      } + @conf.table.end_fn.call(data)
  end
end
