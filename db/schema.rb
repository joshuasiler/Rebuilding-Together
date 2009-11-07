# This file is auto-generated from the current state of the database. Instead of editing this file, 
# please use the migrations feature of Active Record to incrementally modify your database, and
# then regenerate this schema definition.
#
# Note that this schema.rb definition is the authoritative source for your database schema. If you need
# to create the application database on another system, you should be using db:schema:load, not running
# all the migrations from scratch. The latter is a flawed and unsustainable approach (the more migrations
# you'll amass, the slower it'll run and the greater likelihood for issues).
#
# It's strongly recommended to check this file into your version control system.

ActiveRecord::Schema.define(:version => 20091107184838) do

  create_table "assignments", :force => true do |t|
    t.boolean  "is_recruited",     :default => false, :null => false
    t.boolean  "is_confirmed",     :default => false, :null => false
    t.boolean  "is_participated",  :default => false, :null => false
    t.date     "thankyou_sent_on"
    t.datetime "created_at",                          :null => false
    t.datetime "updated_at",                          :null => false
  end

  create_table "contact_contact_types", :force => true do |t|
    t.datetime "created_at", :null => false
    t.datetime "updated_at", :null => false
  end

  create_table "contact_skills", :force => true do |t|
    t.datetime "created_at", :null => false
    t.datetime "updated_at", :null => false
  end

  create_table "contact_types", :force => true do |t|
    t.string   "description", :null => false
    t.datetime "created_at",  :null => false
    t.datetime "updated_at",  :null => false
  end

  create_table "contacts", :force => true do |t|
    t.string   "salutation"
    t.string   "first_name"
    t.string   "last_name"
    t.string   "address_1"
    t.string   "address_2"
    t.string   "city"
    t.string   "state"
    t.string   "zip"
    t.string   "country"
    t.string   "home_phone"
    t.string   "work_phone"
    t.string   "mobile_phone"
    t.string   "fax"
    t.string   "pager"
    t.string   "email"
    t.string   "email_preference"
    t.string   "email_status"
    t.string   "job_title"
    t.string   "company_name"
    t.string   "company_phone"
    t.string   "age"
    t.string   "gender"
    t.text     "other_skills"
    t.string   "committee_position"
    t.string   "board_position"
    t.text     "comments"
    t.boolean  "is_active",          :default => false, :null => false
    t.datetime "created_at",                            :null => false
    t.datetime "updated_at",                            :null => false
  end

  create_table "house_skills", :force => true do |t|
    t.datetime "created_at", :null => false
    t.datetime "updated_at", :null => false
  end

  create_table "houses", :force => true do |t|
    t.string   "ethnicity"
    t.string   "disability"
    t.string   "years_in_home"
    t.boolean  "is_homeowner",                :default => false, :null => false
    t.string   "annual_income"
    t.string   "monthly_payments"
    t.text     "children_names_and_ages"
    t.date     "application_submitted_on"
    t.boolean  "is_application_complete",     :default => false, :null => false
    t.boolean  "is_application_signed",       :default => false, :null => false
    t.boolean  "is_proof_of_ownership",       :default => false, :null => false
    t.boolean  "is_income_verification",      :default => false, :null => false
    t.boolean  "is_previous_cia_application", :default => false, :null => false
    t.string   "referral_organization"
    t.string   "referral_agent"
    t.string   "area_of_town"
    t.text     "repairs_needed"
    t.string   "number_of_volunteers_needed"
    t.text     "materials_needed"
    t.string   "estimated_cost"
    t.text     "comments"
    t.boolean  "is_accepted",                 :default => false, :null => false
    t.date     "accept_letter_sent_on"
    t.datetime "created_at",                                     :null => false
    t.datetime "updated_at",                                     :null => false
  end

  create_table "projects", :force => true do |t|
    t.string   "project_type", :null => false
    t.string   "project_name", :null => false
    t.string   "cia_lead"
    t.date     "starts_on",    :null => false
    t.date     "ends_on",      :null => false
    t.datetime "created_at",   :null => false
    t.datetime "updated_at",   :null => false
  end

  create_table "skills", :force => true do |t|
    t.string   "description",                         :null => false
    t.boolean  "is_trade_skill",   :default => false, :null => false
    t.boolean  "is_house_skill",   :default => false, :null => false
    t.boolean  "is_contact_skill", :default => false, :null => false
    t.datetime "created_at",                          :null => false
    t.datetime "updated_at",                          :null => false
  end

  create_table "volunteer_groups", :force => true do |t|
    t.string   "group_name"
    t.boolean  "is_volunteer_year_round",         :default => false, :null => false
    t.boolean  "is_volunteer_week_before_bigday", :default => false, :null => false
    t.boolean  "is_volunteer_on_bigday",          :default => false, :null => false
    t.boolean  "is_volunteer_week_after_bigday",  :default => false, :null => false
    t.datetime "created_at",                                         :null => false
    t.datetime "updated_at",                                         :null => false
  end

end
