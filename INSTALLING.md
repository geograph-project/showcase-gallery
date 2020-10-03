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

