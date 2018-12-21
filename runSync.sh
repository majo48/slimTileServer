#!/bin/bash

# sync remote folder with local folder contents
rsync -aP ~/projects/slimTileServer/ mart@osmap.ch:~/app
