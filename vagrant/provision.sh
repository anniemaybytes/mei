#!/bin/bash
set -e

if [ "$EUID" -ne 0 ]; then
    echo "Must be run as root"
    exit
fi

export DEBIAN_FRONTEND=noninteractive

echo Configuring system...
DPKG_MAINTSCRIPT_NAME=postinst DPKG_MAINTSCRIPT_PACKAGE=grub-pc upgrade-from-grub-legacy # bug: system assumes /dev/vda but that is not necessarily valid anymore
apt-mark hold linux-image-amd64 # bug: vboxsf component are not updated

echo
echo Installing required base components...
apt-get update
apt-get -y install apt-transport-https dirmngr curl

echo
echo Adding repositories...
cd /vagrantroot/configs/etc/apt
cp -avu * /etc/apt/
apt-key adv --no-tty --recv-keys --keyserver hkp://keyserver.ubuntu.com:80 0xF1656F24C74CD1D8
curl -sSL https://packages.sury.org/php/apt.gpg | apt-key add -

echo
echo Updating apt cache...
apt-get update

echo
echo Updating currently installed packages...
apt-get -y -o Dpkg::Options::="--force-confnew" upgrade

echo
echo Installing packages...
apt-get -y -o Dpkg::Options::="--force-confold" install php-xdebug php7.4 php7.4-xml php7.4-fpm php7.4-cli php7.4-gd \
    php7.4-curl php7.4-mysqlnd php7.4-bcmath php7.4-imagick php7.4-geoip php7.4-mbstring php7.4-json pv git unzip zip \
    htop iotop nginx libmysqlclient18 libmariadb3 mariadb-server

echo
echo Setting up packages...
rm -f /etc/php/7.4/cli/conf.d/20-xdebug.ini
rm -f /etc/nginx/sites-enabled/default
rm -rf /etc/nginx/conf.d
cd /vagrantroot/configs
cp -av * /
chmod 755 /etc/mysql/mariadb.cnf
echo never >/sys/kernel/mm/transparent_hugepage/defrag
echo never >/sys/kernel/mm/transparent_hugepage/enabled

echo
echo Installing composer as /usr/local/bin/composer...
cd /tmp
curl -s https://getcomposer.org/installer | php
mv ./composer.phar /usr/local/bin/composer

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
echo Resetting MariaDB storage...
rm -f /var/lib/mysql/ibdata1 /var/lib/mysql/ib_buffer_file /var/lib/mysql/ib_logfile0
rm -rf '/var/lib/mysql/#rocksdb'

echo
echo Starting MariaDB...
systemctl start mariadb

echo
echo Configuring MySQL...
echo "CREATE DATABASE mei; GRANT ALL ON mei.* TO mei@localhost IDENTIFIED BY 'mei';GRANT ALL ON mei.* TO mei@127.0.0.1 IDENTIFIED BY 'mei';GRANT ALL ON mei.* TO 'mei'@'10.0.%.%' IDENTIFIED BY 'mei';" | mysql -uroot
mysqladmin -uroot password w3llkn0wn
echo "DELETE FROM user WHERE (User = 'root' AND Host != 'localhost') OR (User = '');FLUSH PRIVILEGES;\q" | mysql -uroot -pw3llkn0wn mysql

echo
echo Importing database...
mysql -umei -pmei mei </vagrantroot/mei.sql

echo
echo Restarting MariaDB...
systemctl restart mariadb

echo
echo Installing composer packages from lock file...
cd /code
su -s /bin/bash vagrant -c 'composer install'

echo
echo Creating required directories...
su -s /bin/bash vagrant -c 'mkdir -p /code/images'
su -s /bin/bash vagrant -c 'mkdir -p /code/logs'

echo
echo Starting daemons...
systemctl start nginx
systemctl start php7.4-fpm
systemctl start cron
