# Be sure to restart your server when you modify this file.

# Your secret key for verifying cookie session data integrity.
# If you change this key, all old sessions will become invalid!
# Make sure the secret is at least 30 characters and all random, 
# no regular words or you'll be exposed to dictionary attacks.
ActionController::Base.session = {
  :key         => '_rebuilding_session',
  :secret      => '105b13746ad7c9029b290e1a4d0dd9074a951e74d7757a323a06a76e5d1a7d20c7f30b437e1714669befb91545af99e24da5be262b81bddd99714ea441725e26'
}

# Use the database for sessions instead of the cookie-based default,
# which shouldn't be used to store highly confidential information
# (create the session table with "rake db:sessions:create")
# ActionController::Base.session_store = :active_record_store
