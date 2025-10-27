#!/bin/sh
php-fpm -D

#Supervisor
supervisord -n -c /etc/supervisord.conf

#Start cron
crond -f
