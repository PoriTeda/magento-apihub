<?php

namespace Riki\Framework\Helper\Cache;

abstract class AbstractCache extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var array
     */
    protected $loadedData = [];

    /**
     * @var array
     */
    protected $cacheTags = [];

    /**
     * Get cache key
     *
     * @param $params
     * @return int|string
     *
     * @throws \Exception
     */
    abstract public function getCacheKey($params);

    /**
     * Load data by cache key
     *
     * @param null $params
     *
     * @return mixed|null
     */
    public function load($params = null)
    {
        if (is_array($params) && isset($params['cacheTag'])) {
            unset($params['cacheTag']);
        }

        $cacheKey = $this->getCacheKey($params);

        return isset($this->loadedData[$cacheKey])
            ? $this->loadedData[$cacheKey]
            : null;
    }

    /**
     * Store data by cache key
     *
     * @param $value
     * @param null $params
     *
     * @return $this
     */
    public function store($value, $params = null)
    {
        $cacheTags = [];
        if (is_array($params) && isset($params['cacheTag'])) {
            $params['cacheTag'] = is_array($params['cacheTag'])
                ? $params['cacheTag']
                : [$params['cacheTag']];
            array_push($cacheTags, ...$params['cacheTag']);
            unset($params['cacheTag']);
        }

        $cacheKey = $this->getCacheKey($params);
        $this->loadedData[$cacheKey] = $value;

        if ($value instanceof \Magento\Framework\Model\AbstractModel) {
            $cacheTags[] = get_class($value) . '_' . $value->getId();

        }

        foreach ($cacheTags as $cacheTag) {
            if (isset($this->cacheTags[$cacheTag])) {
                $this->cacheTags[$cacheTag][] = $cacheKey;
            } else {
                $this->cacheTags[$cacheTag] = [$cacheKey];
            }
        }

        return $this;
    }

    /**
     * Check cache data exist by cache key
     *
     * @param null $params
     *
     * @return bool
     */
    public function has($params = null)
    {
        if (is_array($params) && isset($params['cacheTag'])) {
            unset($params['cacheTag']);
        }

        return array_key_exists($this->getCacheKey($params), $this->loadedData);
    }

    /**
     * Invalidate cache by params
     *
     * @param $params
     *
     * @return $this
     */
    public function invalidate($params)
    {
        return $this->invalidateByCacheKey($this->getCacheKey($params));
    }

    /**
     * Invalidate cache by cache key
     *
     * @param $cacheKey
     *
     * @return $this
     */
    public function invalidateByCacheKey($cacheKey)
    {
        if (isset($this->loadedData[$cacheKey])) {
            unset($this->loadedData[$cacheKey]);
        }

        return $this;
    }

    /**
     * Invalidate cache by cache tag
     *
     * @param $cacheTag
     *
     * @return $this
     */
    public function invalidateByCacheTag($cacheTag)
    {
        $cacheTags = is_array($cacheTag) ? $cacheTag : [$cacheTag];

        foreach ($cacheTags as $cacheTag) {
            if (!isset($this->cacheTags[$cacheTag])) {
                continue;
            }
            foreach ($this->cacheTags[$cacheTag] as $cacheKey) {
                $this->invalidateByCacheKey($cacheKey);
            }
        }

        return $this;
    }

    /**
     * @param $cacheKey
     * @param $value
     * @return mixed
     */
    public function forceData($cacheKey, $value)
    {
        return $this->loadedData[$cacheKey] = $value;
    }
}