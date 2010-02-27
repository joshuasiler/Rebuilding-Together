class Contact < ActiveRecord::Base
  include Enumerable

  has_many :contact_skills
  has_many :skills, :through => :contact_skills
  has_many :contact_contacttypes
  has_many :contacttypes, :through => :contact_contacttypes
  has_many :volunteers
  has_many :projects, :through => :volunteers
  has_many :houses, :through => :volunteers
  
  validates_presence_of :first_name, :message => "is required"
  validates_presence_of :last_name, :message => "is required"
  validates_presence_of :email, :message => "is required"
  validates_format_of   :email,
                        :with       => /^([^@\s]+)@((?:[-a-z0-9]+\.)+[a-z]{2,})$/i,
                        :message    => "is not valid"

  # Get the currently assigned home for the latest
  # project, if any.
  def current_house
    if ! houses.empty?
      # descending sort
      hs = houses.sort do |h1, h2|
        h2.project.starts_on <=> h1.project.starts_on 
      end

      if hs[0].project.id == Project.latest.id
        hs[0]
      else
        nil
      end
    else
      nil
    end
  end

  # If a contact has a project assigned, this will return
  # the most recent one.
  def latest_project
    if ! (ps = projects.all).empty?
      ps.sort do |p1, p2|
        p1.starts_on <=> p2.starts_on
      end
    else
      nil
    end
  end

  def find_duplicates
    c = Contact.find_by_sql(["select * from contacts where substr(first_name,1,3) = ? and substr(last_name,1,3) = ? and email = ?",self.first_name[0,3],self.last_name[0,3],self.email])
    unless c[0].nil?
      c[0].id
    else
      nil
    end
  end
  
end
