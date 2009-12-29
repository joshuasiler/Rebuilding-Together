class Contact < ActiveRecord::Base
  include Enumerable

  has_many :contact_skills
  has_many :skills, :through => :contact_skills
  has_many :volunteers
  has_many :projects, :through => :volunteers
  has_many :houses, :through => :volunteers

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

end
