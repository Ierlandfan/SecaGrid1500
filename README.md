# SecaGrid1500
SecaGrid1500 monitoring script

Modified script for logging a SecaGrid 1500 by using the Erhernet port and new firmware as of ~ 04-2017. 
Original script is written by Anton Boonstra and used a different suburl to get the measurements values.
Since I didn't have that suburl I sniffed the page and found measurements.xml.
I modified $energy by removing the "trim" statement because well, now it works again.
Script is added to crontab to send data to PVoutput.org every 5 minutes.
The benefit of this script is that it can also output to Domoticz, a SQL database or Grafana since all the values can be exported.

12-6-2017 Ierlandfan

Based on a SecaGrid logging script written by Anton Boonstra

Modules needed php-xml and php-curl.
