#!/bin/bash

#set folder
cd ~/projects/slimTileServer/ansible/

#run playbook
ansible-playbook -i development.tileserver install_playbook.yml