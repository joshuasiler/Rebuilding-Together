class AddCaptainFlagtoVolunteers < ActiveRecord::Migration
  def self.up
    add_column :volunteers, :is_housecaptain, :boolean, :null => false, :default => 0
    end

  def self.down
    remove_column :volunteers, :is_housecaptain
  end
end
