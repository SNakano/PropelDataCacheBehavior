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
class DataCacheBehaviorObjectBuilderModifier extends Behavior
{
    /**
     * @param DataModelBuilder $builder
     * @return string
     */
    public function postSave(DataModelBuilder $builder)
    {
        $peerClassName = $builder->getStubPeerBuilder()->getClassname();

        return "{$peerClassName}::purgeCache();";
    }

    /**
     * @param DataModelBuilder $builder
     * @return string
     */
    public function postDelete(DataModelBuilder $builder)
    {
        return $this->postSave($builder);
    }
}
