<?php


namespace Riki\Wamb\Model;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\NoSuchEntityException;

class RegisterRepository implements \Riki\Wamb\Api\RegisterRepositoryInterface
{
    /**
     * @var \Riki\Wamb\Api\Data\RegisterSearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var \Riki\Wamb\Model\RegisterFactory
     */
    protected $registerFactory;

    /**
     * WambRepository constructor.
     *
     * @param \Riki\Wamb\Model\RegisterFactory $registerFactory
     * @param \Riki\Wamb\Api\Data\RegisterSearchResultsInterfaceFactory $searchResultsFactory
     */
    public function __construct(
        \Riki\Wamb\Model\RegisterFactory $registerFactory,
        \Riki\Wamb\Api\Data\RegisterSearchResultsInterfaceFactory $searchResultsFactory
    ) {
        $this->registerFactory = $registerFactory;
        $this->searchResultsFactory = $searchResultsFactory;
    }

    /**
     * {@inheritdoc}
     *
     * @return \Riki\Wamb\Model\Register
     */
    public function save(\Riki\Wamb\Api\Data\RegisterInterface $register)
    {
        try {
            /** @var \Riki\Wamb\Model\Register $register */
            $register->save();
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the register: %1',
                $exception->getMessage()
            ));
        }

        return $register;
    }

    /**
     * {@inheritdoc}
     *
     * @return \Riki\Wamb\Model\Register
     */
    public function getById($id)
    {
        $register = $this->registerFactory->create();
        $register->load($id);
        if (!$register->getId()) {
            throw new NoSuchEntityException(__('Wamb with id "%1" does not exist.', $id));
        }

        return $register;
    }

    /**
     * {@inheritdoc}
     *
     * @return \Riki\Wamb\Api\Data\RegisterSearchResultsInterface
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $criteria)
    {
        $collection = $this->registerFactory->create()->getCollection();
        foreach ($criteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                $condition = $filter->getConditionType() ?: 'eq';
                $collection->addFieldToFilter($filter->getField(), [$condition => $filter->getValue()]);
            }
        }

        $sortOrders = $criteria->getSortOrders();
        if ($sortOrders) {
            /** @var SortOrder $sortOrder */
            foreach ($sortOrders as $sortOrder) {
                $collection->addOrder(
                    $sortOrder->getField(),
                    ($sortOrder->getDirection() == SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
                );
            }
        }
        $collection->setCurPage($criteria->getCurrentPage());
        $collection->setPageSize($criteria->getPageSize());

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);
        $searchResults->setTotalCount($collection->getSize());
        $searchResults->setItems($collection->getItems());

        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(\Riki\Wamb\Api\Data\RegisterInterface $register)
    {
        try {
            $register->delete();
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the wamb: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($id)
    {
        return $this->delete($this->getById($id));
    }

    /**
     * {@inheritdoc}
     *
     * @param array $data
     *
     * @return \Riki\Wamb\Api\Data\RegisterInterface
     */
    public function createFromArray($data = [])
    {
        return $this->registerFactory->create()->addData($data);
    }
}
