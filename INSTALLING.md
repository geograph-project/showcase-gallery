######################

-- Create database user --

Run these commands (eg with phpmyadmin or the mysql command line client!) 

    CREATE DATABASE `showcase`;

    CREATE USER 'showcase'@'%' IDENTIFIED BY 'change-this';

    -- these are the minimum privileges needed. Partcully useful, if you dont have SUPER etc, so can't just use 'ALL'
    -- note, lack of delete! Although DROP is needed, as the export functions needs to create tables. Drop is needed even to execute TRUNCATE.

    GRANT SELECT, INSERT, UPDATE, CREATE, INDEX, DROP, CREATE TEMPORARY TABLES, LOCK TABLES ON `showcase`.* TO 'showcase'@'%';

######################

-- Download files --

eg download the .zip file from github, or just use git clone!

    cp includes/mysql-config.example includes/mysql-config.inc.php

and edit `mysql-config.inc.php` to have proper database creditials. 

######################

-- import schema --

    USE `showcase`;

    source schema.mysql

######################

If you importing an old database that only had ipv4 support, then may need to convert existing rows, 
Heres a useful trick. 

    ALTER TABLE `showcase`.`gallery_email` MODIFY `ipaddr` varbinary(16) NOT NULL;
    UPDATE `showcase`.`gallery_email` SET `ipaddr` = inet6_aton(inet_ntoa(`ipaddr`)), `updated` = `updated` WHERE `ipaddr` regexp binary '^[[:digit:]]+$';

    ALTER TABLE `showcase`.`gallery_log` MODIFY `ipaddr` varbinary(16) NOT NULL;
    UPDATE `showcase`.`gallery_log` SET `ipaddr` = inet6_aton(inet_ntoa(`ipaddr`)) WHERE `ipaddr` regexp binary '^[[:digit:]]+$';


Decodes value from inet_aton and re-encodes with inet6_aton (which ends up as a binary string, not simple number!), was previousl a 'int unsigned' column, which was fine with ipv4. 
The code now uses inet6_aton when writing values.


######################

And if need to convert to https!

    update gallery_image set url = replace(url,'http://','https://'), updated=updated where url like 'http://www.geograph.org.uk%';

    update gallery_image set fullsize = replace(fullsize,'http://','https://'), updated=updated where fullsize like 'http://%';

    update gallery_image set thumbnail = replace(thumbnail,'http://','https://'), updated=updated where thumbnail like 'http://%';


