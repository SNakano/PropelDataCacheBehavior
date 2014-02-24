PropelDataCacheBehavior
==========================
[![Build Status](https://travis-ci.org/SNakano/PropelDataCacheBehavior.png)](https://travis-ci.org/SNakano/PropelDataCacheBehavior)
[![Latest Stable Version](https://poser.pugx.org/snakano/propel-data-cache-behavior/v/stable.png)](https://packagist.org/packages/snakano/propel-data-cache-behavior)
[![Total Downloads](https://poser.pugx.org/snakano/propel-data-cache-behavior/downloads.png)](https://packagist.org/packages/snakano/propel-data-cache-behavior)

A Propel ORM behavior that provide auto data caching to your model.

- support caching system APC, memcached and Redis (via [DominoCacheStore](https://github.com/SNakano/CacheStore))
- auto caching and auto flush.

#### What's the difference Query Cache Behavior

[Query Cache Behavior](http://propelorm.org/behaviors/query-cache.html) is caching transformation of a Query object (caching SQL code).<br />
This Behavior is caching the results of database. (result data cache)


Requirements
------------

- Propel >= 1.6.0
- [DominoCacheStore](https://github.com/SNakano/CacheStore)


Install
-------

### Composer

Add a dependency on `snakano/propel-data-cache-behavior` to your project's `composer.json` file.

```javascript
{
    "require": {
        "snakano/propel-data-cache-behavior": "1.*"
    }
}
```

Then, add the following configuration to your `build.properties` or `propel.ini` file:

```ini
propel.behavior.data_cache.class = vendor.propel-datacache-behavior.src.DataCacheBehavior
```

Configuration
-------------

### schema.xml

```xml
<table name="book">
  <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
  <column name="title" type="VARCHAR" required="true" primaryString="true" />
  <behavior name="data_cache">
    <parameter name="backend" value="apc" />     <!-- cache system. "apc" or "memcache", default "apc". (optional) -->
    <parameter name="lifetime" value="3600" />   <!-- cache expire time (second). default 3600 (optional) -->
    <parameter name="auto_cache" value="true" /> <!-- auto cache enable. default true (optional) -->
  </behavior>
</table>
```

### if use memcached.

Add the following configuration code to your project bootstraping file.

```php
// configure memcached setting.
Domino\CacheStore\Factory::setOption(
    array(
        'storage'     => 'memcached',
        'prefix'      => 'domino_test',
        'default_ttl' => 360,
        'servers'     => array(
            array('server1', 11211, 20),
            array('server2', 11211, 80)
        )
    )
);

```

### Basic usage

```php
$title = 'War And Peace';
BookQuery::create()
    ->filterByTitle($title)
    ->findOne(); // from Database

BookQuery::create()
    ->filterByTitle($title)
    ->findOne(); // from caching system
```

### Disable cache

```php
$title = 'Anna Karenina';
BookQuery::create()
    ->setCacheDisable()  // disable cache
    ->filterByTitle($title)
    ->findOne();
```

- setCacheEnable()
- setCacheDisable()
- isCacheEnable()
- setLifetime($ttl)


### When cache delete?

```php
$book = new Book;
$book->setId(1);
$book->setTitle("War And Peace");
$book->save();  // purge cache.
```

- expire cache lifetime.
- call `save()` method.
- call `delete()` method.
- call `BookPeer::doDeleteAll()` method.
- call `BookPeer::purgeCache()` method.

### Manually delete cache.

```php
$title = 'War And Peace';
$query = BookQuery::create();
$book  = $query->filterByTitle($title)->findOne();
$cacheKey = $query->getCacheKey(); // get cache key.

BookPeer::cacheDelete($cacheKey);  // delete cache by key.
```

License
-------

MIT License
