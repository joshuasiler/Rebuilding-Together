class AddContactTypes < ActiveRecord::Migration
  def self.up
      ContactType.create(:description => "House Captain")
      ContactType.create(:description => "House Liaison")
  end

  def self.down
  end
end
