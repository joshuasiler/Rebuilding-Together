module ContactHelper
  
  @tab_index_counter = 1
  
  # "TabIndex" utility function:
  # Returns a tabindex attribute string with an incremental number.
  # Starts at tabindex 1 first time called, increments by 1 each subsequent call.
  # Pass a seed value to (re)start counting at a specific number.
  def ti(seed = nil)
    @tab_index_counter = seed || (@tab_index_counter + 1)
    html = 'tabindex="' + @tab_index_counter.to_s + '"'
    return html
  end
  
end
