# OpenVPN GUI #
Web interface for OpenVPN server.

**WARNING: UNSTABLE PROJECT!**

## Features ##
- payments per month
- creating accounts
- disabling and enabling accounts
- downloading certificates with configuration in zip archive
- sending configuration in e-mail with install instructions

## Requirements ##
- OpenVPN server with RSA certificates on Linux machine
- PHP 5.3
	- ssh2 extension
	- pdo_sqlite extension
- Nette Framework

## Installation ##
1. [Download source](https://github.com/kolinger/openvpn-gui/archive/master.zip) from github

2. Install dependence via [composer](http://getcomposer.org/): `composer install`

3. create and configure `app/config.local.neon`

4. add certificates for SSH to `app/certificates`

5. modify e-mail template (`app/templates/email.latte`) and OpenVPN client configuration template (`app/templates/openvpn.latte`)

5. done :)

## TODO ##
- account presenter total refactoring
	- split into small presenters like payment presenter
	- account state must be stored in cache (or in database) instead of calculated every request
	- ...
- traffic monitoring
- better error hanling: on non existing account, when SSH crashes, ...
- english translations