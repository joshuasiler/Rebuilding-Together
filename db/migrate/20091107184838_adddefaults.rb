class Adddefaults < ActiveRecord::Migration
  def self.up
    Skill.create(:description => "Appliance Repair", :is_trade_skill => 1)
    Skill.create(:description => "Carpentry", :is_trade_skill => 1)
    Skill.create(:description => "Cleaning", :is_house_skill => 1)
    Skill.create(:description => "Concrete", :is_trade_skill => 1)
    Skill.create(:description => "Crane Operator", :is_trade_skill => 1)
    Skill.create(:description => "Drywall", :is_trade_skill => 1)
    Skill.create(:description => "Electrical", :is_trade_skill => 1)
    Skill.create(:description => "Flooring", :is_trade_skill => 1)
    Skill.create(:description => "Foundation", :is_trade_skill => 1)
    Skill.create(:description => "Gas", :is_trade_skill => 1)
    Skill.create(:description => "Glazing", :is_trade_skill => 1)
    Skill.create(:description => "Hauling", :is_house_skill => 1)
    Skill.create(:description => "Heat/AC", :is_trade_skill => 1)
    Skill.create(:description => "Insulation", :is_trade_skill => 1)
    Skill.create(:description => "Masonry", :is_trade_skill => 1)
    Skill.create(:description => "Painting", :is_trade_skill => 1)
    Skill.create(:description => "Pest Control", :is_trade_skill => 1)
    Skill.create(:description => "Plumbing", :is_trade_skill => 1)
    Skill.create(:description => "Roofing", :is_trade_skill => 1)
    Skill.create(:description => "Scaffolding", :is_trade_skill => 1)
    Skill.create(:description => "Scraping", :is_house_skill => 1)
    Skill.create(:description => "Sheet Rock", :is_trade_skill => 1)
    Skill.create(:description => "Tile", :is_trade_skill => 1)
    Skill.create(:description => "Trash", :is_house_skill => 1)
    Skill.create(:description => "Welding", :is_trade_skill => 1)
    Skill.create(:description => "Yard Work", :is_house_skill => 1)
  
    Skill.create(:description => "Waste Management", :is_house_skill => 1)
    Skill.create(:description => "Organizing", :is_contact_skill => 1)
    Skill.create(:description => "Public Relations", :is_contact_skill => 1)
    Skill.create(:description => "Community Outreach", :is_contact_skill => 1)
    Skill.create(:description => "Special Events / Fundraising", :is_contact_skill => 1)
    Skill.create(:description => "Publicity", :is_contact_skill => 1)
    Skill.create(:description => "Data Entry", :is_contact_skill => 1)
        
    ContactType.create(:description => "Normal Volunteer")
    ContactType.create(:description => "Skilled Trade Volunteer")
    ContactType.create(:description => "Office Volunteer")
    ContactType.create(:description => "Money Contributor")
    ContactType.create(:description => "In-Kind Contributor")
    ContactType.create(:description => "House Sponsor")
    ContactType.create(:description => "Homeowner")
    ContactType.create(:description => "Board Member")
    ContactType.create(:description => "Committee Member")
  end

  def self.down
    Contact.connection.execute("delete from skills")
    Contact.connection.execute("delete from contact_types")
  end
end
