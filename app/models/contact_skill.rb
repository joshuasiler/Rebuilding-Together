class ContactSkill < ActiveRecord::Base
  belongs_to :contact
  belongs_to :skill
end
