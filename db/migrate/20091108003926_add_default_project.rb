class AddDefaultProject < ActiveRecord::Migration
  def self.up
    Project.create(:project_name => "April 2010 Rebuilding Day",:project_type => "Rebuilding Day",:starts_on => Date.parse("4/20/2010"), :ends_on => Date.parse("4/20/2010"))
  end

  def self.down
  end
end
