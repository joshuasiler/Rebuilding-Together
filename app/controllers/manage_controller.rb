require 'data_grid'
require 'cgi'
require 'faster_csv'

# "customer support lookup" could be name, company, email, phone number
class ManageController < ApplicationController
  layout "manage", :except => [:sort_grid, :page_grid]

  def initialize
    @csv_columns = %w(company house skills)
    @display_columns = %w(last_name email) + @csv_columns
    super
  end

  # Sort in-place 
  def sort_grid
    @cond = Conditions.from_param(request["sort"])
    @page = request["curr_page"].to_i

    grid @cond, @page
    render :partial => "grid.html.erb"
  end

  # Page in-place
  def page_grid
    @cond = Conditions.from_param(request["cond"])
    @page = request["page"].to_i

    grid @cond, @page
    render :partial => "grid.html.erb"
  end

  def house_captains
    # "house captain search" is just looking up all the people
    # assigned to a house (so they can email the house captain with
    # his people's names, emails, phone numbers)
    @page = 0
    # Restore query form state if "filter" button
    # pressed.
    @query = request["query"] || Hash.new
    @cond = Conditions.new

    if @query["filter"]
      unless @query["project"].blank? 
        @cond.project = @query["project"].to_i
      end

      unless @query["house"].blank?
        @cond.house = @query["house"].to_i
      end

      unless @query["house_captain"].blank?
        @cond.house_captain = @query["house_captain"].to_i
      end
    elsif @query["clear"]
      @query.clear
      @cond.project = Project.latest.id
    else
      @cond.project = Project.latest.id
    end

    grid @cond, @page
  end
  
  def volunteer_search 
    @page = 0
    # Restore query form state if "filter" button
    # pressed.
    @query = request["query"] || Hash.new
    @cond = Conditions.new

    if @query["filter"]
      if ! @query["name"].blank?
        @cond.name = @query["name"]
      end

      if ! @query["group"].blank?
        @cond.group = @query["group"]
      end

      if ! @query["phone"].blank?
        @cond.phone = @query["phone"]
      end

      if ! @query["email"].blank?
        @cond.email = @query["email"]
      end

      if ! @query["contact_type"].blank?
        @cond.contact_type = @query["contact_type"].to_i
      end
    elsif @query["clear"]
      @query.clear
    end

    grid @cond, @page
  end

  def assign_volunteers
    # "House assignment search" could be only volunteers in a
    # particular year, only people who haven't been assigned yet, and
    # presents the skills and company name and person name filters.
    @page = 0
    # Restore query form state if "filter" button
    # pressed.
    @query = request["query"] || Hash.new
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
      when "3"
        @cond.assigned = false
        @cond.unassigned = false
      when "4"
        @cond.assigned = false
        @cond.unassigned = false
        @cond.house = @query["house"].to_i
      end

      @cond.group = @query["group"] unless @query["group"].blank?
      @cond.name = @query["name"] unless @query["name"].blank?
      @cond.include_inactive = (@query["inactive"] == "1")
    elsif @query["clear"]
      @query.clear
    end
    grid @cond, @page
  end

  def index
  end

  def add
  end

  def update
  end
  
  def download
    
    list = contact_data(Conditions.from_param(request["cond"]))

    csv_string = FasterCSV.generate do |csv|
      columns = Contact.content_columns.collect { |c| c.name } + @csv_columns
      csv << Contact.content_columns.collect { |c| c.human_name } + @csv_columns

      list.each do |record|
        csv << columns.collect { |col| contact_column(record, col) }
      end
    end

    send_data(csv_string,
              :type => 'text/csv; charset=utf-8; header=present',
              :filename => "contacts.csv")
  end
  
private

  def conditions
    return cond, query
  end

  # Create and configure grid displayed.
  def grid(cond, page)
    @grid = DataGrid.new :grid
    @grid.configure do |g|
      @per_page = 20
      @record_count = Contact.count(:conditions => cond.conditions)

      g.get_data do |state|
        contact_data(cond, page)
      end

      g.get_columns do |state, contact|
        @display_columns.collect do |col| 
          [col] << contact_column(contact, col)
        end
      end
    end
  end

  # Retrieve contact data based on the conditions given. Used for CSV and HTML. All
  # data is returned if page is not given.
  def contact_data(cond, page = nil)
    opts = { :conditions => cond.conditions, :include => [:skills, :houses],
      :order => cond.ordering }

    unless page.nil?
      opts.merge!(:offset => (page * @per_page), :limit => @per_page)
    end

    Contact.find(:all, opts)
  end

  # Retrieve one column from a contact record.
  # Used for CSV and HTML display. 
  def contact_column(contact, col)
    case col
    when "last_name"
      "#{contact.last_name}, #{contact.first_name}".strip
    when "skills"
      contact.skills.compact.collect { |skill| skill.description }.uniq.inject { |acc, skill| acc + ", " + skill }
    when "company"
      contact.company_name
    when "house"
      contact.current_house ? contact.current_house.address : ""
    else
      contact[col].to_s
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
    # Limits query to contacts with the name given. 
    attr_accessor :name
    # Sort column
    attr_accessor :sort_by
    # True if ascending sort, false otherwise.
    attr_accessor :sort_ascending
    # ID of the house that contacts are assigned to. Nil by default, means
    # don't limit by house.
    attr_accessor :house
    # ID of project that contacts are associated with. Nil by default, means
    # don't limit by project.
    attr_accessor :project
    # ID of house captain for project that volunteers are assigned to. Nil by default,
    # means don't limit by house captain.
    attr_accessor :house_captain
    # Limits query to contacts with the email given. 
    attr_accessor :email
    # Limits query to contacts with the phone given. 
    attr_accessor :phone
    # Limits query to contacts of the specified type.
    attr_accessor :contact_type

    def initialize
      @skills = []
      @assigned = false
      @unassigned = false
      @include_inactive = false
      @any_skills = false
      @no_skills = false
      @group = nil
      @name = nil
      @sort_by, @sort_ascending = "last_name", true
      @house = nil
      @project = nil
      @house_captain = nil
      @email = nil
      @phone = nil
      @contact_type = nil
    end

    def conditions
      cond = []
      if @no_skills
        cond << "contacts.id not in (select contact_id from contact_skills)"
      elsif @any_skills
        cond << "contacts.id in (select contact_id from contact_skills)"
      elsif @skills && @skills.length > 0
        cond << <<-SQL
contacts.id in (select contact_id 
                from contact_skills 
                where skill_id in (#{@skills.collect { |s| "'#{mysql_escape((s || "").to_s)}'"}.join(",")}))
SQL
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
        cond << "company_name LIKE '#{mysql_escape(@group.strip)}%'"
      end

      if ! @name.blank?
        cond << <<-SQL
(last_name LIKE '%#{mysql_escape(@name.strip)}%' OR 
 first_name LIKE '%#{mysql_escape(@name.strip)}%' OR
 CONCAT(last_name, ", ", first_name) LIKE '%#{mysql_escape(@name.strip)}%' OR
 CONCAT(last_name, ",", first_name) LIKE '%#{mysql_escape(@name.strip)}%')
SQL
      end

      if ! @house.blank?
        cond << <<-SQL
contacts.id in (select contact_id from volunteers where house_id = #{@house})
SQL
      end

      if ! @project.blank?
        cond << <<-SQL
contacts.id in (select contact_id from volunteers where project_id = #{@project})
SQL
      end

      if ! @house_captain.blank?
        cond << <<-SQL
contacts.id in (select contact_id from volunteers where house_id in 
                       (select house_id from houses
                        where house_captain_contact_id = #{@house_captain} or
                              house_captain_2_contact_id = #{@house_captain}))
SQL
      end

      if ! @email.blank?
        cond << <<-SQL
(email LIKE '%#{mysql_escape(@email.strip)}%')
SQL
      end

      if ! @phone.blank?
        cond << <<-SQL
(home_phone LIKE '%#{mysql_escape(@phone.strip)}%' OR 
mobile_phone LIKE '%#{mysql_escape(@phone.strip)}%' OR 
fax LIKE '%#{mysql_escape(@phone.strip)}%' OR 
pager LIKE '%#{mysql_escape(@phone.strip)}%') 
SQL
      end

      if ! @contact_type.blank?
        cond << <<-SQL
contacts.id in (select contact_id 
  from contact_contacttypes
  where contact_type_id = #{@contact_type})
SQL
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
      @name = nil
      @sort_by, @sort_ascending = "last_name", true
      @house = nil
      @project = nil
      @house_captain = nil
      @phone = nil
      @email = nil
      @contact_type = nil
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
group=#{p[:group] || (@group ? @group : "")}
name=#{p[:name] || (@name ? @name : "")}
sort_by=#{p[:sort_by] || @sort_by}
sort_asc=#{p[:sort_ascending] == nil ? (!! @sort_ascending) : (!! p[:sort_ascending])}
house=#{p[:house] || (@house || "")}
project=#{p[:project] || (@project || "")}
house_captain=#{p[:house_captain] || (@house_captain || "")}
phone=#{p[:phone] || (@phone || "")}
email=#{p[:email] || (@email || "")}
contact_type=#{p[:contact_type] || (@contact_type || "")}
QRY
    end
    
    # Restores query conditions from the string given. The
    # string must be one produced by to_param
    def self.from_param(val)
      make_from_params(val) do |c|
        c.skills = c.for_key("skills", nil) { |v| v.split(",") }
        c.unassigned = c.for_key("not_assigned", false) { |v| v == "true" }
        c.assigned = c.for_key("assigned", false) { |v| v == "true" }
        c.include_inactive = c.for_key("include_inactive", false) { |v| v == "true" }
        c.any_skills = c.for_key("any_skills", false) { |v| v == "true" }
        c.no_skills = c.for_key("no_skills", false) { |v| v == "true" }
        c.group = c.for_key("group", nil) { |v| v }
        c.name = c.for_key("name", nil) { |v| v }
        c.sort_by = c.for_key("sort_by", "last_name") { |v| v }
        c.sort_ascending = c.for_key("sort_asc", true) { |v| v == "true" }
        c.house = c.for_notblank("house", nil) { |v| v.to_i }
        c.project = c.for_notblank("project", nil) { |v| v.to_i }
        c.house_captain = c.for_notblank("house_captain", nil) { |v| v.to_i }
        c.phone = c.for_key("phone", nil) { |v| v }
        c.email = c.for_key("email", nil) { |v| v }
        c.contact_type = c.for_notblank("contact_type", nil) { |v| v.to_i }
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

    # Convenience method. If the key is found in the vals hash and its
    # value is not blank, pass the first element of value array to the
    # block given and return the result. Otherwise, return the default
    # value given.
    def for_notblank(key, default)
      (@vals.has_key?(key) && (! @vals[key].first.blank?)) ? yield(vals[key].first) : default
    end

    # Properly escape strings for MySQL. nil string becomes empty.
    def mysql_escape(str)
      str ? quote_string(str.strip.gsub('%', '\%').gsub('_', '\_')) : ""
    end
  end
end
