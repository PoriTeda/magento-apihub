<?php

namespace Wyomind\PointOfSale\Model;

class PointOfSale extends \Magento\Framework\Model\AbstractModel
{

    protected $_session = null;
    protected $_coreHelper = null;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Model\Session $session,
        \Wyomind\Core\Helper\Data $coreHelper,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_session = $session;
        $this->_coreHelper = $coreHelper;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    public function _construct()
    {
        $this->_init('Wyomind\PointOfSale\Model\ResourceModel\PointOfSale');
    }

    public function getPlaces()
    {
        $collection = $this->getCollection();
        return $collection;
    }

    public function getPlace($id)
    {

        return $this->getPlaces()->getPlace($id);
    }

    public function getPlacesByStoreId($storeId, $onlyVisible = false)
    {
        $whereGroupId = null;
        $login = $this->_session->isLoggedIn();
        if (!$this->_coreHelper->isAdmin()) {
            if (!$login) {
                $whereGroupId = 0;
            } else {
                $whereGroupId = $this->_session->getCustomerGroupId();
            }
        }
        $collection = $this->getPlaces();
        if ($onlyVisible) {
            $collection->addFieldToFilter('status', ['status' => 1]);
        }
        $collection->setOrder('`position`', 'ASC')->getPlacesByStoreId($storeId, $whereGroupId);

        return $collection;
    }

    public function getCountries($storeId)
    {

        return $this->getCollection()->addFieldToFilter('status', ['status' => 1])->getCountries($storeId);
    }

    public function getLastInsertedId()
    {
        $collection = $this->getCollection()->getLastInsertedId();
        return $collection->getFirstItem()->getPlaceId();
    }
}
