class RemoveSalutation < ActiveRecord::Migration
  def self.up
      remove_column :contacts, :salutation
  end

  def self.down
    add_column :contacts, :salutation, :string
  end
end
