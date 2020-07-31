<?php
namespace Riki\Framework\Helper\Cache;

class AppCache extends AbstractCache
{
    /**
     * {@inheritdoc}
     *
     * @param $params
     *
     * @return int|string
     *
     * @throws \Exception
     */
    public function getCacheKey($params)
    {
        if (is_string($params) || is_int($params) || is_float($params)) {
            return $params;
        }
        if (is_null($params) || is_bool($params)) {
            return intval($params);
        }
        if (!is_array($params)) {
            return spl_object_hash($params);
        }

        if (!$params) {
            return '_[]';
        }

        $uniqueKey = [];
        foreach ($params as $param) {
            if (is_array($param)) {
                throw new \Exception(
                    'Unique key for function cache can generated on nested array.'
                );
            }

            if (is_string($param) || is_int($param) || is_float($param)) {
                $uniqueKey[] = $param;
                continue;
            }
            if (is_null($param) || is_bool($param)) {
                $uniqueKey[] = intval($param);
                continue;
            }
            if (!is_array($param)) {
                $uniqueKey[] = spl_object_hash($param);
                continue;
            }

            if (!$param) {
                $uniqueKey[] = '_[]';
                continue;
            }
        }

        return implode('_', $uniqueKey);
    }
}