class UpdateHastruckDescription < ActiveRecord::Migration
  def self.up
    Contact.connection.execute("UPDATE skills SET description = 'Have a Truck' WHERE description = 'Has Truck'")
  end

  def self.down
    Contact.connection.execute("UPDATE skills SET description = 'Has Truck' WHERE description = 'Have a Truck'")
  end
end
