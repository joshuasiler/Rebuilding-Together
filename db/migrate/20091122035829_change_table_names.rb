class ChangeTableNames < ActiveRecord::Migration
  def self.up
    begin
      Contact.connection.execute("rename table contact_types to contacttypes")
      Contact.connection.execute("rename table contact_contact_types to contact_contacttypes")
      Contact.connection.execute("ALTER TABLE contact_contacttypes CHANGE COLUMN contact_type_id contacttype_id integer")
    rescue
      puts "table renaming not necessary for clean builds"
    end
  end

  def self.down
    Contact.connection.execute("ALTER TABLE contact_contacttypes CHANGE COLUMN contacttype_id contact_type_id integer")
    Contact.connection.execute("rename table contacttypes to contact_types")
    Contact.connection.execute("rename table contact_contacttypes to contact_contact_types ")
  end
end
