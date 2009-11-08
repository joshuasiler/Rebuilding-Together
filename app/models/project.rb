class Project < ActiveRecord::Base
  def self.latest
    Project.find_by_sql("select * from projects order by created_at desc limit 1")[0]
  end
end
