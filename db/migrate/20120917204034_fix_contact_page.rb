class FixContactPage < ActiveRecord::Migration
  def self.up
  	ActiveRecord::Base.connection.execute('UPDATE pages SET link_url = "/contact" WHERE id=9')
  end

  def self.down
  end
end
