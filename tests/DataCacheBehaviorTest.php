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

        $this->assertEquals($expected, $actual);
    }

    public function testFindOne()
    {
        $this->setData(100, "foo");

        $expected = DataCacheBehaviorMemcachedTestQuery::create()->findOneById(100);
        $this->deleteDirect("data_cache_behavior_memcached_test", 100);
        $actual = DataCacheBehaviorMemcachedTestQuery::create()->findOneById(100);

        $this->assertEquals($expected, $actual);
    }

    public function testFindPk()
    {
        $this->setData(100, "foo");

        $expected = DataCacheBehaviorMemcachedTestQuery::create()->findOneById(100);
        $this->deleteDirect("data_cache_behavior_memcached_test", 100);
        $actual = DataCacheBehaviorMemcachedTestQuery::create()->findOneById(100);

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