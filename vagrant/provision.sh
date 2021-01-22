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
apt-get -y -o Dpkg::Options::="--force-confold" install php8.0 php8.0-xdebug php8.0-imagick php8.0-xml php8.0-fpm \
    php8.0-cli php8.0-gd php8.0-curl php8.0-mysqlnd php8.0-bcmath php8.0-imagick php8.0-mbstring pv git unzip zip \
    htop iotop nginx libmysqlclient18 libmariadb3 mariadb-server imagemagick

echo
echo Setting up packages...
rm -f /etc/php/8.0/cli/conf.d/20-xdebug.ini
rm -rf /etc/nginx/{sites,mods}-enabled
rm -rf /etc/nginx/{sites,mods}-available
rm -rf /etc/nginx/conf.d
cd /vagrantroot/configs
cp -av * /
chmod 755 /etc/mysql/mariadb.cnf

echo
echo Installing composer as /usr/local/bin/composer...
cd /tmp
curl -s https://getcomposer.org/installer | php
mv ./composer.phar /usr/local/bin/composer

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
echo Starting MariaDB...
systemctl start mariadb

echo
echo Configuring MySQL...
echo "CREATE DATABASE mei; GRANT ALL ON mei.* TO mei@localhost IDENTIFIED BY 'mei';GRANT ALL ON mei.* TO mei@127.0.0.1 IDENTIFIED BY 'mei';GRANT ALL ON mei.* TO 'mei'@'10.0.%.%' IDENTIFIED BY 'mei';" | mysql -uroot
mysqladmin -uroot password w3llkn0wn
echo "DELETE FROM user WHERE (User = 'root' AND Host != 'localhost') OR (User = '');FLUSH PRIVILEGES;\q" | mysql -uroot -pw3llkn0wn mysql

echo
echo Importing database...
find /vagrantroot/fixtures -type f -name '*.sql' -exec bash -c 'cat '{}' | mysql -umei -pmei mei' \;

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
systemctl start php8.0-fpm
systemctl start cron
