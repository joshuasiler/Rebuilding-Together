class ContactsController < ApplicationController
  
  def new
    @contact = Contact.new(params[:contact])
  end
end

