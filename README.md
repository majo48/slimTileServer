# SlimTileServer

Welcome to the slimTileServer GitHub repository! This is an open source project, starting November 2018:

**Scope**:

(Reverse) Geocoding app with:
  * Open Address, swiss data (ch.bfs.gebaeude_wohnungs_register)
  * and some more

Client software with:
* Leaflet
* Javascript/ jQuery library

Server software with:
* Slim Framework
* LAMP: Ubuntu, Apache2, MySQL, PHP 
* PostgreSQL
* ~~Tile server: mapnik, mapserver~~

Deployed with Ansible.

**Out of Scope**:

Discontinued the creation of a tileserver; the available code is not very flexible (e.g. changing map style) and/or unprofessional (DIY). Also, most of the installation guides are legacy/out-of-date. IMHO really not worth all the trouble.

* Tile servers:
  * mapnik: not flexible enough, ~~outdated, DIY stuff~~ (haven't tried https://www.linuxbabe.com/ubuntu/openstreetmap-tile-server-ubuntu-18-04-osm yet)
  * mapserver: ambiguous and outdated docs, also DIY
* use Google Maps, Bing, Maptiler instead

**Copyright**:

Copyright (C) 2019  Martin Jonasse, see LICENCE.md.
