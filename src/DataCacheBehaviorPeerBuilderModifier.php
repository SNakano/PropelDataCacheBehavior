<?php
/**
 * Propel Data Cache Behavior
 *
 * @copyright Copyright (c) 2013 Domino Co. Ltd.
 * @license MIT
 * @package propel.generator.behavior
 */

/**
 * Propel Data Cache Behavior Peer Builder Modifier
 *
 * @copyright Copyright (c) 2013 Domino Co. Ltd.
 * @license MIT
 * @package propel.generator.behavior
 */
class DataCacheBehaviorPeerBuilderModifier
{
    protected $behavior;
    protected $builder;

    public function __construct($behavior)
    {
        $this->behavior = $behavior;
    }

    public function staticMethods($builder)
    {
        $this->builder = $builder;
        $script = "";

        $this->addPurgeCache($script);
        $this->addCacheFetch($script);
        $this->addCacheStore($script);
        $this->addCacheDelete($script);

        return $script;
    }

    public function peerFilter(&$script)
    {
        $parser = new PropelPHPParser($script, true);

        $this->replaceDoDeleteAll($parser);

        $script = $parser->getCode();
    }

    protected function addPurgeCache(&$script)
    {
        $backend = $this->behavior->getParameter("backend");

        $script .= "
public static function purgeCache()
{
    return \Domino\CacheStore\Factory::factory('{$backend}')->clearByNamespace(self::TABLE_NAME);
}
        ";
    }

    protected function addCacheFetch(&$script)
    {
        $backend = $this->behavior->getParameter("backend");
        $peerClassname = $this->builder->getStubPeerBuilder()->getClassname();
        $objectClassname = $this->builder->getStubObjectBuilder()->getClassname();

        $script .= "
public static function cacheFetch(\$key)
{
    \$result = \Domino\CacheStore\Factory::factory('{$backend}')->get(self::TABLE_NAME, \$key);

    if (\$result !== null) {
        if (\$result instanceof ArrayAccess) {
            foreach (\$result as \$element) {
                if (\$element instanceof {$objectClassname}) {
                    {$peerClassname}::addInstanceToPool(\$element);
                }
            }
        } else if (\$result instanceof {$objectClassname}) {
            {$peerClassname}::addInstanceToPool(\$result);
        }
    }

    return \$result;
}
        ";
    }

    protected function addCacheStore(&$script)
    {
        $backend = $this->behavior->getParameter("backend");

        $script .= "
public static function cacheStore(\$key, \$data, \$lifetime)
{
    return \Domino\CacheStore\Factory::factory('{$backend}')->set(self::TABLE_NAME, \$key, \$data, \$lifetime);
}
        ";
    }

    protected function addCacheDelete(&$script)
    {
        $backend = $this->behavior->getParameter("backend");

        $script .= "
public static function cacheDelete(\$key)
{
    return \Domino\CacheStore\Factory::factory('{$backend}')->clear(self::TABLE_NAME, \$key);
}
        ";
    }

    protected function replaceDoDeleteAll(&$parser)
    {
        $peerClassname = $this->builder->getStubPeerBuilder()->getClassname();
        $tableName = $this->builder->getStubObjectBuilder()->getTable()->getName();

        $script = "

    /**
     * Deletes all rows from the $tableName table.
     *
     * @param      PropelPDO \$con the connection to use
     * @return int             The number of affected rows (if supported by underlying database driver).
     * @throws PropelException
     */
    public static function doDeleteAll(PropelPDO \$con = null)
    {
        if (\$con === null) {
            \$con = Propel::getConnection({$peerClassname}::DATABASE_NAME, Propel::CONNECTION_WRITE);
        }
        \$affectedRows = 0; // initialize var to track total num of affected rows
        try {
            // use transaction because \$criteria could contain info
            // for more than one table or we could emulating ON DELETE CASCADE, etc.
            \$con->beginTransaction();
            \$affectedRows += BasePeer::doDeleteAll({$peerClassname}::TABLE_NAME, \$con, {$peerClassname}::DATABASE_NAME);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            {$peerClassname}::clearInstancePool();
            {$peerClassname}::clearRelatedInstancePool();
            \$con->commit();
            {$peerClassname}::purgeCache();

            return \$affectedRows;
        } catch (PropelException \$e) {
            \$con->rollBack();
            throw \$e;
        }
    }";

        $parser->replaceMethod("doDeleteAll", $script);
    }

}
