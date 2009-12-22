class AddIndices < ActiveRecord::Migration
  def self.up
    add_index "contact_skills", "contact_id", :name => "index1"
  end

  def self.down
    remove_index "contact_skills", "index1"
  end
end
