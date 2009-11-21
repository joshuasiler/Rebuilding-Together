require 'data_grid'
require 'cgi'

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
      if @query["any_skills"]
        @cond.any_skills = true
      elsif @query["skills"]
        @cond.skills = @query["skills"].split(",")
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
      
      @cond.include_inactive = !! @query["inactive"]
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
    @cond = Conditions.from_param(request["cond"])
    @list = Contact.find(:all, :conditions => @cond.conditions)

    csv_string = FasterCSV.generate do |csv|
      csv << Contact.column_names

      @list.each do |record|
        csv << record.attributes.collect { |k, v| v }
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
    # A boolean, defaults to false. Overrides skills list.
    attr_accessor :any_skills

    def initialize
      @skills = []
      @assigned = false
      @unassigned = false
      @include_inactive = false
      @any_skills = false
    end

    def conditions
      cond = []
      if @any_skills
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

      if @include_inactive
        cond << "is_active = 1"
      end

      cond.join " AND "
    end
    
    def clear
      @skills = nil
      @not_assigned = false
      @include_inactive = false
      @any_skills = false
    end

    # A string containing all the query conditions which can be used
    # as the value for a URL parameter
    def to_param
      # split on newlines, join with ampersands, and escape
      CGI.escape(<<-QRY.split.join("&"))
skills=#{@skills ? @skills.join(",") : ""}
not_assigned=#{!! @not_assigned}
include_inactive=#{!! @include_inactive}
any_skills=#{!! @any_skills}
QRY
    end

    # Restores query conditions from the string given. The
    # string must be one produced by to_param
    def self.from_param(val)
      c = Conditions.new
      if val && ! val.blank?
        vals = CGI.parse(CGI.unescape(val))
        c.skills = vals.has_key?("skills") ? vals["skills"].split(",") : nil
        c.unassigned = vals.has_key?("not_assigned") ? (!! vals["not_assigned"]) : false
        c.include_inactive = vals.has_key?("include_inactive") ? (!! vals["include_inactive"]) : false
        c.any_skills = vals.has_key?("any_skills") ? (!! vals["any_skills"]) : false
      end
      c
    end
  end
end
