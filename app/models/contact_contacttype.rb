class ContactContacttype < ActiveRecord::Base
  belongs_to :contact
  belongs_to :contacttype
end
