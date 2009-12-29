class Volunteer < ActiveRecord::Base
  belongs_to :contact
  belongs_to :project
  belongs_to :house
end
