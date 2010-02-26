namespace :emailops do

  desc "Send emails out notifying contacts of upcoming day."
  task :send_volunteer_recruitment => :environment do
		@volunteers = Contact.find(:all, :conditions => "optout = 0")
		@volunteers.each { |volunteer|
			puts volunteer.email
			}
  end

  
end
