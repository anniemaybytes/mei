#!/bin/bash
set -e

export DEBIAN_FRONTEND=noninteractive

echo
echo Installing required base components
apt-get update
apt-get -y --force-yes install apt-transport-https dirmngr

echo
echo Adding repositories
cd /vagrantroot/configs/etc/apt
sudo cp -avu * /etc/apt/
sudo apt-key add /etc/apt/nginx_signing.key
sudo apt-key add /etc/apt/dotdeb.key
sudo apt-key adv --no-tty --recv-keys --keyserver hkp://keyserver.ubuntu.com:80 0xF1656F24C74CD1D8
sudo apt-key adv --no-tty --recv-keys --keyserver hkp://keyserver.ubuntu.com:80 0x1655A0AB68576280

echo
echo Updating apt cache
apt-get update

echo
echo Installing packages...
apt-get -y --force-yes install software-properties-common nginx php7.0 php7.0-curl php7.0-fpm php7.0-mcrypt php7.0-mysqlnd mariadb-server php7.0-common php7.0-gd php7.0-geoip php7.0-imagick phpunit pv php7.0-xdebug curl php7.0-cli git libpq5 libodbc1 unzip zip libmysqlclient18 libmariadb3 php7.0-mbstring

echo
echo Setting up packages...
# disable default nginx site
rm -f /etc/nginx/sites-enabled/default
rm -rf /etc/nginx/conf.d
cd /vagrantroot/configs
cp -av * /
chmod 755 /etc/mysql/my.cnf 
sudo chown -R root:root /etc/mysql/{,mariadb.}conf.d
sudo chmod -R 644 /etc/mysql/{,mariadb.}conf.d/
sudo chmod +x /etc/mysql/{,mariadb.}conf.d/
echo never > /sys/kernel/mm/transparent_hugepage/defrag
echo never > /sys/kernel/mm/transparent_hugepage/enabled
# remove xdebug from php-cli (composer performance)
rm -f /etc/php/7.0/cli/conf.d/20-xdebug.ini

echo
echo Installing composer as /usr/local/bin/composer...
cd /tmp
curl -s https://getcomposer.org/installer | php
mv ./composer.phar /usr/local/bin/composer
cd /code
composer install
ln -s /code/vendor/robmorgan/phinx/bin/phinx /usr/local/bin/phinx

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

echo
echo Configuring MySQL...
echo "CREATE DATABASE mei; GRANT ALL ON mei.* TO mei@localhost IDENTIFIED BY 'mei';GRANT ALL ON mei.* TO mei@127.0.0.1 IDENTIFIED BY 'mei';GRANT ALL ON mei.* TO 'mei'@'10.0.%.%' IDENTIFIED BY 'mei';" | mysql -uroot
mysqladmin -uroot password w3llkn0wn
echo "DELETE FROM user WHERE (User = 'root' AND Host != 'localhost') OR (User = '');FLUSH PRIVILEGES;\q" | mysql -uroot -pw3llkn0wn mysql
