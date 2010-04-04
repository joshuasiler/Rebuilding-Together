class Sentemail < ActiveRecord::Base
  def self.generate_pin(prefix)
    5.times { prefix += ['0','1','2','3','4','5','6','7','8','9','a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z'][rand(35)] }
    prefix 
  end
  
  def self.find_dups(email,email_name)
    Sentemail.find_by_sql(["select * from sentemails where email = ? and email_name = ? ", email,email_name])[0]
  end
end
