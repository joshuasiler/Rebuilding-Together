class Contact < ActiveRecord::Base
  include Enumerable

  has_many :contact_skills
  has_many :skills, :through => :contact_skills

  def each
    attributes.each do |attr, val|
      yield [attr, val]
    end
  end
end
