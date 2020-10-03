# Showcase Gallery

A mini-website showing images from Geograph Projects. 

Allows users to vote on images (with a basic 1-5 stars) and then creates galleries of high rated images. 

In theory works with multiple Geograph Projects (ie images can be used from any) but 
generally its only been used with Geograph Britain and Ireland, 
not tested with any others. 

Started as sub-project of Geograph.org (the worldwide parent site), but is currently hosted by Geograph Britain and Ireland

The URL is subject to change, but access the live site at https://www.geograph.org/gallery.php
(which will redirect to the current hosting) 

Or https://www.geograph.org/gallery.php?mobile=1 on a mobile device. 

###########

General Requirements are a **PHP5.6** (it may work with later, but untested! Although still uses the old mysql_* client functions!) 

... expects `short_open_tags = on` !

Been built to run in Apache, but probably not a requirement. 

In general it expects the server define the charset as "charset=ISO-8859-1" (ie to get output on Content-Type headers!!)

And **Mysql 5.5+**. Although it might work with innodb as default engine, its generally designed with default engine as myiasm. 

###########

The current code is a direct dump of the code as been running on our old servers.

... We are shortly going to be running the code with a MariaDB 10.4 server (but still PHP 5.6), so will be commiting code changes (if needed) 




