#!/bin/bash
set -e

if [ "$EUID" -ne 0 ]; then
    echo "Must be run as root"
    exit
fi

export DEBIAN_FRONTEND=noninteractive

echo
echo Installing required base components
apt-get update
apt-get -y install apt-transport-https dirmngr curl htop iotop

echo
echo Adding repositories
cd /vagrantroot/configs/etc/apt
cp -avu * /etc/apt/
apt-key adv --no-tty --recv-keys --keyserver hkp://keyserver.ubuntu.com:80 0xF1656F24C74CD1D8
curl -sSL https://packages.sury.org/php/apt.gpg | apt-key add -

echo
echo Updating apt cache
apt-get update

echo
echo Updating currently installed packages
apt-get -y -o Dpkg::Options::="--force-confnew" dist-upgrade

echo
echo Installing packages...
apt-get -y -o Dpkg::Options::="--force-confold" install software-properties-common nginx php7.4 php7.4-xml php7.4-curl \
    php7.4-fpm php7.4-bcmath php7.4-mysqlnd mariadb-server php7.4-common php7.4-gd php7.4-geoip php7.4-imagick phpunit \
    pv php7.4-dev php-pear libcurl3-openssl-dev build-essential php7.4-cli git libpq5 libodbc1 unzip zip \
    libmysqlclient18 libmariadb3 php7.4-mbstring php7.4-json

pecl install xdebug-2.9.6

echo
echo Setting up packages...
rm -f /etc/nginx/sites-enabled/default
rm -rf /etc/nginx/conf.d
cd /vagrantroot/configs
cp -av * /
chmod 755 /etc/mysql/my.cnf
chown -R root:root /etc/mysql/{,mariadb.}conf.d
chmod -R 644 /etc/mysql/{,mariadb.}conf.d/
chmod +x /etc/mysql/{,mariadb.}conf.d/
echo never >/sys/kernel/mm/transparent_hugepage/defrag
echo never >/sys/kernel/mm/transparent_hugepage/enabled

echo
echo Installing composer as /usr/local/bin/composer...
cd /tmp
curl -s https://getcomposer.org/installer | php
mv ./composer.phar /usr/local/bin/composer
cd /code
composer install

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
echo Starting daemons...
systemctl daemon-reload
systemctl start nginx
systemctl start php7.4-fpm
systemctl start mariadb
systemctl start cron

echo
echo Configuring MySQL...
echo "CREATE DATABASE mei; GRANT ALL ON mei.* TO mei@localhost IDENTIFIED BY 'mei';GRANT ALL ON mei.* TO mei@127.0.0.1 IDENTIFIED BY 'mei';GRANT ALL ON mei.* TO 'mei'@'10.0.%.%' IDENTIFIED BY 'mei';" | mysql -uroot
mysqladmin -uroot password w3llkn0wn
echo "DELETE FROM user WHERE (User = 'root' AND Host != 'localhost') OR (User = '');FLUSH PRIVILEGES;\q" | mysql -uroot -pw3llkn0wn mysql
mysql -umei -pmei mei </vagrantroot/mei.sql

echo
echo Creating required directories
su -s /bin/bash vagrant -c 'mkdir -p /code/images'
su -s /bin/bash vagrant -c 'mkdir -p /code/logs'
