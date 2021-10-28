#!/bin/bash
set -e

if [ "$EUID" -ne 0 ]; then
    echo "Must be run as root"
    exit
fi

export DEBIAN_FRONTEND=noninteractive

echo Configuring system...
apt-mark hold linux-image-amd64 grub-pc # do not bloat image with new kernel
rm -rf /var/log/journal && systemctl restart systemd-journald

echo
echo Updating base system...
apt-get update --allow-releaseinfo-change
apt-get -qq -y -o Dpkg::Options::="--force-confnew" dist-upgrade
apt-get -y autoremove && apt-get -y autoclean

echo
echo Adding repositories...
cd /vagrantroot/configs/etc/apt
cp -avu * /etc/apt/

echo
echo Installing packages...
apt-get update
apt-get -qq -y -o Dpkg::Options::="--force-confold" install php8.0 php8.0-xdebug php8.0-imagick php8.0-xml php8.0-fpm \
    php8.0-cli php8.0-gd php8.0-curl php8.0-mysqlnd php8.0-bcmath php8.0-imagick php8.0-mbstring pv curl git unzip zip \
    htop iotop nginx libmysqlclient18 libmariadb3 mariadb-server imagemagick

echo
echo Setting up packages...
rm -f /etc/php/8.0/cli/conf.d/20-xdebug.ini
rm -rf /etc/nginx/{sites,mods}-enabled
rm -rf /etc/nginx/{sites,mods}-available
rm -rf /etc/nginx/conf.d
cd /vagrantroot/configs
cp -av * /
rm -rf /etc/mysql/mariadb.conf.d && ln -s /etc/mysql/conf.d /etc/mysql/mariadb.conf.d
chown -R root:root /etc/mysql/conf.d /etc/cron.d
find /etc/mysql -name "*.cnf" -type f -exec chmod 644 '{}' \;
find /etc/cron.d/ -type f -exec chmod 644 '{}' \;
echo never >/sys/kernel/mm/transparent_hugepage/defrag
echo never >/sys/kernel/mm/transparent_hugepage/enabled
update-grub

echo
echo Installing composer as /usr/local/bin/composer...
cd /tmp
curl -s https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

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
echo Resetting MariaDB storage...
rm -rf /var/lib/mysql
mysql_install_db

echo
echo Starting MariaDB...
systemctl start mariadb

echo
echo Configuring MySQL...
echo "DELETE FROM mysql.user WHERE user =''" | mysql -uroot
echo "CREATE DATABASE mei" | mysql -uroot
echo "GRANT ALL ON mei.* TO mei@localhost IDENTIFIED BY 'mei'" | mysql -uroot
echo "GRANT ALL ON mei.* TO 'mei'@'10.0.%.%' IDENTIFIED BY 'mei'" | mysql -uroot

echo
echo Importing database...
find /vagrantroot/fixtures -type f -name '*.sql' -exec bash -c 'cat '{}' | mysql -umei -pmei mei' \;

echo
echo Restarting MariaDB...
systemctl restart mariadb

echo
echo Installing composer packages from lock file...
cd /code
su vagrant -s /bin/bash -c 'composer install'

echo
echo Creating required directories...
su vagrant -s /bin/bash -c 'mkdir -p /code/images'
su vagrant -s /bin/bash -c 'mkdir -p /code/logs'

echo
echo Starting daemons...
systemctl start nginx
systemctl start php8.0-fpm
systemctl start cron
