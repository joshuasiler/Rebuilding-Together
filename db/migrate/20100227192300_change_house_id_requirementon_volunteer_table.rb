class ChangeHouseIdRequirementonVolunteerTable < ActiveRecord::Migration
  def self.up
    change_column :volunteers, :house_id, :int, :null => true
  end

  def self.down
    change_column :volunteers, :house_id, :int, :null => false
  end
end
