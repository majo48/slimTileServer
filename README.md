# SlimTileServer

Welcome to the slimTileServer GitHub repository! 

This is an open source project, starting November 2018:

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
* Tile servers:
  * mapnik: not flexible enough, outdated, DIY stuff
  * mapserver: ambiguous and outdated docs, also DIY
  * IMHO really not worth all the trouble
* use Google Maps, Bing, Maptiler instead