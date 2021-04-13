<?

include "includes/database.php";
include "includes/mysql-config.inc.php";

mysqli_query($db,"drop table if exists gallery_ids");
mysqli_query($db,"create table gallery_ids (primary key (`id`)) select substring_index(url,'/',-1) as id,users,showday,baysian,`avg`,fetched from gallery_image where length(title) > 2");

print "# (c)".date('Y')." Geograph Project - https://creativecommons.org/licenses/by-sa/2.0/\n";

header("Content-Type: text/plain");
passthru("echo 'select * from gallery_ids' | mysql -h$db_host -u$db_user -p$db_passwd $db_name");
