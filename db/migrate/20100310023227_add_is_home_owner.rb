class AddIsHomeOwner < ActiveRecord::Migration
  def self.up
    add_column :contacts, :is_homecontact, :boolean, :null => false, :default => 0
    houses = House.find(:all)
    houses.each { |house|
      c = Contact.find(house.contact_id)
      c.is_homecontact = 1
      c.save
      }
    Contact.connection.execute("update contacts set email='' where email = 'malonem@up.edu' and not first_name='mike'")
  end

  def self.down
    remove_column :contacts, :is_homeowner
  end
end
