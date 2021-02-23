# -*- mode: ruby -*-
# vi: set ft=ruby :

ENV["LC_ALL"] = "en_US.UTF-8"

Vagrant.configure(2) do |config|
  # box
  config.vm.box = "debian/contrib-buster64"
  config.vm.box_version = ">= 10.7.0"
  
  # custom
  config.vm.graceful_halt_timeout = 30

  # network
  config.vm.network "forwarded_port", guest: 443, host_ip: "127.0.0.1", host: 7443 # nginx
  config.vm.network "forwarded_port", guest: 3306, host_ip: "127.0.0.1", host: 7306 # mariadb

  # synced folders
  config.vm.synced_folder "./", "/code",
    owner: "vagrant",
    group: "www-data",
    mount_options: ["dmode=775,fmode=775"]
  config.vm.synced_folder "./vagrant", "/vagrantroot"
  config.vm.synced_folder ".", "/vagrant", type: "rsync", disabled: true
  
  # virtualbox-specific overrides
  config.vm.provider :virtualbox do |v, override|
    v.check_guest_additions = true
    v.functional_vboxsf     = true
    v.memory = 1024
    v.cpus = 2
    v.customize ["modifyvm", :id, "--vram", "16"]
    v.customize ["modifyvm", :id, "--paravirtprovider", "default"]
  end
  
  # provision scripts
  config.vm.provision "shell", path: "./vagrant/provision.sh"
  config.vm.provision "shell", run: "always", path: "./vagrant/update.sh"
  
end
