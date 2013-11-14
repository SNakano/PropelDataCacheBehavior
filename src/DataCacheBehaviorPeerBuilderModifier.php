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

        $search  = "\$con->commit();";
        $replace = "\$con->commit();\n            {$peerClassname}::purgeCache();";
        $script  = $parser->findMethod('doDeleteAll');
        $script  = str_replace($search, $replace, $script);

        $parser->replaceMethod("doDeleteAll", $script);
    }
}
