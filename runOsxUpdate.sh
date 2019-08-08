#!/bin/bash

#set folder
cd ~/projects/slimTileServer/ansible/

#run playbook
ansible-playbook -i development.tileserver update_playbook.yml
