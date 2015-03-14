Rock PHP Framework
=================

**Framework is not ready for production use yet.**

[![Latest Stable Version](https://poser.pugx.org/romeOz/rock/v/stable.svg)](https://packagist.org/packages/romeOz/rock)
[![Total Downloads](https://poser.pugx.org/romeOz/rock/downloads.svg)](https://packagist.org/packages/romeOz/rock)
[![Build Status](https://travis-ci.org/romeOz/rock.svg?branch=master)](https://travis-ci.org/romeOz/rock)
[![Coverage Status](https://coveralls.io/repos/romeOz/rock/badge.svg?branch=master)](https://coveralls.io/r/romeOz/rock?branch=master)
[![License](https://poser.pugx.org/romeOz/rock/license.svg)](https://packagist.org/packages/romeOz/rock)

[Rock Framework on Packagist](https://packagist.org/packages/romeOz/rock)

Features
-------------------

 * Modular design
 * MVC
 * [Dependency Injection](https://github.com/romeOz/rock-di)
 * Route
 * [Template engine](https://github.com/romeOz/rock-template)
    * [Snippets (ListView, Pagination,...)](https://github.com/romeOz/rock-template)
    * [HTML Builder](https://github.com/romeOz/rock-template)
    * [Widgets](https://github.com/romeOz/rock-widgets)
 * [ORM/DBAL](https://github.com/romeOz/rock-db)
 * [Events (Pub/Sub)](https://github.com/romeOz/rock-events)
 * [Behaviors](https://github.com/romeOz/rock-behaviors) 
    * TimestampBehavior
    * SluggableBehavior
    * ...
 * Action filters 
    * AccessFilter, 
    * VerbFilter,
    * ContentNegotiatorFilter, 
    * RateLimiter
    * CORS
    * ...
 * Many different [helpers](https://github.com/romeOz/rock-helpers)
    * StringHelper
    * NumericHelper
    * ArrayHelper
    * ...
 * [Url Builder](https://github.com/romeOz/rock-url)
 * [DateTime Builder](https://github.com/romeOz/rock-date)
 * [Request](https://github.com/romeOz/rock-request)
 * Response + Formatters 
    * HtmlResponseFormatter
    * JsonResponseFormatter 
    * XmlResponseFormatter 
    * RssResponseFormatter 
    * SitemapResponseFormatter
 * [Session](https://github.com/romeOz/rock-session)
 * Cookie
 * [i18n](https://github.com/romeOz/rock-i18n)
 * Mail
 * [Sanitization](https://github.com/romeOz/rock-sanitize)
 * [Validation](https://github.com/romeOz/rock-validate)
 * [Security](https://github.com/romeOz/rock-security) + [CSRF](https://github.com/romeOz/rock-csrf)
 * RBAC (local or DB)
 * Exception + Logger + Tracing
 * [Cache](https://github.com/romeOz/rock-cache) **(option)**
    * File storage
    * Memcached
    * Redis
    * Couchbase
    * APCu
 * [Image](https://github.com/romeOz/rock-image) **(option)**
 * [File Manager + Upload File](https://github.com/romeOz/rock-file) **(option)**
 * [Markdown](https://github.com/romeOz/rock-markdown) **(option)**
 * [Sphinx API/ORM](https://github.com/romeOz/rock-sphinx) **(option)**
 * [Morphy API](https://github.com/romeOz/rock-morphy) **(option)**
 * OAuth/OAuth2 clients
 * [Message queue services](https://github.com/romeOz/rock-mq) **(option)**
    * ZeroMQ
    * RabbitMQ
    * Gearman

Installation
-------------------

From the Command Line:

```composer require romeoz/rock:*@dev```

In your composer.json:

```json
{
    "require": {
        "romeoz/rock": "*@dev"
    }
}
```

Demo & Tests (one of two ways)
-------------------

####1. Docker + Ansible

 * [Install Docker](https://docs.docker.com/installation/) or [askubuntu](http://askubuntu.com/a/473720)
 * `docker run -d -p 8080:80 romeoz/rock-app-basic:dev`
 * Open demo [http://localhost:8080/](http://localhost:8080/)
 
####2. VirtualBox + Vagrant + Ansible

 * `composer create-project --prefer-dist romeoz/rock-app-basic:*@dev`
 * [Install VirtualBox](https://www.virtualbox.org/wiki/Downloads)
 * [Install Vagrant](https://www.vagrantup.com/downloads), and additional Vagrant plugins `vagrant plugin install vagrant-hostsupdater vagrant-vbguest vagrant-cachier`
 * `vagrant up`
 * Open demo [http://rock-basic/](http:/rock-basic/) or [http://192.168.33.40/](http://192.168.33.40/)

> Work/editing the project can be done via ssh:

```bash
vagrant ssh
cd /var/www/rock-basic
```

Requirements
-------------------
 * **PHP 5.4+**
 * **MySQL 5.5+**

License
-------------------

The Rock PHP Framework is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).