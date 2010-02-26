class Addoptouttocontacts < ActiveRecord::Migration
  def self.up
    add_column :contacts, :optout, :int, :default => 0
  end

  def self.down
    remove_column :contacts, :optout
  end
end
