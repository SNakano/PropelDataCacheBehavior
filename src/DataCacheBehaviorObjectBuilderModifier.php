<?php
/**
 * Propel Data Cache Behavior
 *
 * @copyright Copyright (c) 2013 Domino Co. Ltd.
 * @license MIT
 * @package propel.generator.behavior
 */

/**
 * Propel Data Cache Behavior Object Build Modifier
 *
 * @copyright Copyright (c) 2013 Domino Co. Ltd.
 * @license MIT
 * @package propel.generator.behavior
 */
class DataCacheBehaviorObjectBuilderModifier
{
    protected $behavior;

    public function __construct($behavior)
    {
        $this->behavior = $behavior;
    }

    public function postSave($builder)
    {
        $peerClassname = $builder->getStubPeerBuilder()->getClassname();

        return "{$peerClassname}::purgeCache();";
    }

    public function postDelete($builder)
    {
        return $this->postSave($builder);
    }
}
