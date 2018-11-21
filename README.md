sysPassClient
=============

This is a command line client for searching and retrieving passwords from the [sysPass](https://www.syspass.org)
password manager. It uses syspass api to search for accounts matching your searchterm, asks you to choose from several
matches and gives you the the username and password for the account stored in your syspass.

If you have `pbcopy` (on macOS) or `xclip` (on Linux) installed the client automatically copies the password onto your
clipboard instead of showing it on screen. Additionally, if the account is tagged with `ssh`, the client automatically
prompts you to login in to the server so you only need to paste the password.

Usage
-----

Search for credentials:

`syspass searchterm`

Requirements
------------

 - A [sysPass](https://www.syspass.org) instance running somewhere
 - [API access permissions](https://doc.syspass.org/en/application/permissions.html#api) set up for your sysPass account
 - `pbcopy` (on macOS) or `xclip` (on Linux) (*optional*)


Installation
------------
 - `git clone https://github.com/IntegralOy/syspassclient.git`
 - `cd syspassclient`
 - `composer install`
 - `mkdir ~/.syspass`
 - `cp config_example.json ~/.syspass/config.json`
 - `nano ~/.syspass/config.json` and type your syspass host, token and password there
 - `nano ~/.bash_profile`
   - add line `alias syspass='/path/to/syspassclient_install_dir/bin/syspass'`
 - Start a new terminal and type `syspass searchterm`
