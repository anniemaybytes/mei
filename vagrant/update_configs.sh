#!/bin/bash
echo
echo Copying over configs...
sudo cp -avu /vagrantroot/configs/* /
chmod 755 /etc/mysql/my.cnf
sudo chown -R root:root /etc/mysql/{,mariadb.}conf.d
sudo chmod -R 644 /etc/mysql/{,mariadb.}conf.d/
sudo chmod +x /etc/mysql/{,mariadb.}conf.d/
echo never > /sys/kernel/mm/transparent_hugepage/defrag
echo never > /sys/kernel/mm/transparent_hugepage/enabled

echo
echo Stopping daemons...
service nginx stop
service php7.0-fpm stop
service mysql stop

echo
echo Starting daemons...
service nginx start
service php7.0-fpm start
service mysql start
