<?php
require_once 'DataCacheBehaviorPeerBuilderModifier.php';
require_once 'DataCacheBehaviorObjectBuilderModifier.php';
require_once 'DataCacheBehaviorQueryBuilderModifier.php';

/**
 * Propel Data Cache Behavior
 *
 * @copyright Copyright (c) 2013 Domino Co. Ltd.
 * @license MIT
 * @package propel.generator.behavior
 */

/**
 * Propel Data Cache Behavior
 *
 * @copyright Copyright (c) 2013 Domino Co. Ltd.
 * @license MIT
 * @package propel.generator.behavior
 */
class DataCacheBehavior extends Behavior
{
    /** @var array */
    protected $parameters = array(
        'auto_cache'    => true,
        'backend'       => 'apc',
        'lifetime'      => 3600
    );

    /** @var DataCacheBehaviorPeerBuilderModifier */
    protected $peerBuilderModifier;

    /** @var DataCacheBehaviorObjectBuilderModifier */
    protected $objectBuilderModifier;

    /** @var DataCacheBehaviorQueryBuilderModifier */
    protected $queryBuilderModifier;

    /**
     * @return DataCacheBehaviorPeerBuilderModifier
     */
    public function getPeerBuilderModifier()
    {
        if (is_null($this->peerBuilderModifier)) {
            $this->peerBuilderModifier = new DataCacheBehaviorPeerBuilderModifier($this);
        }
        $this->peerBuilderModifier->setParameters($this->parameters);

        return $this->peerBuilderModifier;
    }

    /**
     * @return DataCacheBehaviorObjectBuilderModifier
     */
    public function getObjectBuilderModifier()
    {
        if (is_null($this->objectBuilderModifier)) {
            $this->objectBuilderModifier = new DataCacheBehaviorObjectBuilderModifier($this);
        }
        $this->objectBuilderModifier->setParameters($this->parameters);

        return $this->objectBuilderModifier;
    }

    /**
     * @return DataCacheBehaviorQueryBuilderModifier
     */
    public function getQueryBuilderModifier()
    {
        if (is_null($this->queryBuilderModifier)) {
            $this->queryBuilderModifier = new DataCacheBehaviorQueryBuilderModifier($this);
        }
        $this->queryBuilderModifier->setParameters($this->parameters);

        return $this->queryBuilderModifier;
    }
}
