<?php
namespace Riki\Rma\Helper;

class Json extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * To json format
     *
     * @param $tree
     * @return string
     *
     * @throws \Magento\Framework\Exception\InputException
     */
    public function toJson($tree)
    {
        if (is_null($tree)) {
            $tree = [];
        }
        if (!is_array($tree)) {
            throw new \Magento\Framework\Exception\InputException(__('Argument $tree must be array type'));
        }

        return \Zend_Json::encode($tree);
    }

    /**
     * To array format
     *
     * @param $tree
     *
     * @return mixed
     */
    public function toArray($tree)
    {
        return \Zend_Json::decode(is_null($tree) ? '{}' : $tree);
    }

    /**
     * Remove json node
     *
     * @param array|string $tree
     * @param string $node
     *
     * @return array
     *
     * @throws \Magento\Framework\Exception\InputException
     */
    public function removeNode($tree, $node = '')
    {
        $returnType = 'array';
        if (!is_array($tree)) {
            $tree = $this->toArray($tree);
            $returnType = 'json';
            if (!is_array($tree)) {
                throw new \Magento\Framework\Exception\InputException(__('Argument $tree must be array/json type'));
            }
        }

        // support remove 5th deep key
        $node = array_pad(explode('/', $node), 5, null);

        list($i, $j, $k, $m, $n) = $node;
        if (isset($i) && isset($j) && isset($k) && isset($m) && isset($n)) {
            unset($tree[$i][$j][$k][$m][$n]);
        } elseif (isset($i) && isset($j) && isset($k) && isset($m)) {
            unset($tree[$i][$j][$k][$m]);
        } elseif (isset($i) && isset($j) && isset($k)) {
            unset($tree[$i][$j][$k]);
        } elseif (isset($i) && isset($j)) {
            unset($tree[$i][$j]);
        } elseif (isset($i)) {
            unset($tree[$i]);
        }

        if ($returnType == 'json') {
            $tree = $this->toJson($tree);
        }

        return $tree;
    }

    /**
     * Add json node
     *
     * @param array|string $tree
     * @param array|string $node
     *
     * @return array
     *
     * @throws \Magento\Framework\Exception\InputException
     */
    public function addNode($tree, $node)
    {
        $returnType = 'array';
        if (!is_array($tree)) {
            $tree = $this->toArray($tree);
            $returnType = 'json';
            if (!is_array($tree)) {
                throw new \Magento\Framework\Exception\InputException(__('Argument $tree must be array/json type'));
            }
        }

        if (!is_array($node)) {
            $node = $this->toArray($node);
            if (!is_array($node)) {
                throw new \Magento\Framework\Exception\InputException(__('Argument $node must be array/json type'));
            }

        }

        $tree = array_merge_recursive($tree, $node);
        if ($returnType == 'json') {
            $tree = $this->toJson($tree);
        }

        return $tree;
    }

    /**
     * Get node
     *
     * @param array|string $tree
     * @param string $node
     *
     * @return mixed|null
     *
     * @throws \Magento\Framework\Exception\InputException
     */
    public function getNode($tree, $node)
    {
        if (!is_array($tree)) {
            $tree = $this->toArray($tree);
            if (!is_array($tree)) {
                throw new \Magento\Framework\Exception\InputException(__('Argument $tree must be array/json type'));
            }
        }

        // why use array_reverse + array_pop instead of array_shift? pls google
        $keys = array_reverse(explode('/', $node));
        while ($key = array_pop($keys)) {
            if (!isset($tree[$key])) {
                return null;
            }

            $tree = $tree[$key];
        }

        return $tree;
    }
}