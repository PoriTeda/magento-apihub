<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Model;

use Magento\CustomerSegment\Helper\Data;

/**
 * Segment/customer relation model. Model working in website scope.
 *
 * If website is not declared all methods are working in current ran website scope
 *
 * @method int getSegmentId()
 * @method Customer setSegmentId(int $value)
 * @method int getCustomerId()
 * @method Customer setCustomerId(int $value)
 * @method string getAddedDate()
 * @method Customer setAddedDate(string $value)
 * @method string getUpdatedDate()
 * @method Customer setUpdatedDate(string $value)
 * @method int getWebsiteId()
 * @method Customer setWebsiteId(int $value)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @api
 * @since 100.0.2
 */
class Customer extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * Array of Segments collections per event name
     *
     * @var array
     */
    protected $_segmentMap = [];

    /**
     * Array of segment ids per customer id and website id
     *
     * @var array
     */
    protected $_customerWebsiteSegments = [];

    /**
     * @var \Magento\Customer\Model\Visitor
     */
    protected $_visitor;

    /**
     * @var \Magento\Customer\Model\Config\Share
     */
    protected $_configShare;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer
     */
    protected $_resourceCustomer;

    /**
     * @var \Magento\CustomerSegment\Model\ResourceModel\Segment\CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * Store list manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $_httpContext;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\CustomerSegment\Model\ResourceModel\Segment\CollectionFactory $collectionFactory
     * @param \Magento\Customer\Model\ResourceModel\Customer $resourceCustomer
     * @param \Magento\Customer\Model\Config\Share $configShare
     * @param \Magento\Customer\Model\Visitor $visitor
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\CustomerSegment\Model\ResourceModel\Segment\CollectionFactory $collectionFactory,
        \Magento\Customer\Model\ResourceModel\Customer $resourceCustomer,
        \Magento\Customer\Model\Config\Share $configShare,
        \Magento\Customer\Model\Visitor $visitor,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_storeManager = $storeManager;
        $this->_collectionFactory = $collectionFactory;
        $this->_resourceCustomer = $resourceCustomer;
        $this->_configShare = $configShare;
        $this->_visitor = $visitor;
        $this->_customerSession = $customerSession;
        $this->_httpContext = $httpContext;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Class constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init(\Magento\CustomerSegment\Model\ResourceModel\Customer::class);
    }

    /**
     * Get list of active segments for specific event
     *
     * @param string $eventName
     * @param int $websiteId
     * @return \Magento\CustomerSegment\Model\ResourceModel\Segment\Collection
     */
    public function getActiveSegmentsForEvent($eventName, $websiteId)
    {
        if (!isset($this->_segmentMap[$eventName][$websiteId])) {
            $relatedSegments = $this->_collectionFactory->create()
                ->addEventFilter($eventName)
                ->addWebsiteFilter($websiteId)
                ->addIsActiveFilter(1);
            $this->_segmentMap[$eventName][$websiteId] = $relatedSegments;
        }
        return $this->_segmentMap[$eventName][$websiteId];
    }

    /**
     * Match all related to event segments and assign/deassign customer/visitor to segments on specific website
     *
     * @param   string $eventName
     * @param   \Magento\Customer\Model\Customer|int $customer
     * @param   \Magento\Store\Model\Website|int $website
     * @return  $this
     */
    public function processEvent($eventName, $customer, $website)
    {
        \Magento\Framework\Profiler::start('__SEGMENTS_MATCHING__');

        $website = $this->_storeManager->getWebsite($website);
        $segments = $this->getActiveSegmentsForEvent($eventName, $website->getId());

        $this->_processSegmentsValidation($customer, $website, $segments);

        \Magento\Framework\Profiler::stop('__SEGMENTS_MATCHING__');
        return $this;
    }

    /**
     * Validate all segments for specific customer/visitor on specific website
     *
     * @param   \Magento\Customer\Model\Customer $customer
     * @param   \Magento\Store\Model\Website $website
     * @return  $this
     */
    public function processCustomer(\Magento\Customer\Model\Customer $customer, $website)
    {
        $website = $this->_storeManager->getWebsite($website);
        $segments = $this->_collectionFactory->create()->addWebsiteFilter($website)->addIsActiveFilter(1);

        $this->_processSegmentsValidation($customer, $website, $segments);

        return $this;
    }

    /**
     * Check if customer is related to segments and update customer-segment relations
     *
     * @param int|null|\Magento\Customer\Model\Customer $customer
     * @param \Magento\Store\Model\Website $website
     * @param \Magento\CustomerSegment\Model\ResourceModel\Segment\Collection $segments
     * @return $this
     */
    protected function _processSegmentsValidation($customer, $website, $segments)
    {
        $websiteId = $website->getId();
        if ($customer instanceof \Magento\Customer\Model\Customer) {
            $customerId = $customer->getId();
        } else {
            $customerId = $customer;
        }

        $matchedIds = [];
        $notMatchedIds = [];
        $useVisitorId = !$customer || !$customerId;
        /** @var Segment $segment */
        foreach ($segments as $segment) {
            if ($useVisitorId) {
                // Skip segment if it cannot be applied to visitor
                if ($segment->getApplyTo() == Segment::APPLY_TO_REGISTERED) {
                    continue;
                }
                $segment->setVisitorId($this->_visitor->getId());
                $segment->setQuoteId($this->_visitor->getQuoteId());
            } else {
                // Skip segment if it cannot be applied to customer
                if ($segment->getApplyTo() == Segment::APPLY_TO_VISITORS) {
                    continue;
                }
            }
            $isMatched = $segment->validateCustomer($customer, $website);
            if ($isMatched) {
                $matchedIds[] = $segment->getId();
            } else {
                $notMatchedIds[] = $segment->getId();
            }
        }

        if ($customerId) {
            $this->addCustomerToWebsiteSegments($customerId, $websiteId, $matchedIds);
            $this->removeCustomerFromWebsiteSegments($customerId, $websiteId, $notMatchedIds);
        } else {
            $this->addVisitorToWebsiteSegments($this->_customerSession, $websiteId, $matchedIds);
            $this->removeVisitorFromWebsiteSegments($this->_customerSession, $websiteId, $notMatchedIds);
        }

        return $this;
    }

    /**
     * Match customer id to all segments related to event on all websites where customer can be presented
     *
     * @param string $eventName
     * @param int $customerId
     * @return $this
     */
    public function processCustomerEvent($eventName, $customerId)
    {
        if ($this->_configShare->isWebsiteScope()) {
            $websiteIds = $this->_resourceCustomer->getWebsiteId($customerId);
            if ($websiteIds) {
                $websiteIds = [$websiteIds];
            } else {
                $websiteIds = [];
            }
        } else {
            $websiteIds = $this->_storeManager->getWebsites();
            $websiteIds = array_keys($websiteIds);
        }
        foreach ($websiteIds as $websiteId) {
            $this->processEvent($eventName, $customerId, $websiteId);
        }
        return $this;
    }

    /**
     * Add visitor-segment relation for specified website
     *
     * @param \Magento\Framework\Session\SessionManagerInterface $visitorSession
     * @param int $websiteId
     * @param array $segmentIds
     * @return $this
     */
    public function addVisitorToWebsiteSegments($visitorSession, $websiteId, $segmentIds)
    {
        $visitorSegmentIds = $visitorSession->getCustomerSegmentIds();
        if (!is_array($visitorSegmentIds)) {
            $visitorSegmentIds = [];
        }
        if (isset($visitorSegmentIds[$websiteId]) && is_array($visitorSegmentIds[$websiteId])) {
            $segmentsIdsForWebsite = $visitorSegmentIds[$websiteId];
            if (!empty($segmentIds)) {
                $segmentsIdsForWebsite = array_unique(array_merge($segmentsIdsForWebsite, $segmentIds));
            }
            $visitorSegmentIds[$websiteId] = $segmentsIdsForWebsite;
        } else {
            $visitorSegmentIds[$websiteId] = $segmentIds;
        }

        $visitorSession->setCustomerSegmentIds($visitorSegmentIds);

        $value = array_filter($visitorSegmentIds[$websiteId]);
        $this->_httpContext->setValue(Data::CONTEXT_SEGMENT, $value, $value);

        return $this;
    }

    /**
     * Remove visitor-segment relation for specified website
     *
     * @param \Magento\Framework\Session\SessionManagerInterface $visitorSession
     * @param int $websiteId
     * @param array $segmentIds
     * @return $this
     */
    public function removeVisitorFromWebsiteSegments($visitorSession, $websiteId, $segmentIds)
    {
        $visitorCustomerSegmentIds = $visitorSession->getCustomerSegmentIds();
        if (!is_array($visitorCustomerSegmentIds)) {
            $visitorCustomerSegmentIds = [];
        }
        if (isset($visitorCustomerSegmentIds[$websiteId]) && is_array($visitorCustomerSegmentIds[$websiteId])) {
            $segmentsIdsForWebsite = $visitorCustomerSegmentIds[$websiteId];
            if (!empty($segmentIds)) {
                $segmentsIdsForWebsite = array_diff($segmentsIdsForWebsite, $segmentIds);
            }
            $visitorCustomerSegmentIds[$websiteId] = $segmentsIdsForWebsite;
        }

        $visitorSession->setCustomerSegmentIds($visitorCustomerSegmentIds);

        $value = isset($visitorCustomerSegmentIds[$websiteId])
            ? array_filter($visitorCustomerSegmentIds[$websiteId])
            : [];

        $this->_httpContext->setValue(Data::CONTEXT_SEGMENT, $value, $value);

        return $this;
    }

    /**
     * Add customer relation with segment for specific website.
     *
     * @param int $customerId
     * @param int $websiteId
     * @param array $segmentIds
     * @return $this
     */
    public function addCustomerToWebsiteSegments(int $customerId, int $websiteId, array $segmentIds): Customer
    {
        $existingIds = $this->getCustomerSegmentIdsForWebsite($customerId, $websiteId);
        $this->_getResource()->addCustomerToWebsiteSegments($customerId, $websiteId, $segmentIds);
        $this->_customerWebsiteSegments[$websiteId][$customerId] = array_unique(
            array_merge($existingIds, $segmentIds)
        );

        $value = array_filter($this->_customerWebsiteSegments[$websiteId][$customerId]);
        $this->_httpContext->setValue(Data::CONTEXT_SEGMENT, $value, $value);

        $visitorCustomerSegmentIds = $this->_customerSession->getCustomerSegmentIds();

        $visitorCustomerSegmentIds[$websiteId] = !empty($visitorCustomerSegmentIds[$websiteId])
            ?  array_unique(array_merge($visitorCustomerSegmentIds[$websiteId], $value))
            :  $value;
        $this->_customerSession->setCustomerSegmentIds($visitorCustomerSegmentIds);

        return $this;
    }

    /**
     * Remove customer id association with segment ids on specific website
     *
     * @param int $customerId
     * @param int $websiteId
     * @param array $segmentIds
     * @return $this
     */
    public function removeCustomerFromWebsiteSegments($customerId, $websiteId, $segmentIds)
    {
        $existingIds = $this->getCustomerSegmentIdsForWebsite($customerId, $websiteId);
        $this->_getResource()->removeCustomerFromWebsiteSegments($customerId, $websiteId, $segmentIds);
        $this->_customerWebsiteSegments[$websiteId][$customerId] = array_diff($existingIds, $segmentIds);
        return $this;
    }

    /**
     * Get segment ids for specific customer id and website id
     *
     * @param int $customerId
     * @param int $websiteId
     * @return array
     */
    public function getCustomerSegmentIdsForWebsite($customerId, $websiteId)
    {
        if (!$customerId || !$websiteId) {
            return [];
        }
        if (!isset($this->_customerWebsiteSegments[$websiteId][$customerId])) {
            $this->_customerWebsiteSegments[$websiteId][$customerId] = $this
                ->_getResource()
                ->getCustomerWebsiteSegments(
                    $customerId,
                    $websiteId
                );
        }
        return $this->_customerWebsiteSegments[$websiteId][$customerId];
    }

    /**
     * Retrieve segment ids for the current customer and current website
     *
     * @return array
     *
     * @deprecated 101.0.0 This method works incorrectly in admin panel and should be avoided
     */
    public function getCurrentCustomerSegmentIds()
    {
        /** @var \Magento\Customer\Model\Session $customerSession */
        $customerSession = $this->_customerSession;
        $result = [];
        /** @var \Magento\Customer\Model\Customer $customer */
        $customer = $this->_registry->registry('segment_customer');
        if (!$customer) {
            $customer = $customerSession->getCustomer();
        }
        $websiteId = $this->_storeManager->getWebsite()->getId();
        if (!$customer->getId()) {
            $allSegmentIds = $customerSession->getCustomerSegmentIds();
            if (is_array($allSegmentIds) && isset($allSegmentIds[$websiteId])) {
                $result = $allSegmentIds[$websiteId];
            }
        } else {
            $result = $this->getCustomerSegmentIdsForWebsite(
                $customer->getId(),
                $this->_storeManager->getWebsite()->getId()
            );
        }
        return $result;
    }
}
