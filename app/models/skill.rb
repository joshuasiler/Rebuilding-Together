
class Skill < ActiveRecord::Base
  class << self
    include ERB::Util
  end

  has_many :contact_skills
  has_many :contacts, :through => :contact_skills

  # An array of arrays, where each element has
  # the form:
  #
  #  [descriptoin, id]
  #
  # The result is designed to be used with ActionView::Helpers::FormOptionsHelper#options_for_select
  def self.all_skills
    all.collect { |skill| [html_escape(skill.description), h(skill.id.to_s)] }
  end

end
