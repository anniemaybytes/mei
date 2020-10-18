#!/bin/bash
set -e

if [ "$EUID" -ne 0 ]; then
    echo "Must be run as root"
    exit
fi

export DEBIAN_FRONTEND=noninteractive

echo
echo Copying over configs...
cd /vagrantroot/configs
cp -avu * /
chmod 755 /etc/mysql/mariadb.cnf
echo never >/sys/kernel/mm/transparent_hugepage/defrag
echo never >/sys/kernel/mm/transparent_hugepage/enabled

echo
echo Configuring daemons...
systemctl daemon-reload
systemctl disable nginx
systemctl disable php7.4-fpm
systemctl disable mariadb

echo
echo Stopping daemons...
systemctl stop nginx
systemctl stop php7.4-fpm
systemctl stop mariadb
systemctl stop cron

echo
echo Updating composer from lock file...
cd /code
composer self-update
su -s /bin/bash vagrant -c 'composer install'

echo
echo Starting MariaDB...
systemctl start mariadb

echo
echo Migrating...
cd /code
su -s /bin/bash vagrant -c 'composer phinx migrate'

echo
echo Starting daemons...
systemctl start nginx
systemctl start php7.4-fpm
systemctl start cron
