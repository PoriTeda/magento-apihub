<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Controller\Adminhtml\Report\Customer;

/**
 * Customer Segment reports controller
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
abstract class Customersegment extends \Magento\Backend\App\Action
{
    /**
     * Admin session
     *
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $_adminSession;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Magento\CustomerSegment\Model\ResourceModel\Segment\CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $_fileFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\CustomerSegment\Model\ResourceModel\Segment\CollectionFactory $collectionFactory
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\CustomerSegment\Model\ResourceModel\Segment\CollectionFactory $collectionFactory,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory
    ) {
        $this->_collectionFactory = $collectionFactory;
        $this->_coreRegistry = $coreRegistry;
        $this->_fileFactory = $fileFactory;
        parent::__construct($context);
    }

    /**
     * Init layout and adding breadcrumbs
     *
     * @return $this
     */
    protected function _initAction()
    {
        $this->_view->loadLayout();
        $this->_setActiveMenu(
            'Magento_CustomerSegment::report_customers_segment'
        )->_addBreadcrumb(
            __('Reports'),
            __('Reports')
        )->_addBreadcrumb(
            __('Customers'),
            __('Customers')
        );
        return $this;
    }

    /**
     * Initialize Customer Segmen Model
     * or adding error to session storage if object was not loaded
     *
     * @param bool $outputMessage
     * @return \Magento\CustomerSegment\Model\Segment|false
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function _initSegment($outputMessage = true)
    {
        $segmentId = $this->getRequest()->getParam('segment_id', 0);
        $segmentIds = $this->getRequest()->getParam('massaction');
        if ($segmentIds) {
            $this->_getAdminSession()->setMassactionIds(
                $segmentIds
            )->setViewMode(
                $this->getRequest()->getParam('view_mode')
            );
        }

        /* @var $segment \Magento\CustomerSegment\Model\Segment */
        $segment = $this->_objectManager->create(\Magento\CustomerSegment\Model\Segment::class);
        if ($segmentId) {
            $segment->load($segmentId);
        }
        if ($this->_getAdminSession()->getMassactionIds()) {
            $segment->setMassactionIds($this->_getAdminSession()->getMassactionIds());
            $segment->setViewMode($this->_getAdminSession()->getViewMode());
        }
        if (!$segment->getId() && !$segment->getMassactionIds()) {
            if ($outputMessage) {
                $this->messageManager->addError(__('Please request the correct customer segment.'));
            }
            return false;
        }
        $this->_coreRegistry->register('current_customer_segment', $segment);

        $websiteIds = $this->getRequest()->getParam('website_ids');
        if ($websiteIds !== null && empty($websiteIds)) {
            $websiteIds = null;
        } elseif ($websiteIds !== null && !empty($websiteIds)) {
            $websiteIds = explode(',', $websiteIds);
        }
        $this->_coreRegistry->register('filter_website_ids', $websiteIds);

        return $segment;
    }

    /**
     * Retrieve admin session model
     *
     * @return \Magento\Backend\Model\Auth\Session
     */
    protected function _getAdminSession()
    {
        if ($this->_adminSession === null) {
            $this->_adminSession = $this->_objectManager->create(\Magento\Backend\Model\Auth\Session::class);
        }
        return $this->_adminSession;
    }

    /**
     * Check the permission to run it
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(
            'Magento_CustomerSegment::customersegment'
        ) && $this->_objectManager->get(
            \Magento\CustomerSegment\Helper\Data::class
        )->isEnabled();
    }
}
