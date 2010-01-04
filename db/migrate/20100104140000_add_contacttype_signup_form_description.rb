class AddContacttypeSignupFormDescription < ActiveRecord::Migration
  def self.up
    add_column :contacttypes, :signup_form_description, :string, :null => true
    add_column :contacttypes, :signup_form_display_order, :integer, :null => false, :default => 0
    Contact.connection.execute("UPDATE contacttypes SET signup_form_display_order = 0")
    Contact.connection.execute("UPDATE contacttypes SET signup_form_display_order = 1, signup_form_description = 'Rebuilding Day Volunteer' WHERE description = 'Normal Volunteer'")
    Contact.connection.execute("UPDATE contacttypes SET signup_form_display_order = 2, signup_form_description = 'House Captain (coordinate the materials and scope of work to be completed on one home)' WHERE description = 'House Captain'")
    Contact.connection.execute("UPDATE contacttypes SET signup_form_display_order = 3, signup_form_description = 'House Liaison (assist and organize the needs of the homeowner)' WHERE description = 'House Liaison'")
    Contact.connection.execute("UPDATE contacttypes SET signup_form_display_order = 4, signup_form_description = 'Homeowner Interviewer (screen homeowners for eligibility year-round)' WHERE description = 'Homeowner Interviewer'")
    Contact.connection.execute("UPDATE contacttypes SET signup_form_display_order = 5, signup_form_description = 'Area Captain (liaisons between 5 houses and headquarters on the work day)' WHERE description = 'Area Captain'")
  end

  def self.down
    remove_column :contacttypes, :signup_form_display_order
    remove_column :contacttypes, :signup_form_description
  end
end
