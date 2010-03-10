namespace :onetime do

  desc "One time update to migrate volunteers"
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
  
	desc "Dedupe the contact list (only for contacts with email)"
	task :dedupe_contacts => :environment do
		countera = 0
		counterb = 0
		# get all dups by first(3) + last(3) + email
		dups = Contact.find_by_sql("select * from ( SELECT CONCAT(SUBSTR(first_name,1,3),SUBSTR(last_name,1,3),email) AS keyer, COUNT(*) AS counter FROM contacts where not TRIM(email)='' GROUP BY keyer ORDER BY counter desc ) a where counter > 1 ")
		dups.each { |dup|
			puts "Found: " + dup.keyer
			mydups = Contact.find_by_sql(["SELECT * from contacts where CONCAT(SUBSTR(first_name,1,3),SUBSTR(last_name,1,3),email)=? ORDER BY updated_at desc",dup.keyer])
			survivor = mydups[0]
			countera += 1
			mydups.each { |mydup|
				unless mydup.id == survivor.id
					counterb += 1
					puts "Removing: " + mydup.id.to_s
					if Volunteer.find_by_contact_id(survivor.id).nil?
						Contact.connection.execute("update volunteers set contact_id = #{survivor.id} where contact_id = #{mydup.id}")
					end
					Contact.connection.execute("update sentemails set contact_id = #{survivor.id} where contact_id = #{mydup.id}")
					Contact.connection.execute("update houses set contact_id = #{survivor.id} where contact_id = #{mydup.id}")
					mydup.destroy
				end
				}
			}		
			puts "evaluated with duplicates: " + countera.to_s
			puts "removed duplicate records: " + counterb.to_s
	end

  
end
