namespace :onetime do

  desc "One time update"
  task :migrate_stragglers => :environment do
	@contacts = Contact.find_by_sql('select * from contacts where created_at > "2009-12-13";')
	@contacts.each { |contact|
	    puts contact.email
	    
	    # looks for dups here, prevents someone getting same email twice
	    t = Volunteer.find_by_contact_id(contact.id)
	    if t.nil?
		v = Volunteer.new
		v.contact_id = contact.id
		v.project_id = Project.latest.id
		v.group_name = contact.company_name
		if contact.est_group_size.blank?
		  v.number_of_people = 1
		else
		  v.number_of_people = contact.est_group_size
		end
		v.save
		puts "created"
	    else
		puts "dup skipped"
	    end
	    }
  end

  
end
