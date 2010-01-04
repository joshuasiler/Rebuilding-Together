class AddContactEstGroupSize < ActiveRecord::Migration
  def self.up
    add_column :contacts, :est_group_size, :string
  end

  def self.down
    remove_column :contacts, :est_group_size
  end
end
