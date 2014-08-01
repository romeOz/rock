Rock PHP Framework
=================

**Framework is not ready for production use yet.**

[![Latest Stable Version](https://poser.pugx.org/romeo7/rock/v/stable.svg)](https://packagist.org/packages/romeo7/rock)
[![Total Downloads](https://poser.pugx.org/romeo7/rock/downloads.svg)](https://packagist.org/packages/romeo7/rock)
[![Build Status](https://travis-ci.org/romeo7/rock.svg?branch=master)](https://travis-ci.org/romeo7/rock)
[![Coverage Status](https://coveralls.io/repos/romeo7/rock/badge.png?branch=master)](https://coveralls.io/r/romeo7/rock?branch=master)
[![License](https://poser.pugx.org/romeo7/rock/license.svg)](https://packagist.org/packages/romeo7/rock)

[Rock Framework on Packagist](https://packagist.org/packages/romeo7/rock)

Features
-------------------

 * MVC
 * DI
 * Route
 * Template engine (in a separate project [Rock Template](https://github.com/romeo7/rock-template))
    * Snippets (ListView, Pagination,...)
    * HTML Builder (fork from [Yii2](https://github.com/yiisoft/yii2))
    * Widgets (fork from [Yii2](https://github.com/yiisoft/yii2))
 * ORM/DBAL (fork from [Yii2](https://github.com/yiisoft/yii2))
 * DataProviders (DB, Thumb)
 * Events (Pub/Sub)
 * Many different helpers (String, Numeric, ArrayHelper, File, Pagination...)
 * Url Builder
 * DateTime Builder
 * FileManager (abstraction over the [thephpleague/flysystem](https://github.com/thephpleague/flysystem))
 * Sanitize
 * Request
 * Response + Formatters (HtmlResponseFormatter, JsonResponseFormatter, XmlResponseFormatter, RssResponseFormatter, SitemapResponseFormatter)
 * Session
 * Cookie
 * i18n
 * Validation (fork from [Respect/Validation](https://github.com/Respect/Validation))
 * Cache (in a separate project [Rock Cache](https://github.com/romeo7/rock-cache))
 * Behaviors + Filters (AccessFilter, ContentNegotiatorFilter, EventFilter, RateLimiter, SanitizeFilter, ValidationFilters, VerbFilter, TimestampBehavior)
 * Mail
 * Security + Token
 * Markdown (abstraction over the [cebe/markdown](https://github.com/cebe/markdown))
 * RBAC (local or DB)
 * Exception + Logger + Tracing
 * Extensions
    * Sphinx (search engine)
    * phpMorphy (morphological analyzer library for search and other)


Installation
-------------------

From the Command Line:

`composer create-project romeo7/rock --prefer-dist`

Then, to create the structure of the application you must to run `/path/to/framework/rock.sh`.
if you want to create tables `Users` and `Access`, then run with parameter `/path/to/framework/rock.sh -u <username> -p <password>`.

###Configure server

For a single entry point.

####Apache

Security via "white list":

```
RewriteCond %{REQUEST_URI} ^\/(?!index\.php|robots\.txt|500\.html|favicon\.ico||assets\b\/.+\.(?:js|ts|css|ico|xml|swf|flv|pdf|xls|htc|gif|jpg|png|jpeg)$).*$ [NC]
RewriteRule ^.*$ index.php [L]
```

####Nginx

Security via "white list":

```
location ~ ^\/(?!index\.php|robots\.txt|favicon\.ico|500\.html|assets\b\/.+\.(?:js|ts|css|ico|xml|swf|flv|pdf|xls|htc|gif|jpg|png|jpeg)$).*$
{
    rewrite ^.*$ /index.php;
}
```

Demo & Tests
-------------------

Use a specially prepared environment (Vagrant + Ansible) with preinstalled and configured storages.

###Out of the box:

 * Ubuntu 14.04 32 bit
 * Nginx 1.6
 * PHP-FPM 5.5
 * Composer
 * MySQL 5.5
 * For caching
    * Couhbase 2.2.0 ( + pecl couchbase-1.2.2)
    * Redis 2.8 ( + php5-redis)
    * Memcached 1.4.14 ( + php5_memcached, php5_memcache)
 * Local IP loop on Host machine /etc/hosts and Virtual hosts in Nginx already set up!

###Installation:

1. [Install Composer](https://getcomposer.org/doc/00-intro.md#globally)
2. `composer create-project --prefer-dist romeo7/rock`
3. [Install Vagrant](https://www.vagrantup.com/downloads), and additional Vagrant plugins `vagrant plugin install vagrant-hostsupdater vagrant-vbguest vagrant-cachier`
4. `vagrant up`
5. Open demo [http://rock/](http://rock/) or [http://192.168.33.37/](http://192.168.33.37/)

> Work/editing the project can be done via ssh:
```bash
vagrant ssh
cd /var/www/
```

Requirements
-------------------
 * **PHP 5.4+**
 * **MySQL 5.5+**

License
-------------------

The Rock PHP Framework is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).