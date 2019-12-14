#!/bin/bash
set -e

export DEBIAN_FRONTEND=noninteractive

echo
echo Copying over configs...
cd /vagrantroot/configs
cp -av * /
chmod 755 /etc/mysql/my.cnf
chown -R root:root /etc/mysql/{,mariadb.}conf.d
chmod -R 644 /etc/mysql/{,mariadb.}conf.d/
chmod +x /etc/mysql/{,mariadb.}conf.d/
echo never > /sys/kernel/mm/transparent_hugepage/defrag
echo never > /sys/kernel/mm/transparent_hugepage/enabled

echo
echo Stopping daemons...
systemctl stop nginx
systemctl stop php7.3-fpm
systemctl stop mariadb

echo
echo Starting daemons...
systemctl daemon-reload
systemctl start nginx
systemctl start php7.3-fpm
systemctl start mariadb

cd /code

echo
echo Updating composer from lock file
sudo -u vagrant composer install
