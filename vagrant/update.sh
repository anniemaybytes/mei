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
chown -R root:root /etc/mysql/conf.d /etc/cron.d/*
chmod 644 /etc/mysql/conf.d/* /etc/cron.d/*
chmod 755 /etc/mysql/mariadb.cnf
echo never >/sys/kernel/mm/transparent_hugepage/defrag
echo never >/sys/kernel/mm/transparent_hugepage/enabled

echo
echo Updating packages...
apt-get update
apt-get -qq -y -o Dpkg::Options::="--force-confold" --only-upgrade install php8.0* mariadb-server
apt-get -y autoremove && apt-get -y autoclean

echo
echo Configuring daemons...
systemctl daemon-reload
systemctl disable nginx
systemctl disable php8.0-fpm
systemctl disable mariadb

echo
echo Stopping daemons...
systemctl stop nginx
systemctl stop php8.0-fpm
systemctl stop mariadb
systemctl stop cron

echo
echo Updating composer from lock file...
cd /code
if composer --version -n | grep "1\."; then
    composer self-update -n --2 # update to composer v2
else
    composer self-update -n
fi
su -s /bin/bash vagrant -c 'composer install'

echo
echo Starting MariaDB...
systemctl start mariadb

echo
echo Migrating...
cd /code
su -s /bin/bash vagrant -c 'composer phpmig migrate'

echo
echo Starting daemons...
systemctl start nginx
systemctl start php8.0-fpm
systemctl start cron
