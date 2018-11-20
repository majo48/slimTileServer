#!/bin/bash

# root folder
cd ~/projects/slimTileServer

# start php webserver
php -S localhost:8080 -t public public/index.php
