# -*- mode: ruby -*-
# vi: set ft=ruby :

ENV["LC_ALL"] = "en_US.UTF-8"

Vagrant.require_version ">= 2.2.12"

Vagrant.configure(2) do |config|
  # box
  config.vm.box = "generic/debian11"
  config.vm.box_version = ">= 3.5.0"

  # network
  config.vm.network "forwarded_port", id: "ssh", guest: 22, host_ip: "127.0.0.1", host: 7022
  config.vm.network "forwarded_port", id: "nginx", guest: 443, host_ip: "127.0.0.1", host: 7443
  config.vm.network "forwarded_port", id: "mariadb", guest: 3306, host_ip: "127.0.0.1", host: 7306

  # synced folders
  config.vm.synced_folder "./", "/code",
    group: "www-data",
    mount_options: ["umask=002"]
  config.vm.synced_folder "./vagrant", "/vagrantroot"

  # virtualbox specific overrides
  config.vm.provider "virtualbox" do |v|
    v.memory = 1024
    v.cpus = 2
    v.linked_clone = true
  end

  # vmware specific overrides
  config.vm.provider "vmware_desktop" do |v|
    v.memory = 1024
    v.cpus = 2
    v.vmx["cpuid.corespersocket"] = 2
    v.vmx["mainMem.useNamedFile"] = false
    v.vmx["ulm.disableMitigations"] = true
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
