PropelDataCacheBehavior
==========================

The Propel Data Cache Behavior provide data cacaching.

- support caching system APC and memcached
- auto caching and auto flush.


### What's the difference [Query Cache Behavior](http://propelorm.org/behaviors/query-cache.html)

Query Cache Behavior is caching transformation of a Query object (caching SQL code).<br />
This Behavior is caching the results of database. (result cache)


Requirements
------------

- Propel >= 1.6.0
- [DominoDataCache](https://github.com/SNakano/DataCache)


Install
-------

using Composer(recommended):
```javascript
{
    "require": {
        "snakano/propel-data-cache-behavior": "1.*"
    }
}
```

Then, if you don't use Composer, or an autoloader in your application, add the
following configuration to your `build.properties` or `propel.ini` file:

```ini
propel.behavior.data_cache.class = vendor.propel-datacache-behavior.src.DataCacheBehavior
```

Usage
-----

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
- call save() method.
- call delete() method.
- call BookPeer::doDeleteAll() method.
- call BookPeer::purgeCache() method.


License
-------

MIT License
