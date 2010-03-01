class AddEmailTable < ActiveRecord::Migration
  def self.up
    create_table :sentemails do |t|
      t.integer :id, :null => false
      t.integer :contact_id, :null => false
      t.string :email, :null => false
      t.string :pin, :null => false
      t.string :email_name, :null => false
      t.datetime :sent_at, :null => true
      t.datetime :responded_at, :null => true
      t.datetime :created_at, :null => false
      t.datetime :updated_at, :null => false
    end
    
    add_index "sentemails", "contact_id", :name => "index1"
    add_index "sentemails", "id", :name => "index2"
    add_index "sentemails", "pin", :name => "index3"
  end

  def self.down
    drop_table :sentemails
  end
end
