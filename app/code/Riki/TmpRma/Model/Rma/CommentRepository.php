<?php
/**
 * TmpRma
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\TmpRma
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\TmpRma\Model\Rma;

use Magento\Framework\Api;
use Riki\TmpRma\Model;
use Riki\TmpRma\Model\ResourceModel;
use Magento\Framework\Api\SortOrder;

/**
 * Class CommentRepository
 *
 * @category  RIKI
 * @package   Riki\TmpRma\Model
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class CommentRepository implements \Riki\TmpRma\Api\CommentRepositoryInterface
{
    /**
     * CommentFactory
     *
     * @var \Riki\TmpRma\Model\Rma\CommentFactory
     */
    protected $commentFactory;
    /**
     * SearchResultFactory
     *
     * @var \Magento\Framework\Api\SearchResultsInterfaceFactory
     */
    protected $searchResultFactory;

    /**
     * CommentRepository constructor.
     *
     * @param Api\SearchResultsInterfaceFactory $searchResultsFactory factory
     * @param Model\Rma\CommentFactory          $commentFactory       factory
     */
    public function __construct(
        \Magento\Framework\Api\SearchResultsInterfaceFactory $searchResultsFactory,
        \Riki\TmpRma\Model\Rma\CommentFactory $commentFactory
    ) {
        $this->searchResultFactory = $searchResultsFactory;
        $this->commentFactory = $commentFactory;
    }

    /**
     * Helper function that adds a FilterGroup to the collection.
     *
     * @param \Magento\Framework\Api\Search\FilterGroup $filterGroup filterGroup
     * @param ResourceModel\Rma\Comment\Collection      $collection  collection
     *
     * @return void
     */
    protected function addFilterGroupToCollection(
        \Magento\Framework\Api\Search\FilterGroup $filterGroup,
        \Riki\TmpRma\Model\ResourceModel\Rma\Comment\Collection $collection
    ) {

        foreach ($filterGroup->getFilters() as $filter) {
            $conditionType = $filter->getConditionType()
                ? $filter->getConditionType()
                : 'eq';
            $collection->addFieldToFilter(
                $filter->getField(),
                [
                    $conditionType => $filter->getValue()
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param Api\SearchCriteria $searchCriteria searchCriteria
     *
     * @return Api\SearchResultsInterface
     */
    public function getList(\Magento\Framework\Api\SearchCriteria $searchCriteria)
    {
        /**
         * Type hinting
         *
         * @var \Riki\TmpRma\Model\ResourceModel\Rma\Comment\Collection $collection
         */
        $collection = $this->commentFactory->create()->getCollection();

        //Add filters from root filter group to the collection
        foreach ($searchCriteria->getFilterGroups() as $group) {
            $this->addFilterGroupToCollection($group, $collection);
        }
        /**
         * Type hinting
         *
         * @var \Magento\Framework\Api\SortOrder $sortOrder
         */
        foreach ((array)$searchCriteria->getSortOrders() as $sortOrder) {
            $field = $sortOrder->getField();
            $collection->addOrder(
                $field,
                ($sortOrder->getDirection() == SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
            );
        }
        $collection->setCurPage($searchCriteria->getCurrentPage());
        $collection->setPageSize($searchCriteria->getPageSize());
        $collection->load();


        /**
         * Type hinting
         *
         * @var \Magento\Framework\Api\SearchResultsInterface $result
         */
        $result = $this->searchResultFactory->create();
        $result->setItems($collection->getItems());
        $result->setSearchCriteria($searchCriteria);
        $result->setTotalCount($collection->getSize());

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @param string|int $id id
     *
     * @return Comment
     */
    public function get($id)
    {
        /**
         * Type hinting
         *
         * @var \Riki\TmpRma\Model\Rma\Comment $comment
         */
        $comment = $this->commentFactory->create();
        $comment->load($id);

        return $comment;
    }

    /**
     * {@inheritdoc}
     *
     * @param \Riki\TmpRma\Api\Data\CommentInterface $entity entity
     *
     * @return bool
     *
     * @throws \Magento\Framework\Exception\StateException
     */
    public function delete(\Riki\TmpRma\Api\Data\CommentInterface $entity)
    {
        try {
            /**
             * Type hinting
             *
             * @var \Riki\TmpRma\Model\Rma\Comment $entity
             */
            $this->commentFactory->create()->getResource()->delete($entity);
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\StateException(
                __('Unable to remove comment %1', $entity->getEntityId())
            );
        }

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @param \Riki\TmpRma\Api\Data\CommentInterface $entity entity
     *
     * @return \Riki\TmpRma\Api\Data\CommentInterface|Comment
     *
     * @throws \Magento\Framework\Exception\StateException
     */
    public function save(\Riki\TmpRma\Api\Data\CommentInterface $entity)
    {
        try {
            /**
             * Type hinting
             *
             * @var \Riki\TmpRma\Model\Rma\Comment $entity
             */
            $this->commentFactory->create()->getResource()->save($entity);
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\StateException(
                __('Unable to save comment %1', $entity->getEntityId())
            );
        }

        return $entity;
    }

}
