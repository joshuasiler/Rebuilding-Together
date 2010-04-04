namespace :emailops do

  desc "Send emails out notifying contacts of upcoming day."
  task :send_volunteer_recruitment => :environment do
	counter = 0
	dups = 0
	    # Change this email name to send a new email to people
	    email_name = "2010daynotification"
	    @contacts = Contact.find(:all, :conditions => "optout = 0 and not TRIM(email)='' and not email='old'", :order => "created_at desc")
	    @contacts.each { |contact|
		print contact.email
		
		# looks for dups here, prevents someone getting same email twice
		t = Sentemail.find_by_email(contact.email)
		if t.nil?
		    # make record of send
		    s = Sentemail.new
		    s.email = contact.email
		    s.email_name = email_name
		    s.contact_id = contact.id
		    s.pin = Sentemail.generate_pin(contact.id.to_s)
		    s.sent_at = DateTime.now()
		    s.save
				print "("+contact.id.to_s+")"
		    Notification.deliver_volunteer_notification(contact,s.pin)
		    counter += 1
		    puts ""
		else
		    puts " 				<-- dup skipped"
		    dups += 1
		end
		}
	    puts ""
	    puts counter.to_s + " emails sent."
	    puts dups.to_s + " dups found."
  end
  
  desc "Send emails out notifying volunteers of upcoming day."
  task :send_volunteer_update => :environment do
	counter = 0
	dups = 0
	    # Change this email name to send a new email to people
	    email_name = "2010dayupdate"
	    @volunteers = Volunteer.find(:all, {:conditions => "volunteers.project_id = #{Project.latest.id}", :include => [{:contact => :skills},:house]})
	    @volunteers.each { |volunteer|
		contact = volunteer.contact
		print contact.email
		
		# looks for dups here, prevents someone getting same email twice
		t = Sentemail.find_dups(contact.email,email_name)
		if t.nil?
		    # make record of send
		    s = Sentemail.new
		    s.email = contact.email
		    s.email_name = email_name
		    s.contact_id = contact.id
		    s.pin = Sentemail.generate_pin(contact.id.to_s)
		    s.sent_at = DateTime.now()
		    s.save
		    print "("+contact.id.to_s+")"
		    Notification.deliver_volunteer_update(contact,s.pin)
		    counter += 1
		    puts ""
		else
		    puts " 				<-- dup skipped"
		    dups += 1
		end
		}
	    puts ""
	    puts counter.to_s + " emails sent."
	    puts dups.to_s + " dups found."
  end
  

    desc "TEST::Send emails out notifying volunteers of upcoming day."
  task :test_send_volunteer_update => :environment do
	counter = 0
	dups = 0
	    # Change this email name to send a new email to people
	    email_name = "2010dayupdate"
	    @volunteers = Volunteer.find(:all, {:conditions => "volunteers.project_id = #{Project.latest.id}", :include => [{:contact => :skills},:house]})
	    @volunteers.each { |volunteer|
		contact = volunteer.contact
		print contact.email
		
		# looks for dups here, prevents someone getting same email twice
		t = Sentemail.find_dups(contact.email,email_name)
		if t.nil?
		    # make record of send
		    s = Sentemail.new
		    s.email = contact.email
		    s.email_name = email_name
		    s.contact_id = contact.id
		    s.pin = Sentemail.generate_pin(contact.id.to_s)
		    s.sent_at = DateTime.now()
		    s.save
		    print "("+contact.id.to_s+")"
		    if contact.email == "joshua.siler@gmail.com" || contact.email == "joshuas@bnj.com"
			Notification.deliver_volunteer_update(contact,s.pin)
		    end
		    counter += 1
		    puts ""
		else
		    puts " 				<-- dup skipped"
		    dups += 1
		end
		}
	    puts ""
	    puts counter.to_s + " emails sent."
	    puts dups.to_s + " dups found."
  end
  
      desc "Clears all pins out for 2010dayupdate"
  task :clear_pins => :environment do
    Sentemail.connection.execute("delete from sentemails where email_name = '2010dayupdate'")
  end
end
