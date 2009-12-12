require 'data_grid'
require 'cgi'
require 'faster_csv'

class ManageController < ApplicationController

  def initialize
    @display_columns = %w(name email company skills house)
    super
  end

  def index
    @grid = DataGrid.new
    
    # Restore query form state if "filter" button
    # pressed.
    @query = 
      if request["query"] && request["query"]["filter"] 
        request["query"]
      else
        Hash.new
      end

    @cond = Conditions.new
    if @query["filter"]
      case @query["skill_sel"]
      when "1"
        @cond.any_skills = false
        @cond.no_skills = false
        @cond.skills = nil
      when "2"
        @cond.any_skills = false
        @cond.no_skills = true
        @cond.skills = nil
      when "3"
        @cond.any_skills = true
        @cond.no_skills = false
        @cond.skills = nil
      when "4"
        @cond.any_skills = false
        @cond.no_skills = false
        @cond.skills = @query["skills"]
      end
      
      case @query["assigned"]
      when "1"
        @cond.assigned = true
        @cond.unassigned = false
      when "2"
        @cond.assigned = false
        @cond.unassigned = true
      else
        @cond.assigned = true
        @cond.unassigned = true
      end
      
      @cond.include_inactive = (@query["inactive"] == "1")
    end
    
    @grid.configure do |g|
      g.model = Contact
      g.get_data do |state, model|
        model.find(:all, :conditions => @cond.conditions)
      end

      g.get_columns do |state, model, contact|
        @display_columns.collect do |col| 
          case col
          when "name"
            [col, "#{contact.first_name} #{contact.last_name}".strip]
          when "skills"
            [col, contact.skills.inject { |acc, skill| acc + ", " + skill }.to_s]
          when "company"
            [col, contact.company_name]
          else
            [col, contact[col].to_s]
          end
        end
      end
    end
  end

  def add
  end

  def update
  end
  
  def download
    cond = Conditions.from_param(request["cond"])
    puts "Got conditions: #{cond.inspect}"
    list = Contact.find(:all, :conditions => cond.conditions)

    csv_string = FasterCSV.generate do |csv|
      columns = Contact.column_names
      csv << columns

      list.each do |record|
        csv << columns.collect { |col| record[col].to_s }
      end
    end

    send_data(csv_string,
      :type => 'text/csv; charset=utf-8; header=present',
      :filename => "contacts.csv")
  end

  # Helps build conditions statement for
  # contact query on this page.
  class Conditions
    include ::ActiveRecord::ConnectionAdapters::Quoting

    # An array of skill IDs. Defaults to empty.
    attr_accessor :skills
    # A boolean, defaults to false
    attr_accessor :assigned
    # A boolean, defaults to false
    attr_accessor :unassigned
    # A boolean, defaults to false.
    attr_accessor :include_inactive
    # A boolean, defaults to false. Selects only contacts
    # with skills. Overrides skills list.
    attr_accessor :any_skills
    # A boolean, defaults to false. Selects only contacts
    # w/o skills. Overrides any_skills.
    attr_accessor :no_skills

    def initialize
      @skills = []
      @assigned = false
      @unassigned = false
      @include_inactive = false
      @any_skills = false
      @no_skills = false
    end

    def conditions
      cond = []
      if @no_skills
        cond << "id not in (select contact_id from contact_skills)"
      elsif @any_skills
        cond << "id in (select contact_id from contact_skills)"
      elsif @skills && @skills.length > 0
        cond << %(id in (select contact_id from contact_skills where skill_id in (#{@skills.collect { |s| "'#{quote_string((s || "").to_s)}'"}.join(",")})))
      end

      assigned_contacts = <<-SQL
      (select v.contact_id 
       from volunteers v inner join
            projects p on v.project_id = p.id
       where p.ends_on is null OR p.ends_on > now())
SQL

      if @assigned && ! @unassigned
        cond << "id in (#{assigned_contacts})"
      elsif ! @assigned && @unassigned
        cond << "id not in (#{assigned_contacts})"
      end

      if ! @include_inactive
        cond << "is_active = 1"
      end

      cond.join " AND "
    end
    
    def clear
      @skills = nil
      @unassigned = false
      @assigned = false
      @include_inactive = false
      @any_skills = false
      @no_skills = false
    end

    # A string containing all the query conditions which can be used
    # as the value for a URL parameter
    def to_param
      # split on newlines, join with ampersands, and escape
      CGI.escape(<<-QRY.split.join("&"))
skills=#{@skills ? @skills.join(",") : ""}
unassigned=#{!! @unassigned}
assigned=#{!! @assigned}
include_inactive=#{!! @include_inactive}
any_skills=#{!! @any_skills}
no_skills=#{!! @no_skills}
QRY
    end
    
    # Restores query conditions from the string given. The
    # string must be one produced by to_param
    def self.from_param(val)
      puts "Got val: #{val.inspect}"
      if val && ! val.blank?
        make_conditions do |c|
          vals = CGI.parse(CGI.unescape(val))
          puts "Parsed to vals: #{vals.inspect}"
          c.skills = for_key(vals, "skills", nil) { |v| v.split(",") }
          c.unassigned = for_key(vals, "not_assigned", false) { |v| v == "true" }
          c.assigned = for_key(vals, "assigned", false) { |v| v == "true" }
          c.include_inactive = for_key(vals, "include_inactive", false) { |v| v == "true" }
          c.any_skills = for_key(vals, "any_skills", false) { |v| v == "true" }
          c.no_skills = for_key(vals, "no_skills", false) { |v| v == "true" }
        end
      else
        nil
      end
    end

  private
    # Convenience method - lets us construct
    # an instance, configure it, and return the result.
    def self.make_conditions
      c = Conditions.new
      yield(c)
      c
    end

    # Convenience method. If the key is found in the vals
    # hash, pass the first element of value array to the 
    # block given and return the result. Otherwise, return
    # the default value given.
    def self.for_key(vals, key, default)
      vals.has_key?(key) ? yield(vals[key].first) : default
    end
  end
end
