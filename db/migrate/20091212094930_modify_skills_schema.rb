class ModifySkillsSchema < ActiveRecord::Migration
  def self.up
      change_column :volunteers, :number_of_people, :integer, :null => false, :default => 1 #Add default=1
      remove_column :skills, :is_contact_skill
      remove_column :skills, :is_house_skill
      add_column :houses, :house_captain_2_contact_id, :integer
  end

  def self.down
    remove_column :houses, :house_captain_2_contact_id
    add_column :skills, :is_contact_skill, :boolean, :null => false, :default => 0
    add_column :skills, :is_house_skill, :boolean, :null => false, :default => 0
    change_column :volunteers, :number_of_people, :integer, :null => false
  end
end
