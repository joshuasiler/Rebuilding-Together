class MoreIndices < ActiveRecord::Migration
  def self.up
    add_index "contact_contacttypes", "contacttype_id", :name => "index1"
    add_index "volunteers", "house_id", :name => "index1"
    add_index "volunteers", "project_id", :name => "index2"
    add_index "houses", ["house_captain_contact_id", "house_captain_2_contact_id"], :name => "index1"
  end

  def self.down
    drop_index "contact_contacttypes", "index1"
    drop_index "volunteers", "index1"
    drop_index "volunteers", "index2"
    drop_index "houses", "index1"
  end
end
