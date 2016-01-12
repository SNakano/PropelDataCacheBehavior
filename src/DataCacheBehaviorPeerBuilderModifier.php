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
class DataCacheBehaviorPeerBuilderModifier extends Behavior
{
    /** @var DataModelBuilder */
    protected $builder;

    /**
     * @param DataModelBuilder $builder
     * @return string
     */
    public function staticMethods($builder)
    {
        $this->builder  = $builder;
        $script         = '';

        $this->addPurgeCache($script);
        $this->addCacheFetch($script);
        $this->addCacheStore($script);
        $this->addCacheDelete($script);

        return $script;
    }

    /**
     * @param string $script
     */
    public function peerFilter(&$script)
    {
        $parser = new PropelPHPParser($script, true);

        $this->replaceDoDeleteAll($parser);

        $script = $parser->getCode();
    }

    /**
     * @param string $script
     */
    protected function addPurgeCache(&$script)
    {
        $backend = $this->parameters['backend'];

        $script .= "
/**
 * @return boolean            success or failure
 */
public static function purgeCache()
{
    return \Domino\CacheStore\Factory::factory('{$backend}')->clearByNamespace(self::TABLE_NAME);
}
        ";
    }

    /**
     * @param string $script
     */
    protected function addCacheFetch(&$script)
    {
        $backend            = $this->parameters['backend'];
        $peerClassName      = $this->builder->getStubPeerBuilder()->getClassname();
        $objectClassName    = $this->builder->getStubObjectBuilder()->getClassname();
        $weAreInAnNameSpace = (strlen($this->builder->getStubPeerBuilder()->getNamespace()) > 0);

        $script .= "
/**
 * @param string \$key
 * @return null|array|{$objectClassName}|{$objectClassName}[]
 */
public static function cacheFetch(\$key)
{
    \$result = \Domino\CacheStore\Factory::factory('{$backend}')->get(self::TABLE_NAME, \$key);

    if (\$result !== null) {
        if (\$result instanceof " . ($weAreInAnNameSpace ? '\\' : '') . "ArrayAccess) {
            foreach (\$result as \$element) {
                if (\$element instanceof {$objectClassName}) {
                    {$peerClassName}::addInstanceToPool(\$element);
                }
            }
        } else if (\$result instanceof {$objectClassName}) {
            {$peerClassName}::addInstanceToPool(\$result);
        }
    }

    return \$result;
}
        ";
    }

    /**
     * @param string $script
     */
    protected function addCacheStore(&$script)
    {
        $backend = $this->parameters['backend'];

        $script .= "
/**
 * @param string \$key
 * @param mixed \$data
 * @param int \$lifetime
 */
public static function cacheStore(\$key, \$data, \$lifetime)
{
    return \Domino\CacheStore\Factory::factory('{$backend}')->set(self::TABLE_NAME, \$key, \$data, \$lifetime);
}
        ";
    }

    /**
     * @param string $script
     */
    protected function addCacheDelete(&$script)
    {
        $backend = $this->parameters['backend'];

        $script .= "
/**
 * @param string \$key
 * @return boolean            success or failure
 */
public static function cacheDelete(\$key)
{
    return \Domino\CacheStore\Factory::factory('{$backend}')->clear(self::TABLE_NAME, \$key);
}
        ";
    }



    /**
     * @param PropelPHPParser $parser
     */
    protected function replaceDoDeleteAll($parser)
    {
        $peerClassName = $this->builder->getStubPeerBuilder()->getClassname();

        $search  = "\$con->commit();";
        $replace = "\$con->commit();\n            {$peerClassName}::purgeCache();";
        $script  = $parser->findMethod('doDeleteAll');
        $script  = str_replace($search, $replace, $script);

        $parser->replaceMethod("doDeleteAll", $script);
    }
}
