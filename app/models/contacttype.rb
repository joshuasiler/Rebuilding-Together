class Contacttype < ActiveRecord::Base
  class << self
    include ERB::Util
  end
  
  has_many :contact_contacttypes
  has_many :contacts, :through => :contact_contacttypes

  # An array of arrays, where each element has the form:
  #
  #  [description, id]
  #
  # The result is designed to be used with ActionView::Helpers::FormOptionsHelper#options_for_select
  def self.all_contacttypes
    all.collect { |contacttype| [html_escape(contacttype.description), h(contacttype.id.to_s)] }
  end

end
