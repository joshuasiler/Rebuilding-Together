class Contact < ActiveRecord::Base
  include Enumerable

  def each
    attributes.each do |attr, val|
      yield [attr, val]
    end
  end
end
