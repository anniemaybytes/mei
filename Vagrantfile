# -*- mode: ruby -*-
# vi: set ft=ruby :

ENV["LC_ALL"] = "en_US.UTF-8"

Vagrant.configure(2) do |config|
  # box
  config.vm.box = "debian/bullseye64"
  config.vm.box_version = ">= 11.20210829.1"
  
  # custom
  config.vm.graceful_halt_timeout = 30

  # network
  config.vm.network "forwarded_port", id: "ssh", guest: 22, host_ip: "127.0.0.1", host: 7022
  config.vm.network "forwarded_port", id: "nginx", guest: 443, host_ip: "127.0.0.1", host: 7443
  config.vm.network "forwarded_port", id: "mariadb", guest: 3306, host_ip: "127.0.0.1", host: 7306

  # synced folders
  config.vm.synced_folder "./", "/code",
    owner: "vagrant",
    group: "www-data",
    mount_options: ["dmode=775,fmode=775"]
  config.vm.synced_folder "./vagrant", "/vagrantroot"
  config.vm.synced_folder ".", "/vagrant", type: "rsync", disabled: true
  
  # virtualbox specific overrides
  config.vm.provider "virtualbox" do |v|
    v.memory = 1024
    v.cpus = 2

    v.check_guest_additions = false # guest additions are mainlined now, version is meaningless
    v.functional_vboxsf     = true
  end

  # libvirt specific overrides
  config.vm.provider "libvirt" do |v|
    v.memory = 1024
    v.cpus = 2
  end
  
  # provision scripts
  config.vm.provision "shell", path: "./vagrant/provision.sh"
  config.vm.provision "shell", run: "always", path: "./vagrant/update.sh"
  
end
