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
class DataCacheBehaviorQueryBuilderModifier
{
    protected $behavior;
    protected $builder;

    public function __construct($behavior)
    {
        $this->behavior = $behavior;
    }

    public function postUpdateQuery($builder)
    {
        $peerClassname = $builder->getStubPeerBuilder()->getClassname();

        return "{$peerClassname}::purgeCache();";
    }

    public function postDeleteQuery($builder)
    {
        return $this->postUpdateQuery($builder);
    }

    public function queryAttributes($builder)
    {
        $lifetime   = $this->behavior->getParameter("lifetime");
        $auto_cache = $this->behavior->getParameter("auto_cache");

        $script = "
protected \$cacheKey      = '';
protected \$cacheEnable   = {$auto_cache};
protected \$cacheLifeTime = {$lifetime};
        ";

        return $script;
    }

    public function queryMethods($builder)
    {
        $builder->declareClasses('BasePeer');
        
        $this->builder = $builder;

        $script = "";
        $this->addSetCacheEnable($script);
        $this->addSetCacheDisable($script);
        $this->addIsCacheEnable($script);
        $this->addGetCacheKey($script);
        $this->addSetCacheKey($script);
        $this->addSetLifeTime($script);
        $this->addGetLifeTime($script);
        $this->addFind($script);
        $this->addFindOne($script);

        return $script;
    }

    public function queryFilter(&$script)
    {
        $parser = new PropelPHPParser($script, true);

        $this->replaceFindPk($parser);

        $script = $parser->getCode();
    }

    protected function addSetCacheEnable(&$script)
    {
        $script .= "
public function setCacheEnable()
{
    \$this->cacheEnable = true;

    return \$this;
}
        ";
    }

    protected function addSetCacheDisable(&$script)
    {
        $script .= "
public function setCacheDisable()
{
    \$this->cacheEnable = false;

    return \$this;
}
        ";
    }

    protected function addIsCacheEnable(&$script)
    {
        $script .= "
public function isCacheEnable()
{
    return (bool)\$this->cacheEnable;
}
        ";
    }

    protected function addGetCacheKey(&$script)
    {
        $script .= "
public function getCacheKey()
{
    if (\$this->cacheKey) {
        return \$this->cacheKey;
    }
    \$params      = array();
    \$sql_hash    = hash('md4', BasePeer::createSelectSql(\$this, \$params));
    \$params_hash = hash('md4', json_encode(\$params));

    \$this->cacheKey = \$sql_hash . '_' . \$params_hash;

    return \$this->cacheKey;
}
        ";
    }

    protected function addSetCacheKey(&$script)
    {
        $script .= "
public function setCacheKey(\$cacheKey)
{
    \$this->cacheKey = \$cacheKey;

    return \$this;
}
";
    }

    protected function addSetLifeTime(&$script)
    {
        $script .= "
public function setLifeTime(\$lifetime)
{
    \$this->cacheLifeTime = \$lifetime;

    return \$this;
}
        ";
    }

    protected function addGetLifeTime(&$script)
    {
        $script .= "
public function getLifeTime()
{
    return \$this->cacheLifeTime;
}
        ";
    }

    protected function addFind(&$script)
    {
        $className = $this->builder->getStubObjectBuilder()->getClassname();
        $peerClassname = $this->builder->getStubPeerBuilder()->getClassname();

        $script .= "
/**
 * Issue a SELECT query based on the current ModelCriteria
 * and format the list of results with the current formatter
 * By default, returns an array of model objects
 *
 * @param PropelPDO \$con an optional connection object
 *
 * @return PropelObjectCollection|array|mixed the list of results, formatted by the current formatter
 */
public function find(\$con = null)
{
    if (\$this->isCacheEnable() && \$cache = {$peerClassname}::cacheFetch(\$this->getCacheKey())) {
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
        {$peerClassname}::cacheStore(\$this->getCacheKey(), \$data, \$this->getLifeTime());
    }

    return \$data;
}
        ";
    }

    protected function addFindOne(&$script)
    {
        $className = $this->builder->getStubObjectBuilder()->getClassname();
        $peerClassname = $this->builder->getStubPeerBuilder()->getClassname();

        $script .= "
/**
 * Issue a SELECT ... LIMIT 1 query based on the current ModelCriteria
 * and format the result with the current formatter
 * By default, returns a model object
 *
 * @param PropelPDO \$con an optional connection object
 *
 * @return mixed the result, formatted by the current formatter
 */
public function findOne(\$con = null)
{
    if (\$this->isCacheEnable() && \$cache = {$peerClassname}::cacheFetch(\$this->getCacheKey())) {
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
        {$peerClassname}::cacheStore(\$this->getCacheKey(), \$data, \$this->getLifeTime());
    }

    return \$data;
}
        ";
    }

    protected function replaceFindPk(&$parser)
    {
        $className = $this->builder->getStubObjectBuilder()->getClassname();
        $peerClassname = $this->builder->getStubPeerBuilder()->getClassname();

        $script = "
    /**
     * Find object by primary key.
     * Propel uses the instance pool to skip the database if the object exists.
     * Go fast if the query is untouched.
     *
     * <code>
     * \$obj  = \$c->findPk(12, \$con);
     * \$obj  = \$c->findPk(array(1, 2), \$con);  # multiple primary keys
     * </code>
     *
     * @param mixed \$key Primary key to use for the query
     * @param     PropelPDO \$con an optional connection object
     *
     * @return   {$className}|{$className}[]|mixed the result, formatted by the current formatter
     */
    public function findPk(\$key, \$con = null)
    {
        if (is_array(\$key)) {
            \$keys = array();
            foreach (\$key as \$k) {
                \$keys[] = (string) \$k;
            }
            \$pool_key = serialize(\$keys);
        } else {
            \$pool_key = \$key;
        }

        if ((null !== (\$obj = {$peerClassname}::getInstanceFromPool(\$pool_key))) && \$this->getFormatter()->isObjectFormatter()) {
            // the object is alredy in the instance pool
            return \$obj;
        } else {
            // the object has not been requested yet, or the formatter is not an object formatter
            \$criteria = \$this->isKeepQuery() ? clone \$this : \$this;

            return \$this->filterByPrimaryKey(\$key)->findOne(\$con);
        }
    }
        ";

        $parser->replaceMethod("findPk", $script);
    }
}
