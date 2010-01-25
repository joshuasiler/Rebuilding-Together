class House < ActiveRecord::Base
  belongs_to :project
  belongs_to :contact

  # Return a list of houses that belong to the
  # particular project given
  def houses_for(project)
    
  end

  def address
    a = [:address_1, :address_2].inject("") do |addr, f| 
      if contact[f].blank? 
        addr
      elsif ! addr.blank?
        addr + ", " + contact[f]
      else
        contact[f]
      end
    end
    
    a = [:city, :state].inject(a) do |addr, f|
      if contact[f].blank?
        addr
      elsif ! addr.blank?
        addr + ", " + contact[f]
      else
        contact[f]
      end
    end

    if a.blank?
      contact[:zip]
    else
      a + " " + contact[:zip]
    end
  end
end
