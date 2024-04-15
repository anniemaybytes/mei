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
echo Adding repositories...
cd /vagrantroot/configs/etc/apt
cp -avu * /etc/apt/

echo
echo Installing packages...
apt-get update
find /etc/apt/sources.list.d -name "*.list" -type f -exec \
    apt-get -qq -y \
    -o Dpkg::Options::="--force-confnew" \
    -o Dir::Etc::sourcelist="{}" \
    -o Dir::Etc::sourceparts="-" \
    -o APT::Get::List-Cleanup="0" \
    dist-upgrade \; # https://github.com/oerdnj/deb.sury.org/issues/1682
apt-get -qq -y -o Dpkg::Options::="--force-confnew" install php8.3 php8.3-xdebug php8.3-imagick php8.3-xml php8.3-fpm \
    php8.3-cli php8.3-gd php8.3-curl php8.3-mysql php8.3-bcmath php8.3-imagick php8.3-mbstring pv curl git unzip zip \
    htop iotop nginx libmysqlclient18 libmariadb3 mariadb-server imagemagick

echo
echo Setting up packages...
rm -f /etc/php/8.3/cli/conf.d/20-xdebug.ini
rm -rf /etc/nginx/{sites,mods}-enabled
rm -rf /etc/nginx/{sites,mods}-available
rm -rf /etc/nginx/conf.d
cd /vagrantroot/configs
cp -av * /
chown -R root:root /etc/mysql/conf.d /etc/cron.d
find /etc/mysql -name "*.cnf" -type f -exec chmod 644 '{}' \;
find /etc/cron.d/ -type f -exec chmod 644 '{}' \;
echo never >/sys/kernel/mm/transparent_hugepage/defrag
echo never >/sys/kernel/mm/transparent_hugepage/enabled

echo
echo Reconfiguring microarchitecture mitigations...
cat << EOF > /etc/default/grub
GRUB_DEFAULT=0
GRUB_TIMEOUT=5
GRUB_DISTRIBUTOR=`lsb_release -i -s 2> /dev/null || echo Debian`
GRUB_CMDLINE_LINUX_DEFAULT="net.ifnames=0 biosdevname=0 mitigations=off"
GRUB_CMDLINE_LINUX="consoleblank=0"
EOF
update-grub

echo
echo Configuring virtual hosts...
mkdir -p /etc/hosts.d/ && mv /etc/hosts /etc/hosts.d/10-native
echo "$(ip route show default | awk '/default/ {print $3}') animebytes.local " > /etc/hosts.d/99-tentacles
echo "$(ip route show default | awk '/default/ {print $3}') status.animebytes.local " > /etc/hosts.d/99-status
echo "$(ip route show default | awk '/default/ {print $3}') irc.animebytes.local " > /etc/hosts.d/99-irc
echo "$(ip route show default | awk '/default/ {print $3}') tracker.animebytes.local " > /etc/hosts.d/99-tracker
cat /etc/hosts.d/* > /etc/hosts

echo
echo Installing composer as /usr/local/bin/composer...
cd /tmp
curl -s https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

echo
echo Configuring daemons...
systemctl daemon-reload
systemctl disable nginx
systemctl disable php8.3-fpm
systemctl disable mariadb

echo
echo Stopping daemons...
systemctl stop nginx
systemctl stop php8.3-fpm
systemctl stop mariadb
systemctl stop cron

echo
echo Resetting MariaDB storage...
rm -rf /var/lib/mysql
mariadb-install-db

echo
echo Starting MariaDB...
systemctl start mariadb

echo
echo Configuring MariaDB...
echo "DELETE FROM mysql.user WHERE user =''" | mariadb -uroot
echo "CREATE DATABASE mei" | mariadb -uroot
echo "GRANT ALL ON mei.* TO 'vagrant'@localhost IDENTIFIED BY 'vagrant'" | mariadb -uroot
echo "GRANT ALL ON mei.* TO 'vagrant'@'%' IDENTIFIED BY 'vagrant'" | mariadb -uroot

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
echo Seeding database...
cd /code
su vagrant -s /bin/bash -c 'composer phinx seed:run'

echo
echo Migrating...
cd /code
su vagrant -s /bin/bash -c 'composer phinx migrate'

echo
echo Starting daemons...
systemctl start nginx
systemctl start php8.3-fpm
systemctl start cron
