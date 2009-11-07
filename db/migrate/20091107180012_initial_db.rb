class InitialDb < ActiveRecord::Migration
  def self.up

              create_table :contacts do |t|
      t.int :id, :null => false
      t.string :salutation
      t.string :first_name
      t.string :last_name
      t.string :address_1
      t.string :address_2
      t.string :city
      t.string :state
      t.string :zip
      t.string :country
      t.string :home_phone
      t.string :work_phone
      t.string :mobile_phone
      t.string :fax
      t.string :pager
      t.string :email
      t.string :email_preference
      t.string :email_status
      t.string :job_title
      t.string :company_name
      t.string :company_phone
      t.string :age
      t.string :gender
      t.text :other_skills
      t.string :committee_position
      t.string :board_position
      t.text :comments
      t.boolean :is_active, :null => false, :default => 0
      t.datetime :created_at, :null => false
      t.datetime :updated_at, :null => false
      end
      
      create_table :contact_contact_types do |t|
      t.int :contact_id, :null => false
      t.int :contact_type_id, :null => false
      end
      
      create_table :contact_types do |t|
      t.int :id, :null => false
      t.string :description, :null => false
      end
      
    create_table :houses do |t|
      t.int :id, :null => false
      t.int :contact_id, :null => false
      t.int :project_id, :null => false
      t.string :ethnicity
      t.string :disability
      t.string :years_in_home
      t.boolean :is_homeowner, :null => false, :default => 0
      t.string :annual_income
      t.string :monthly_payments
      t.int :number_of_people_in_house
      t.int :number_of_children
      t.text :children_names_and_ages
      t.date :application_submitted_on
      t.boolean :is_application_complete, :null => false, :default => 0
      t.boolean :is_application_signed, :null => false, :default => 0
      t.boolean :is_proof_of_ownership, :null => false, :default => 0
      t.boolean :is_income_verification, :null => false, :default => 0
      t.boolean :is_previous_cia_application, :null => false, :default => 0
      t.string :referral_organization
      t.string :referral_agent
      t.int :house_number
      t.string :area_of_town
      t.text :repairs_needed
      t.string :number_of_volunteers_needed
      t.text :materials_needed
      t.string :estimated_cost
      t.text :comments
      t.boolean :is_accepted, :null => false, :default => 0
      t.date :accept_letter_sent_on
      t.int :previewer_contact_id
      t.int :house_captain_contact_id
      t.datetime :created_at, :null => false
      t.datetime :updated_at, :null => false
    end

      create_table :projects do |t|
      t.int :id, :null => false
      t.string :project_type, :null => false
      t.string :project_name, :null => false
      t.string :cia_lead
      t.date :starts_on, :null => false
      t.date :ends_on, :null => false
      t.int :number_of_volunteers_needed
      t.datetime :created_at, :null => false
      t.datetime :updated_at, :null => false
      end
      
      create_table :contact_skills do |t|
      t.int :contact_id, :null => false
      t.int :skills_id, :null => false
      end
      
      create_table :houses_skills do |t|
      t.int :house_id, :null => false
      t.int :skill_id, :null => false
      end
      
      create_table :skills do |t|
      t.int :id, :null => false
      t.string :description, :null => false
      t.boolean :is_trade_skill, :null => false, :default => 0
      t.boolean :is_house_skill, :null => false, :default => 0
      t.boolean :is_contact_skill, :null => false, :default => 0
      end
      
      create_table :assignments do |t|
      t.int :id, :null => false
      t.int :volunteer_group_id, :null => false
      t.int :house_id, :null => false
      t.boolean :is_recruited, :null => false, :default => 0
      t.boolean :is_confirmed, :null => false, :default => 0
      t.boolean :is_participated, :null => false, :default => 0
      t.date :thankyou_sent_on
      t.datetime :created_at, :null => false
      t.datetime :updated_at, :null => false
      end
      
      create_table :volunteer_groups do |t|
      t.int :id, :null => false
      t.int :contact_id, :null => false
      t.int :project_id, :null => false
      t.int :number_of_people, :null => false
      t.string :group_name
      t.boolean :is_volunteer_year_round, :null => false, :default => 0
      t.boolean :is_volunteer_week_before_bigday, :null => false, :default => 0
      t.boolean :is_volunteer_on_bigday, :null => false, :default => 0
      t.boolean :is_volunteer_week_after_bigday, :null => false, :default => 0
      t.datetime :created_at, :null => false
      t.datetime :updated_at, :null => false
      end


  end

  def self.down

  end
end
