<?php
/**
 * Propel Data Cache Behavior
 *
 * @copyright Copyright (c) 2013 Domino Co. Ltd.
 * @license MIT
 * @package propel.generator.behavior
 */

/**
 * Propel Data Cache Behavior Test
 *
 * @package propel.generator.behavior
 * @subpackage UnitTests
 */
class DataCacheBehaviorTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (!extension_loaded('memcached')) {
            $this->markTestSkipped("Memcached extension is not loaded");
        }

        Domino\CacheStore\Factory::setOption(
            array(
                'storage'     => 'memcached',
                'prefix'      => 'datacache_test',
                'default_ttl' => 360,
                'servers'     => array(
                    array('localhost', 11211, 20)
                )
            )
        );

        if (!class_exists("DataCacheBehaviorMemcachedTest")) {
            $schema = <<<EOF
<database name="data_cache_behavior_memcached_test" defaultIdMethod="native">
    <table name="data_cache_behavior_memcached_test">
        <column name="id" required="true" primaryKey="true" type="INTEGER" />
        <column name="name" type="VARCHAR" required="true" />
        <behavior name="data_cache">
            <parameter name="backend" value="memcached" />
        </behavior>
    </table>
</database>
EOF;
            $this->getBuilder($schema)->build();
        }

        DataCacheBehaviorMemcachedTestPeer::doDeleteAll();
        Propel::disableInstancePooling();
    }

    public function testFind()
    {
        $this->setData(100, "foo");

        $expected = DataCacheBehaviorMemcachedTestQuery::create()->findById(100);
        $this->deleteDirect("data_cache_behavior_memcached_test", 100);
        $actual = DataCacheBehaviorMemcachedTestQuery::create()->findById(100);

        $this->assertInstanceOf("PropelObjectCollection", $actual);
        $this->assertCount(1, $actual);
        $this->assertEquals($expected, $actual);
    }

    public function testFindOne()
    {
        $this->setData(100, "foo");

        $expected = DataCacheBehaviorMemcachedTestQuery::create()->findOneById(100);
        $this->deleteDirect("data_cache_behavior_memcached_test", 100);
        $actual = DataCacheBehaviorMemcachedTestQuery::create()->findOneById(100);

        $this->assertInstanceOf("DataCacheBehaviorMemcachedTest", $actual);
        $this->assertEquals($expected, $actual);
    }

    public function testFindPk()
    {
        $this->setData(100, "foo");

        $expected = DataCacheBehaviorMemcachedTestQuery::create()->findPk(100);
        $this->deleteDirect("data_cache_behavior_memcached_test", 100);
        $actual = DataCacheBehaviorMemcachedTestQuery::create()->findPk(100);

        $this->assertInstanceOf("DataCacheBehaviorMemcachedTest", $actual);
        $this->assertEquals($expected, $actual);
    }

    public function testSave()
    {
        $this->setData(100, "foo");

        $obj = DataCacheBehaviorMemcachedTestQuery::create()->findOneById(100);
        $obj->setName("bar");
        $obj->save();

        $this->assertEquals($obj, DataCacheBehaviorMemcachedTestQuery::create()->findOneById(100));
    }

    public function testDelete()
    {
        $this->setData(100, "foo");

        $obj = DataCacheBehaviorMemcachedTestQuery::create()->findOneById(100);
        $obj->delete();

        $this->assertNull(DataCacheBehaviorMemcachedTestQuery::create()->findOneById(100));
    }

    public function testDeleteAll()
    {
        $this->setData(100, "foo");
        $this->setData(200, "bar");

        DataCacheBehaviorMemcachedTestQuery::create()->findOneById(100);
        DataCacheBehaviorMemcachedTestQuery::create()->findOneById(200);
        DataCacheBehaviorMemcachedTestPeer::doDeleteAll();

        $this->assertNull(DataCacheBehaviorMemcachedTestQuery::create()->findOneById(100));
        $this->assertNull(DataCacheBehaviorMemcachedTestQuery::create()->findOneById(200));
    }

    public function testCacheEnableFlag()
    {
        $obj = DataCacheBehaviorMemcachedTestQuery::create();
        $this->assertTrue($obj->isCacheEnable());
        $obj->setCacheDisable();
        $this->assertFalse($obj->isCacheEnable());
        $obj->setCacheEnable();
        $this->assertTrue($obj->isCacheEnable());
    }

    public function testCacheSkip()
    {
        $this->setData(100, "foo");
        DataCacheBehaviorMemcachedTestQuery::create()->setCacheDisable()->findOneById(100);
        $this->deleteDirect("data_cache_behavior_memcached_test", 100);
        $this->assertNull(DataCacheBehaviorMemcachedTestQuery::create()->findOneById(100));
    }

    public function testCacheDelete()
    {
        $this->setData(100, "foo");
        $query = DataCacheBehaviorMemcachedTestQuery::create();
        $query->findOneById(100);
        $cacheKey = $query->getCacheKey();

        DataCacheBehaviorMemcachedTestPeer::cacheDelete($query->getCacheKey());
        $result = Domino\CacheStore\Factory::factory("memcached")->get("data_cache_behavior_memcached_test", $cacheKey);
        $this->assertNull($result);
    }

    public function testCacheLocale()
    {
        $this->setData(100, "foo");
        $lang_en = DataCacheBehaviorMemcachedTestQuery::create()
                    ->setCacheLocale("en")
                    ->findOneById(100);

        $this->deleteDirect("data_cache_behavior_memcached_test", 100);

        $cached_lang_en = DataCacheBehaviorMemcachedTestQuery::create()
                    ->setCacheLocale("en")
                    ->findOneById(100);

        $lang_ja = DataCacheBehaviorMemcachedTestQuery::create()
                    ->setCacheLocale("ja")
                    ->findOneById(100);

        $this->assertInstanceOf("DataCacheBehaviorMemcachedTest", $lang_en);
        $this->assertInstanceOf("DataCacheBehaviorMemcachedTest", $cached_lang_en);
        $this->assertEquals($lang_en, $cached_lang_en);
        $this->assertNull($lang_ja);
    }

    public function testCompatibleCacheKeyWithoutLocale()
    {
        $lang_en = DataCacheBehaviorMemcachedTestQuery::create()->setCacheLocale("en");
        $lang_en->findOneById(100);

        $lang_en_cache_key = $lang_en->getCacheKey();
        $this->assertRegExp('/^[a-zA-Z0-9]+_[a-zA-Z0-9]+_en$/', $lang_en_cache_key);

        $without_locale = DataCacheBehaviorMemcachedTestQuery::create();
        $without_locale->findOneById(100);

        $without_locale_cache_key = $without_locale->getCacheKey();
        $this->assertRegExp('/^[a-zA-Z0-9]+_[a-zA-Z0-9]+$/', $without_locale_cache_key);
    }

    private function setData($id, $name)
    {
        $obj = new DataCacheBehaviorMemcachedTest;
        $obj->setId($id)
            ->setName($name)
            ->save();
    }

    private function getBuilder($schema)
    {
        $builder = new PropelQuickBuilder();
        $config  = $builder->getConfig();
        $config->setBuildProperty('behavior.data_cache.class', __DIR__.'/../src/DataCacheBehavior');
        $builder->setConfig($config);
        $builder->setSchema($schema);

        return $builder;
    }

    private function deleteDirect($table, $id)
    {
        Propel::getConnection()->exec("DELETE FROM `{$table}` WHERE `id` = '{$id}'");
    }
}