<?php
/**
 * Framework
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Framework
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\Framework\Helper;

/**
 * Class Search
 *
 * Search is used for simple generate search criteria for repository, I will add some small tip later
 *
 * @deprecated
 *
 * @category  RIKI
 * @package   Riki\Framework\Helper
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Search extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * UnderscoreCache
     *
     * @var array
     */
    protected static $underscoreCache = [];
    /**
     * @var array
     */
    protected $where;
    /**
     * @var array
     */
    protected $order;
    /**
     * @var array
     */
    protected $column;
    /**
     * @var int
     */
    protected $limit;
    /**
     * @var bool
     */
    protected $count;
    /**
     * @var bool
     */
    protected $cache;

    /**
     * @var Cache\FunctionCache
     */
    protected $functionCache;
    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    protected $filterBuilder;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searcherCriteriaBuilder;

    /**
     * @var \Magento\Framework\Api\Search\FilterGroupBuilder
     */
    protected $filterGroupBuilder;

    /**
     * @var \Magento\Framework\Api\SortOrderBuilder
     */
    protected $sortOrderBuilder;

    /**
     * Search constructor.
     *
     * @param \Magento\Framework\Api\SortOrderBuilder $sortOrderBuilder
     * @param \Magento\Framework\Api\Search\FilterGroupBuilder $filterGroupBuilder
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param Cache\FunctionCache $functionCache
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\Api\SortOrderBuilder $sortOrderBuilder,
        \Magento\Framework\Api\Search\FilterGroupBuilder $filterGroupBuilder,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->searcherCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->functionCache = $functionCache;
        parent::__construct($context);

        $this->init();
    }

    /**
     * Get
     *
     * @return $this
     */
    public function init()
    {
        $this->where = [];
        $this->order = [];
        $this->column = [];
        $this->limit = 10;
        $this->count = false;
        $this->cache = true;

        return $this;
    }

    /**
     * Converts field names for setters and getters
     *
     * Uses cache to eliminate unnecessary preg_replace
     *
     * @param string $name name
     *
     * @return string
     */
    private function _underscore($name)
    {
        if (isset(self::$underscoreCache[$name])) {
            return self::$underscoreCache[$name];
        }
        $result = strtolower(
            trim(preg_replace('/([A-Z]|[0-9]+)/', "_$1", $name), '_')
        );
        self::$underscoreCache[$name] = $result;
        return $result;
    }

    /**
     * Generate cache key
     *
     * @return string
     */
    protected function generateCacheKey()
    {
        $key = [];

        $key[] = intval($this->count);
        $key[] = $this->limit;

        if ($this->column) {
            natsort($this->column);
            $key[] = implode('_', $this->column);
        }
        if ($this->where) {
            foreach ($this->where as $where) {
                $key[] = $where['field'] . $where['conditionType'] . $where['value'];
            }
        }

        natsort($key);

        return implode('&', $key);
    }


    /**
     * Call magic method
     *
     * @param string $name      name
     * @param array  $arguments arguments
     *
     * @return $this
     */
    public function __call($name, $arguments)
    {
        $getBy = 'getBy';
        if (strpos($name, $getBy) === 0) {
            $fieldName = substr($name, strlen($getBy), strlen($name) - 1);
            $fieldName = $this->_underscore($fieldName);
            $filter = ['field' => $fieldName];
            $filter['conditionType'] = isset($arguments[1])
                ? $arguments[1]
                : (
                    isset($arguments[0])
                        ? (is_array($arguments[0]) ? 'in' :  'eq')
                        : 'eq'
                );
            $filter['value'] = isset($arguments[0]) ? $arguments[0] : new \Zend_Db_Expr('NULL');
            $this->where[] = $filter;

            return $this;
        }

        $sortBy = 'sortBy';
        if (strpos($name, $sortBy) === 0) {
            $fieldName = substr($name, strlen($sortBy), strlen($name) - 1);
            $fieldName = $this->_underscore($fieldName);
            $direction = isset($arguments[0]) ? $arguments[0] : \Magento\Framework\Api\SortOrder::SORT_DESC;
            $sortOrder = $this->sortOrderBuilder
                ->setField($fieldName)
                ->setDirection($direction)
                ->create();

            $this->order[] = $sortOrder;
            return $this;
        }
    }

    /**
     * Generate get method used for filter
     *
     * @param $name
     *
     * @return string
     */
    public function generateGetMethod($name)
    {
        return implode('', array_map('ucfirst',explode('_', strtolower($name))));
    }

    /**
     * Flush cache
     *
     * @return $this
     */
    public function flushCache()
    {
        $this->cache = false;
        return $this;
    }


    /**
     * Get all of records
     *
     * @return $this
     */
    public function getAll()
    {
        $this->limit = PHP_INT_MAX;

        return $this;
    }

    /**
     * Get only one record
     *
     * @return $this
     */
    public function getOne()
    {
        $this->limit = 1;
        return $this;
    }


    /**
     * Get count of rows result
     *
     * @return $this
     */
    public function getCount()
    {
        $this->count = true;
        return $this;
    }


    /**
     * Get limit
     *
     * @param int $limit
     *
     * @return mixed
     */
    public function limit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Get filters
     *
     * @return array
     */
    public function getFilters()
    {
        $filters = [];

        foreach ($this->where as $where) {
            $filters[] = $this->filterBuilder
                ->setField($where['field'])
                ->setConditionType($where['conditionType'])
                ->setValue($where['value'])
                ->create();
        }

        return $filters;
    }

    /**
     * Get sort orders
     *
     * @return array
     */
    public function getSortOrders()
    {
        return $this->order;
    }

    /**
     * Execute search request
     *
     * @param mixed $handler
     *
     * @return array|mixed
     */
    public function execute($handler)
    {
//        @ disable cache at this time because need implement invalidate by tags
//        $cacheKey = md5(get_class($handler)) . '->' . $this->generateCacheKey();
//
//        if ($this->cache) {
//            if ($this->functionCache->has($cacheKey)) {
//                return $this->functionCache->load($cacheKey);
//            }
//        }

        $limit = $this->limit;
        $filtersGroup = [];
        foreach ($this->getFilters() as $filter) {
            $filtersGroup[] = $this->filterGroupBuilder->addFilter($filter)->create();
        }
        $searchCriteria = $this->searcherCriteriaBuilder
            ->setFilterGroups($filtersGroup)
            ->setSortOrders($this->getSortOrders())
            ->setPageSize($limit)
            ->create();
        $list = $handler->getList($searchCriteria);
        if ($this->count) {
            $totalCount = $list->getTotalCount();
//            if ($this->cache) {
//                $this->functionCache->store($totalCount, $cacheKey);
//            }

            $this->init();
            return $totalCount;
        }
        $items = $list->getItems();

        $result = $items
            ? ($limit == 1 ? end($items) : $items)
            : [];


//        $this->functionCache->store($result, $cacheKey);

        $this->init();

        return $result;
    }
}