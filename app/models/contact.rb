class Contact < ActiveRecord::Base
  has_many :contact_skills
  has_many :skills, :through => :contact_skills

end
