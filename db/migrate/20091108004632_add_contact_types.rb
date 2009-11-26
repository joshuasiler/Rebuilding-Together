class AddContactTypes < ActiveRecord::Migration
  def self.up
      Contacttype.create(:description => "House Captain")
      Contacttype.create(:description => "House Liaison")
  end

  def self.down
  end
end
