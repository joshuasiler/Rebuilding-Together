class Contact < ActiveRecord::Base
  has_many :skills, :through => :contact_skills

end
