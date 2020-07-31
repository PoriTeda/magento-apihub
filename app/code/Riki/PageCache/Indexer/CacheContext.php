<?php

namespace Riki\PageCache\Indexer;

class CacheContext extends \Magento\Framework\Indexer\CacheContext
{
    /**
     * reset entities by cache tag
     *
     * @param $cacheTag
     */
    public function resetEntitiesByCacheTag($cacheTag)
    {
        if (!empty($this->entities[$cacheTag])) {
            $this->entities[$cacheTag] = [];
        }
    }

    /**
     * reset entities
     */
    public function resetEntities()
    {
        $this->entities = [];
    }
}
