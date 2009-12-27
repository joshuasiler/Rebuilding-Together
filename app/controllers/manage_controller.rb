require 'data_grid'
require 'cgi'
require 'faster_csv'

class ManageController < ApplicationController
  layout "mainsite", :except => [:sort_grid, :page_grid]

  def initialize
    @display_columns = %w(last_name email company house skills)
    super
  end

  # Sort in-place 
  def sort_grid
    @cond = Conditions.new
    @cond = Conditions.from_param(request["sort"])
    @page = request["curr_page"].to_i
    grid @cond, @page
    render :partial => "grid.html.erb"
  end

  def page_grid
    @cond = Conditions.new
    @cond = Conditions.from_param(request["cond"])
    @page = request["page"].to_i
    grid @cond, @page
    render :partial => "grid.html.erb"
  end

  def index
    # Restore query form state if "filter" button
    # pressed.
    @query = request["query"] || Hash.new
    @cond = Conditions.new
    @page = 0

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
        @cond.assigned = false
        @cond.unassigned = false
      end

      @cond.group = @query["group"] unless @query["group"].blank?
      @cond.include_inactive = (@query["inactive"] == "1")
    end

    grid @cond, @page
  end

  def add
  end

  def update
  end
  
  def download
    cond = Conditions.from_param(request["cond"])
    list = Contact.find(:all, :conditions => cond.conditions, :order => cond.ordering)

    csv_string = FasterCSV.generate do |csv|
      columns = Contact.column_names << "skills"
      csv << columns

      list.each do |record|
        csv << columns.collect { |col| record[col].to_s }
      end
    end

    send_data(csv_string,
      :type => 'text/csv; charset=utf-8; header=present',
      :filename => "contacts.csv")
  end

private

  def grid(cond, page)
    @grid = DataGrid.new :grid
    @grid.configure do |g|
      @per_page = 20
      @record_count = Contact.count(:conditions => cond.conditions)
      g.model = Contact
      g.get_data do |state, model|
        model.find(:all, :conditions => cond.conditions, :include => :skills,
                   :offset => (page * @per_page), :limit => @per_page, 
                   :order => cond.ordering)
      end

      g.get_columns do |state, model, contact|
        @display_columns.collect do |col| 
          case col
          when "last_name"
            [col, " #{contact.last_name}, #{contact.first_name}".strip]
          when "skills"
            [col, contact.skills.compact.collect { |skill| skill.description }. inject { |acc, skill| acc + ", " + skill }]
          when "company"
            [col, contact.company_name]
          else
            [col, contact[col].to_s]
          end
        end
      end
    end
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
    # Limits query to groups which match the name given. Ignored
    # if blank.
    attr_accessor :group
    # Sort column
    attr_accessor :sort_by
    # True if ascending sort, false otherwise.
    attr_accessor :sort_ascending

    def initialize
      @skills = []
      @assigned = false
      @unassigned = false
      @include_inactive = false
      @any_skills = false
      @no_skills = false
      @group = nil
      @sort_by, @sort_ascending = "last_name", true
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
        cond << "contacts.id in (#{assigned_contacts})"
      elsif ! @assigned && @unassigned
        cond << "contacts.id not in (#{assigned_contacts})"
      end

      if ! @include_inactive
        cond << "is_active = 1"
      end

      if ! @group.blank?
        cond << "company_name LIKE '#{quote_string(@group)}%'"
      end

      cond.join " AND "
    end
    
    def ordering
      case @sort_by
      when "email"
        "email"
      when "company"
        "company_name"
      when "house"
        @sort_by
      when "skills"
      when "last_name"
        @sort_by
      else
        @sort_by
      end + " " + (@sort_ascending ? "ASC" : "DESC")
    end

    def clear
      @skills = nil
      @unassigned = false
      @assigned = false
      @include_inactive = false
      @any_skills = false
      @no_skills = false
      @group = nil
      @sort_by, @sort_ascending = "last_name", true
    end

    # A string containing all the query conditions which can be used
    # as the value for a URL parameter
    def to_param(p = {})
      
      # split on newlines, join with ampersands, and escape
      CGI.escape(<<-QRY.split.join("&"))
skills=#{p[:skills] || (@skills ? @skills.join(",") : "")}
unassigned=#{p[:unassigned] == nil ? (!! @unassigned) : (!! p[:unassigned])}
assigned=#{p[:assigned] == nil ? (!! @assigned) : (!! p[:assigned])}
include_inactive=#{p[:include_inactive] == nil ? (!! @include_inactive) : (!! p[:include_inactive])}
any_skills=#{p[:any_skills] == nil ? (!! @any_skills) : (!! p[:any_skills])}
no_skills=#{p[:no_skills] == nil ? (!! @no_skills) : (!! p[:no_skills])}
sort_by=#{p[:sort_by] || @sort_by}
sort_asc=#{p[:sort_ascending] == nil ? (!! @sort_ascending) : (!! p[:sort_ascending])}
QRY
    end
    
    # Restores query conditions from the string given. The
    # string must be one produced by to_param
    def self.from_param(val)
      make_from_params(val) do |c|
        puts "Parsed to vals: #{c.vals.inspect}"
        c.skills = c.for_key("skills", nil) { |v| v.split(",") }
        c.unassigned = c.for_key("not_assigned", false) { |v| v == "true" }
        c.assigned = c.for_key("assigned", false) { |v| v == "true" }
        c.include_inactive = c.for_key("include_inactive", false) { |v| v == "true" }
        c.any_skills = c.for_key("any_skills", false) { |v| v == "true" }
        c.no_skills = c.for_key("no_skills", false) { |v| v == "true" }
        c.sort_by = c.for_key("sort_by", "last_name") { |v| v }
        c.sort_ascending = c.for_key("sort_asc", true) { |v| v == "true" }
      end
    end

    # Convenience method - lets us construct
    # an instance, configure it, and return the result.
    def self.make_from_params(val)
      c = Conditions.new
      c.vals = val ? CGI.parse(CGI.unescape(val)) : ""
      yield(c)
      c
    end

    attr_accessor :vals

    # Convenience method. If the key is found in the vals
    # hash, pass the first element of value array to the 
    # block given and return the result. Otherwise, return
    # the default value given.
    def for_key(key, default)
      @vals.has_key?(key) ? yield(vals[key].first) : default
    end
  end
end
