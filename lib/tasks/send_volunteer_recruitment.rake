namespace :emailops do

  desc "Send emails out notifying contacts of upcoming day."
  task :send_volunteer_recruitment => :environment do
	counter = 0
	    # Change this email name to send a new email to people
	    email_name = "2010daynotification"
	    @contacts = Contact.find(:all, :conditions => "optout = 0", :order => "created_at desc")
	    @contacts.each { |contact|
		puts contact.email
		
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
		    #Notification.deliver_volunteer_notification(contact)
		    counter += 1
		else
		    puts "dup skipped"
		end
		}
	    puts counter.to_s + " emails sent."
  end

    desc "Send emails out notifying contacts of upcoming day."
  task :test_send_volunteer_recruitment => :environment do
	counter = 0
	    # Change this email name to send a new email to people
	    email_name = "2010daynotification"
	    @contacts = Contact.find(:all, :conditions => "optout = 0", :order => "created_at desc")
	    @contacts.each { |contact|
		puts contact.email
		
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
		    if contact.email == "joshua.siler@gmail.com" || contact.email == "joshuas@bnj.com"
			Notification.deliver_volunteer_notification(contact)
		    end
		    counter += 1
		else
		    puts "dup skipped"
		end
		}
	    puts counter.to_s + " emails sent."
  end

  
end
