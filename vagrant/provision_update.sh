#!/bin/bash
set -e

export DEBIAN_FRONTEND=noninteractive

echo
echo Copying over configs...
cd /vagrantroot/configs
cp -av * /
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

cd /code

echo
echo Updating composer from lock file
sudo -u vagrant composer install
