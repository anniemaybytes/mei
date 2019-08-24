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
sudo systemctl stop nginx
sudo systemctl stop php7.3-fpm
sudo systemctl stop mariadb

echo
echo Starting daemons...
sudo systemctl start nginx
sudo systemctl start php7.3-fpm
sudo systemctl start mariadb
