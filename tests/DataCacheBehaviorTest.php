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
        if (!ini_get('apc.enable_cli')) {
            $this->markTestSkipped("APC is not enabled");
        }

        if (!class_exists("DataCacheBehaviorApcTest")) {
            $schema = <<<EOF
<database name="data_cache_behavior_apc_test" defaultIdMethod="native">
    <table name="data_cache_behavior_apc_test">
        <column name="id" required="true" primaryKey="true" type="INTEGER" />
        <column name="name" type="VARCHAR" required="true" />
        <behavior name="data_cache">
            <parameter name="backend" value="apc" />
        </behavior>
    </table>
</database>
EOF;
            $this->getBuilder($schema)->build();
        }

        DataCacheBehaviorApcTestPeer::doDeleteAll();
        Propel::disableInstancePooling();
    }

    public function testFind()
    {
        $this->setData(100, "foo");

        $expected = DataCacheBehaviorApcTestQuery::create()->findById(100);
        $this->deleteDirect("data_cache_behavior_apc_test", 100);
        $actual = DataCacheBehaviorApcTestQuery::create()->findById(100);

        $this->assertEquals($expected, $actual);
    }

    public function testFindOne()
    {
        $this->setData(100, "foo");

        $expected = DataCacheBehaviorApcTestQuery::create()->findOneById(100);
        $this->deleteDirect("data_cache_behavior_apc_test", 100);
        $actual = DataCacheBehaviorApcTestQuery::create()->findOneById(100);

        $this->assertEquals($expected, $actual);
    }

    public function testFindPk()
    {
        $this->setData(100, "foo");

        $expected = DataCacheBehaviorApcTestQuery::create()->findOneById(100);
        $this->deleteDirect("data_cache_behavior_apc_test", 100);
        $actual = DataCacheBehaviorApcTestQuery::create()->findOneById(100);

        $this->assertEquals($expected, $actual);
    }

    public function testSave()
    {
        $this->setData(100, "foo");

        $obj = DataCacheBehaviorApcTestQuery::create()->findOneById(100);
        $obj->setName("bar");
        $obj->save();

        $this->assertEquals($obj, DataCacheBehaviorApcTestQuery::create()->findOneById(100));
    }

    public function testDelete()
    {
        $this->setData(100, "foo");

        $obj = DataCacheBehaviorApcTestQuery::create()->findOneById(100);
        $obj->delete();

        $this->assertNull(DataCacheBehaviorApcTestQuery::create()->findOneById(100));
    }

    public function testDeleteAll()
    {
        $this->setData(100, "foo");
        $this->setData(200, "bar");

        DataCacheBehaviorApcTestQuery::create()->findOneById(100);
        DataCacheBehaviorApcTestQuery::create()->findOneById(200);
        DataCacheBehaviorApcTestPeer::doDeleteAll();

        $this->assertNull(DataCacheBehaviorApcTestQuery::create()->findOneById(100));
        $this->assertNull(DataCacheBehaviorApcTestQuery::create()->findOneById(200));
    }

    public function testCacheEnableFlag()
    {
        $obj = DataCacheBehaviorApcTestQuery::create();
        $this->assertTrue($obj->isCacheEnable());
        $obj->setCacheDisable();
        $this->assertFalse($obj->isCacheEnable());
        $obj->setCacheEnable();
        $this->assertTrue($obj->isCacheEnable());
    }

    public function testCacheSkip()
    {
        $this->setData(100, "foo");
        DataCacheBehaviorApcTestQuery::create()->setCacheDisable()->findOneById(100);
        $this->deleteDirect("data_cache_behavior_apc_test", 100);
        $this->assertNull(DataCacheBehaviorApcTestQuery::create()->findOneById(100));
    }

    private function setData($id, $name)
    {
        $obj = new DataCacheBehaviorApcTest;
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