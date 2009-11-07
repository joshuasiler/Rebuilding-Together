class Skill < ActiveRecord::Base
  has_many :contact_skills
  has_many :contacts, :through => :contact_skills
end
