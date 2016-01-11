<?php
/**
 * Propel Data Cache Behavior
 *
 * @copyright Copyright (c) 2013 Domino Co. Ltd.
 * @license MIT
 * @package propel.generator.behavior
 */

/**
 * Propel Data Cache Behavior Query Builder Modifier
 *
 * @copyright Copyright (c) 2013 Domino Co. Ltd.
 * @license MIT
 * @package propel.generator.behavior
 */
class DataCacheBehaviorQueryBuilderModifier extends Behavior
{
    /** @var OMBuilder */
    protected $builder;

    /**
     * @param DataModelBuilder $builder
     * @return string
     */
    public function postUpdateQuery(DataModelBuilder $builder)
    {
        $peerClassName = $builder->getStubPeerBuilder()->getClassname();

        return "{$peerClassName}::purgeCache();";
    }

    /**
     * @param DataModelBuilder $builder
     * @return string
     */
    public function postDeleteQuery(DataModelBuilder $builder)
    {
        return $this->postUpdateQuery($builder);
    }

    /**
     * @return string
     */
    public function queryAttributes()
    {
        $lifetime   = $this->parameters['lifetime'];
        $auto_cache = $this->parameters['auto_cache'];

        $script = "
protected \$cacheKey      = '';
protected \$cacheLocale   = '';
protected \$cacheEnable   = {$auto_cache};
protected \$cacheLifeTime = {$lifetime};
        ";

        return $script;
    }

    /**
     * @param OMBuilder $builder
     * @return string
     */
    public function queryMethods(OMBuilder $builder)
    {
        $builder->declareClasses('BasePeer');

        $this->builder = $builder;

        $script = '';
        $this->addSetCacheEnable($script);
        $this->addSetCacheDisable($script);
        $this->addIsCacheEnable($script);
        $this->addGetCacheKey($script);
        $this->addSetCacheKey($script);
        $this->addSetLocale($script);
        $this->addSetLifeTime($script);
        $this->addGetLifeTime($script);
        $this->addFind($script);
        $this->addFindOne($script);
        $this->addPurgeFromCache($script);
        $this->addDeleteFromCache($script);

        return $script;
    }

    /**
     * @param string $script
     */
    public function queryFilter(&$script)
    {
        $parser = new PropelPHPParser($script, true);

        $this->replaceFindPk($parser);

        $script = $parser->getCode();
    }

    /**
     * @param string $script
     */
    protected function addSetCacheEnable(&$script)
    {
        $script .= "
/**
 * @return \$this
 */
public function setCacheEnable()
{
    \$this->cacheEnable = true;

    return \$this;
}
        ";
    }

    /**
     * @param string $script
     */
    protected function addSetCacheDisable(&$script)
    {
        $script .= "
/**
 * @return \$this
 */
public function setCacheDisable()
{
    \$this->cacheEnable = false;

    return \$this;
}
        ";
    }

    /**
     * @param string $script
     */
    protected function addIsCacheEnable(&$script)
    {
        $script .= "
/**
 * @return bool
 */
public function isCacheEnable()
{
    return (bool)\$this->cacheEnable;
}
        ";
    }

    /**
     * @param string $script
     */
    protected function addGetCacheKey(&$script)
    {
        $script .= "
/**
 * @return string
 */
public function getCacheKey()
{
    if (\$this->cacheKey) {
        return \$this->cacheKey;
    }
    \$params      = array();
    \$sql_hash    = hash('md4', BasePeer::createSelectSql(\$this, \$params));
    \$params_hash = hash('md4', json_encode(\$params));
    \$locale      = \$this->cacheLocale ? '_' . \$this->cacheLocale : '';
    \$this->cacheKey = \$sql_hash . '_' . \$params_hash . \$locale;

    return \$this->cacheKey;
}
        ";
    }

    /**
     * @param string $script
     */
    protected function addSetLocale(&$script)
    {
        $script .= "
/**
 * @param string \$locale
 * @return \$this
 */
public function setCacheLocale(\$locale)
{
    \$this->cacheLocale = \$locale;

    return \$this;
}
";
    }

    /**
     * @param string $script
     */
    protected function addSetCacheKey(&$script)
    {
        $script .= "
/**
 * @param string \$cacheKey
 * @return \$this
 */
public function setCacheKey(\$cacheKey)
{
    \$this->cacheKey = \$cacheKey;

    return \$this;
}
";
    }

    /**
     * @param string $script
     */
    protected function addSetLifeTime(&$script)
    {
        $script .= "
/**
 * @param int \$lifetime
 * @return \$this
 */
public function setLifeTime(\$lifetime)
{
    \$this->cacheLifeTime = \$lifetime;

    return \$this;
}
        ";
    }

    /**
     * @param string $script
     */
    protected function addGetLifeTime(&$script)
    {
        $script .= "
/**
 * @return int
 */
public function getLifeTime()
{
    return \$this->cacheLifeTime;
}
        ";
    }

    /**
     * @param string $script
     */
    protected function addFind(&$script)
    {
        $className      = $this->builder->getStubObjectBuilder()->getClassname();
        $peerClassName  = $this->builder->getStubPeerBuilder()->getClassname();

        $script .= "
/**
 * Issue a SELECT query based on the current ModelCriteria
 * and format the list of results with the current formatter
 * By default, returns an array of model objects
 *
 * @param PropelPDO \$con an optional connection object
 *
 * @return PropelObjectCollection|{$className}[]|array|mixed the list of results, formatted by the current formatter
 */
public function find(\$con = null)
{
    if (\$this->isCacheEnable() && \$cache = {$peerClassName}::cacheFetch(\$this->getCacheKey())) {
        if (\$cache instanceof \\PropelCollection) {
            \$formatter = \$this->getFormatter()->init(\$this);
            \$cache->setFormatter(\$formatter);
        }

        return \$cache;
    }

    if (\$con === null) {
        \$con = Propel::getConnection(\$this->getDbName(), Propel::CONNECTION_READ);
    }
    \$this->basePreSelect(\$con);
    \$criteria = \$this->isKeepQuery() ? clone \$this : \$this;
    \$stmt = \$criteria->doSelect(\$con);

    \$data = \$criteria->getFormatter()->init(\$criteria)->format(\$stmt);

    if (\$this->isCacheEnable()) {
        {$peerClassName}::cacheStore(\$this->getCacheKey(), \$data, \$this->getLifeTime());
    }

    return \$data;
}
        ";
    }

    /**
     * @param string $script
     */
    protected function addFindOne(&$script)
    {
        $className      = $this->builder->getStubObjectBuilder()->getClassname();
        $peerClassName  = $this->builder->getStubPeerBuilder()->getClassname();

        $script .= "
/**
 * Issue a SELECT ... LIMIT 1 query based on the current ModelCriteria
 * and format the result with the current formatter
 * By default, returns a model object
 *
 * @param PropelPDO \$con an optional connection object
 *
 * @return mixed|{$className} the result, formatted by the current formatter
 */
public function findOne(\$con = null)
{
    if (\$this->isCacheEnable() && \$cache = {$peerClassName}::cacheFetch(\$this->getCacheKey())) {
        if (\$cache instanceof {$className}) {
            return \$cache;
        }
    }

    if (\$con === null) {
        \$con = Propel::getConnection(\$this->getDbName(), Propel::CONNECTION_READ);
    }
    \$this->basePreSelect(\$con);
    \$criteria = \$this->isKeepQuery() ? clone \$this : \$this;
    \$criteria->limit(1);
    \$stmt = \$criteria->doSelect(\$con);

    \$data = \$criteria->getFormatter()->init(\$criteria)->formatOne(\$stmt);

    if (\$this->isCacheEnable()) {
        {$peerClassName}::cacheStore(\$this->getCacheKey(), \$data, \$this->getLifeTime());
    }

    return \$data;
}
        ";
    }

    /**
     * @param string $script
     */
    protected function addPurgeFromCache(&$script)
    {
        $peerClassName  = $this->builder->getStubPeerBuilder()->getClassname();

        $script .= '
/**
 * @return boolean            success or failure
 */
public function purgeFromCache()
{
    return ' . $peerClassName . '::purgeCache();
}
        ';
    }

    /**
     * @param string $script
     */
    protected function addDeleteFromCache(&$script)
    {
        $peerClassName  = $this->builder->getStubPeerBuilder()->getClassname();

        $script .= '
/**
 * @return boolean            success or failure
 */
public function deleteFromCache()
{
    return ' . $peerClassName . '::cacheDelete($this->getCacheKey());
}
        ';
    }

    /**
     * @param PropelPHPParser $parser
     */
    protected function replaceFindPk(PropelPHPParser $parser)
    {
        $search  = "return \$this->findPkSimple(\$key, \$con);";
        $replace = "return \$this->filterByPrimaryKey(\$key)->findOne(\$con);";
        $script  = $parser->findMethod('findPk');
        $script  = str_replace($search, $replace, $script);

        $parser->replaceMethod('findPk', $script);
    }
}
