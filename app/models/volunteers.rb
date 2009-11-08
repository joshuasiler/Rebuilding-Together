class Volunteers < ActiveRecord::Base
  has_many :contacts

  def self.groups
    latest_project = Project.find_by_sql(<<-SQL).inject(0) { |_, project| project.id }
select id 
from projects
order by created_at DESC
limit 1
SQL

    Volunteers.find_by_sql(<<-SQL).collect { |g| g.group_name.to_s }
select group_name 
from volunteers
where project_id = #{latest_project}
group by group_name
order by group_name ASC
SQL
  end
end
