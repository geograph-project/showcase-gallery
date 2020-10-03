<?

include "includes/database.php";
include "includes/mysql-config.inc.php";

mysql_query("drop table if exists gallery_ids");
mysql_query("create table gallery_ids (primary key (`id`)) engine=myisam select floor(substring_index(url,'/',-1)) as id,users,showday,baysian,fetched,first_vote,last_vote from gallery_image where length(title) > 2");

header("Content-Type: text/plain");
header("Content-Disposition: attachment; filename=\"gallery_ids.mysql\"");
passthru("mysqldump -h$db_host -u$db_user -p$db_passwd $db_name gallery_ids --comments=FALSE --compact");
