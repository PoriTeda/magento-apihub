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
namespace Riki\Framework\Model;

use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class AbstractRepository
 *
 * @category  RIKI
 * @package   Riki\Framework\Model
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
abstract class AbstractRepository
{
    /**
     * Factory
     *
     * @var null|string
     */
    protected $factory;
    /**
     * SearchResultFactory
     *
     * @var \Magento\Framework\Api\Search\SearchResultInterfaceFactory
     */
    protected $searchResultFactory;
    /**
     * ResourceModel
     *
     * @var \Magento\Framework\Model\ResourceModel\Db\AbstractDb
     */
    protected $resourceModel;

    /**
     * AbstractRepository constructor.
     *
     * @param \Magento\Framework\Api\Search\SearchResultInterfaceFactory $resultFactory
     * @param null $factory
     *
     * @throws \Exception
     */
    public function __construct(
        \Magento\Framework\Api\Search\SearchResultInterfaceFactory $resultFactory,
        $factory = null
    ) {
        if (is_string($factory)) {
            $this->factory = \Magento\Framework\App\ObjectManager::getInstance()
                ->get($factory);
        }

        if (!$factory || !method_exists($factory, 'create')) {
            throw new \Exception(
                sprintf('Invalid factory class %s', get_class($factory))
            );
        }

        $this->factory = $factory;
        $this->resourceModel = $factory->create()->getResource();
        $this->searchResultFactory = $resultFactory;
    }

    /**
     * Get entity by id
     *
     * @param string $id id
     *
     * @return mixed
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($id)
    {
        $model = $this->factory->create()->load($id);
        if (!$model->getId()) {
            throw new \Magento\Framework\Exception\NoSuchEntityException(
                __('No such entity %1 with id %2', get_class($model), $id)
            );
        }

        return $model;
    }

    /**
     * Get collection entity
     *
     * @return \Magento\Framework\Data\Collection\AbstractDb
     */
    public function getCollection()
    {
        return $this->factory->create()->getCollection();
    }

    /**
     * Get list entity by searchCriteria
     *
     * @param \Magento\Framework\Api\SearchCriteria $searchCriteria searchCriteria
     *
     * @return \Magento\Framework\Api\Search\SearchResultInterface
     *
     * @throws \Exception
     */
    public function getList(\Magento\Framework\Api\SearchCriteria $searchCriteria)
    {
        /**
         * Type hinting
         *
         * @var \Magento\Framework\Api\Search\SearchResultInterface $result
         */
        $result = $this->searchResultFactory->create();
        /**
         * Type hinting
         *
         * @var AbstractCollection $collection
         */
        $collection = $this->getCollection();
        if (!$collection) {
            $msg = __(
                'Invalid collection which need implemented first on initialize %s',
                get_class($this)
            );
            throw new \Exception($msg);
        }

        foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
            $methodResult = $this->addFilterGroupToCollection($filterGroup, $collection);
            if (!is_null($methodResult)) {
                $result->setTotalCount(count($methodResult));
                $result->setItems($methodResult);
                return $result;
            }
        }

        $sortOrders = $searchCriteria->getSortOrders();
        if ($sortOrders) {
            foreach ($sortOrders as $sortOrder) {
                $collection->addOrder(
                    $sortOrder->getField(),
                    $sortOrder->getDirection()
                );
            }
        }

        $collection->setPageSize($searchCriteria->getPageSize());
        $collection->setCurPage($searchCriteria->getCurrentPage());

        $result->setTotalCount($collection->getSize());
        $result->setItems($collection->getItems());
        $result->setSearchCriteria($searchCriteria);

        return $result;
    }


    /**
     * Add filter into collection
     *
     * @param \Magento\Framework\Api\Search\FilterGroup     $group      group
     * @param \Magento\Framework\Data\Collection\AbstractDb $collection collection
     *
     * @return null
     */
    public function addFilterGroupToCollection(
        \Magento\Framework\Api\Search\FilterGroup $group,
        \Magento\Framework\Data\Collection\AbstractDb $collection
    ) {
        $fields = [];
        $conditions = [];
        foreach ($group->getFilters() as $filter) {
            if ($filter->getField() == 'callback_method' &&
                method_exists($collection, $filter->getConditionType())
            ) {
                $callbackMethod = $filter->getConditionType();
                $args = is_array($filter->getValue()) ?: [$filter->getValue()];
                return $collection->$callbackMethod(...$args);
            }
            $condition = $filter->getConditionType()
                ? $filter->getConditionType()
                : 'eq';

            if ($collection instanceof \Magento\Eav\Model\Entity\Collection\AbstractCollection) {
                $fields[] = [
                    'attribute' => $filter->getField(),
                    $condition => $filter->getValue()
                ];
            } else {
                $fields[] = $filter->getField();
                $conditions[] = [$condition => $filter->getValue()];
            }
        }
        if ($fields && $conditions) {
            $collection->addFieldToFilter($fields, $conditions);
        } else {
            $collection->addFieldToFilter($fields);
        }

        return null;
    }

    /**
     * Create entity from array
     *
     * @param array $data data
     *
     * @return mixed
     */
    public function createFromArray(array $data = [])
    {

        return $this->factory->create()->setData($data);
    }

    /**
     * Execute save model
     *
     * @param mixed $entity entity
     *
     * @return mixed
     * @throws \Exception
     */
    public function executeSave($entity)
    {
        if ($entity instanceof \Magento\Framework\DataObject) {
            $entity->unsetData('updated_at');
        }
        $this->resourceModel->save($entity);
        return $entity;
    }

    /**
     * Delete model by Id
     *
     * @param string $id id
     *
     * @return bool
     * @throws \Exception
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function deleteById($id)
    {
        $this->resourceModel->delete($this->getById($id));
        return true;
    }

    /**
     * Begin a transaction
     *
     * @return $this
     */
    public function beginTransaction()
    {
        $this->resourceModel->beginTransaction();
        return $this;
    }

    /**
     * Commit transaction
     *
     * @return $this
     */
    public function commit()
    {
        $this->resourceModel->commit();
        return $this;
    }

    /**
     * Rollback transaction
     *
     * @return $this
     */
    public function rollback()
    {
        $this->resourceModel->rollBack();
        return $this;
    }
}