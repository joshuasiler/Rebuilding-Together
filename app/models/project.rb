class Project < ActiveRecord::Base
  has_many :houses

  def self.latest
    @latest ||= Project.find_by_sql("select * from projects order by starts_on desc limit 1")[0]
  end

end
