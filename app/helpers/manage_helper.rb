module ManageHelper
  def captains
    Contact.find(:all, :conditions => <<-SQL, :order => "last_name + first_name ASC").collect { |c| [c.last_name + ", " + c.first_name, c.id.to_s] }
contacts.id in (select contact_id 
  from contact_contacttypes 
  where contact_type_id in (select id 
    from contacttypes where
    description = 'House Captain'))
SQL
  end

  def project_options()
   Project.find(:all).collect { |p| [p.project_name, p.id.to_s] }
  end 

  def houses_for(project)
    House.find(:all, :conditions => "project_id = #{project.id}", :include => [:contact]).collect { |h| [h.contact.address_1, h.id.to_s] }
  end
end
