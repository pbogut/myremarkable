#!/bin/bash
#=================================================
# name:   install.sh
# author: Pawel Bogut <https://pbogut.me>
# date:   25/11/2020
#=================================================
dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
[[ -f $dir/.env ]] && source $dir/.env

cd $dir
ssh_ip=${1:-$REMARKABLE_IP}

usage() {
  echo "$0 <remarkable_ip_address>"
  exit 1
}

start_robo() {
  php robo rm:convert-templates
  php robo rm:copy-templates $ssh_ip
  php robo rm:copy-files $ssh_ip
  php robo rm:reboot $ssh_ip
}


## INFO:  Use '/home/root/entware-reenable' to re-enable Toltec after a system update


if [[ -z $ssh_ip ]]; then
  usage
fi

start_robo
