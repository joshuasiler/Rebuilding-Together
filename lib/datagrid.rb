require 'active_record'

class DataGrid 

  # Configuration for rendering
  class Renderator
    attr_accessor :table, :row, :cell, :accumulator

    # Yields a Wrap object which will hold the blocks for rendering at
    # the grid level. Wrap blocks should take the following arguments:
    #
    #   Wrap#start - The state hash, all records and an accumulator
    #
    #   Wrap#end - The state hash, all records an an accumulator
    #
    #   Wrap#last - Not called in this case.
    #
    # Wrap#start and Wrap#end will be called once each, even if
    # there are no records to enumerate.
    #
    # If start or end are nil, the methods will simply not be
    # called.
    def records
      @table = Wrap.new
      yield @table if block_given?
    end
    
    # Yields a Wrap object will hold the blocks for rendering at the
    # row level. Wrap blocks should take the following arguments.
    #
    #   Wrap#start - The state hash, all records, the current record,
    #   and an accumulator.
    #
    #   Wrap#end - The state hash, all records, the current record,
    #   and an accumulator.
    #
    #   Wrap#last - The state hash, all records, the current record,
    #   and an accumulator.
    #
    # Wrap#start will not be called if there is only one record to be
    # called; only Wrap#last will be called in that case. Wrap#end
    # will always be called if there are any records. If there
    # are no records, nothing will be called.
    #
    # If start, end or last are nil, the methods will simply not be
    # called.
    def record
      @row = Wrap.new
      yield @row if block_given?
    end

    # Yields a Wrap object which will hold the blocks for rendering at
    # the column level. Wrap blocks should take the following
    # arguments.
    #
    #   Wrap#start - The state hash, the current record, the current
    #   column name, the current column value and an accumulator.
    #
    #   Wrap#end - The state hash, the current record, the current
    #   column name, the current column value and an accumulator.
    #
    #   Wrap#last - The state hash, the current record, the current
    #   column name, the current column value and an accumulator.
    #
    # Wrap#start will not be called if there is only one record to be
    # called; only Wrap#last will be called in that case. Wrap#end
    # will always be called if there are any records. If there
    # are no records, nothing will be called.
    #
    # If start, end or last are nil, the methods will simply not be
    # called.
    def attribute
      @cell = Wrap.new
      yield @cell if block_given?
    end
  end

  # Holds controller-level grid configuration information.
  # See DataGrid#configure.
  class Configurator
    # Set by get_data and get_columns, respectively.
    attr_reader :get_data_fn, :get_columns_fn
    
    # If get_data is used, then the object set here
    # will be passed to that function. If get_data
    # is not set, this object will be used in one of 
    # two ways, depending on what it is:
    #
    #  ActiveRecord class/object - The all method will be used
    #   to get data.
    #  Enumerable object - each will be called to get data
    attr_accessor :model

    # Assign a block to get data for the grid.
    #
    # The block given should take two arguments, a hash and the model,
    # in that order. It shoul return an enumerable object which will yield
    # each row.
    #
    # The hash object passed in will be created when enumeration
    # starts and will be preserved until enumeration is done. It will
    # be passed to all blocks in turn and can be used to thread state
    # through all blocks.
    def get_data(&blk)
      raise "get_data must be given a block" if blk.nil?
      @get_data_fn = blk
    end

    # Assign a block to get columns for each record in the model. This
    # will be called once for each record. The block should return an Enumerable
    # object which will yield key/value pairs. Those pairs will be used when
    # rendering each column.
    #
    # The block given should take three arguments, which will be a
    # hash object, the model, and the current record. 
    #
    # The hash object passed in will be the same that was passed to
    # get_data above, and it will be passed to each invocation of
    # get_columns. The hash can be used to thread state through all
    # blocks.
    def get_columns(&blk)
      raise "get_colmns must be given a block" if blk.nil?
      @get_columns_fn = blk
    end
  end

  # Helper class which holds start, last and
  # end functions for use when rendering.
  class Wrap
    attr_accessor :start_fn, :end_fn, :last_fn

    def start(&blk)
      @start_fn = blk
    end

    def last(&blk)
      @last_fn = blk
    end

    def end(&blk)
      @end_fn = blk
    end
  end

  # Configure overall grid - model, paging, etc.
  #
  # A Configurator object is yielded.
  def configure # :yields Configurator:
    @conf = Configurator.new
    yield @conf if block_given?
  end

  # Yields an object for configuring rendering. A Renderator
  # object is yielded to the block and then returned.
  def rendering # :yields Renderator:
    @renderer = Renderator.new
    yield @renderer  if block_given?
    @renderer
  end

  # Renders the grid. A Renderator object can be supplied, but if not
  # the one last returned from rendering will be used.
  #
  # The accumulator on the Renderator object will be returned when
  # this method is finished.
  def render(render = nil)
    render ||= @renderer
    raise "No rendering configuration available" unless render

    raise "get_columns must be set on the configurator" unless @conf.get_columns_fn

    # Create our state hash to thread through.
    state = Hash.new

    # Get ourselves an enumerable object
    data =
      if @conf.get_data_fn 
        @conf.get_data_fn.call(state, @conf.model)
      elsif @conf.model.nil?
        raise "No model supplied with configuration"
      elsif @conf.model.is_a?(Class) && @conf.model.ancestors.include?(ActiveRecord::Base) 
        @conf.model.all
      elsif @conf.model.is_a?(Enumerable)
        @conf.model
      else
        raise "Can't enumerate model: #{@conf.model.inspect}"
      end

    render.table.start_fn.call(state, data, render.accumulator) if render.table.start_fn
    last = 
      data.inject(nil) do |prev, r|
        do_row(render, state, render.row.start_fn, data, prev) if prev
        r
      end
    do_row(render, state, render.row.last_fn, data, last) if last && render.row.last_fn
    render.table.end_fn.call(state, data, render.accumulator) if render.table.end_fn

    render.accumulator
  end

  private

  def do_row(render, state, start_fn, data, record)
    start_fn.call(state, data, record, render.accumulator) if start_fn
    last = 
      @conf.get_columns_fn.call(state, @conf.model, record).inject(nil) do |prev, a|
        do_column(render, state, render.cell.start_fn, record, prev[0], prev[1]) if prev
        a
      end 
    do_column(render, state, render.cell.last_fn, record, last[0], last[1]) if last 
    render.row.end_fn.call(state, data, record, render.accumulator) if render.row.end_fn
  end

  def do_column(render, state, start_fn, record, key, value)
    start_fn.call(state, record, key, value, render.accumulator) if start_fn 
    render.cell.end_fn.call(state, record, key, value, render.accumulator) if render.cell.end_fn
  end
end
