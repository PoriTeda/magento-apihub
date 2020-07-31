<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Sales\Block\Adminhtml\Order\View\Tab;

/**
 * Order enquiry tab
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Enquiry extends \Magento\Backend\Block\Template implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * Template
     *
     * @var string
     */
    protected $_template = 'order/view/tab/enquiry.phtml';

    /**
     * @var \Riki\Customer\Model\EnquiryHeaderFactory
     */
    protected $enquiryHeaderFactory;

    /**
     * @var \Riki\Customer\Model\ResourceModel\CategoryEnquiry\CollectionFactory
     */
    protected $enquiryHeaderCategoryFactory;
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Sales\Helper\Admin
     */
    private $adminHelper;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * Enquiry constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Sales\Helper\Admin $adminHelper
     * @param \Riki\Customer\Model\EnquiryHeaderFactory $enquiryHeader
     * @param \Riki\Customer\Model\ResourceModel\CategoryEnquiry\CollectionFactory $enquiryHeaderCategory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        \Riki\Customer\Model\EnquiryHeaderFactory $enquiryHeader,
        \Riki\Customer\Model\ResourceModel\CategoryEnquiry\CollectionFactory $enquiryHeaderCategory,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->enquiryHeaderFactory = $enquiryHeader;
        $this->enquiryHeaderCategoryFactory = $enquiryHeaderCategory;
        parent::__construct($context, $data);
        $this->adminHelper = $adminHelper;
        $this->timezone = $context->getLocaleDate();
    }

    /**
     * Retrieve order model instance
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->_coreRegistry->registry('current_order');
    }

    public function getFullEnquiry()
    {
        $order = $this->getOrder();
        $orderIncrementId = $order->getIncrementId();
        $enquires = $this->enquiryHeaderFactory->create()->getCollection()
            ->join(
                ['c'=>'customer_entity'],
                'c.entity_id = main_table.customer_id',
                'CONCAT( c.lastname," ",c.firstname) as consumer_name'
            )
            ->setOrder('enquiry_updated_datetime','DESC')
            ->addFieldToFilter('order_id',$orderIncrementId)->getData();
        return $enquires;
    }

    /**
     * Status enquiry date/datetime getter
     *
     * @param array $item
     * @param string $dateType
     * @param int $format
     * @return string
     */
    public function getItemUpdated(array $item, $dateType = 'date', $format = \IntlDateFormatter::MEDIUM)
    {
        if (!isset($item['enquiry_updated_datetime'])) {
            return '';
        }
        $date = $item['enquiry_updated_datetime'] instanceof \DateTimeInterface
            ? $item['enquiry_updated_datetime']
            : $this->timezone->date(new \DateTime($item['enquiry_updated_datetime']));
        if ('date' === $dateType) {
            return $this->_localeDate->formatDateTime($date, $format, $format);
        }

        return $this->_localeDate->formatDateTime($date, \IntlDateFormatter::NONE, $format);
    }

    /**
     * Get Item Category
     *
     * @param array $item
     *
     * @return string
     */
    public function getItemCategory(array $item){
        if (!isset($item['enquiry_category_id'])) {
            return '';
        }
        $categoryId = $item['enquiry_category_id'];

        $categoryData = $this->enquiryHeaderCategoryFactory->create()->getItemById($categoryId)->getData();
        $categoryName = '';
        if(isset($categoryData['entity_id'])){
            if(isset($categoryData['code']) && isset($categoryData['code']) != ''){
                $categoryName = $categoryData['code'];
            }

            if(isset($categoryData['name']) && isset($categoryData['name']) != ''){
                if($categoryName != ''){
                    $categoryName.= ' - '.$categoryData['name'];
                }
                else{
                    $categoryName = $categoryData['name'];
                }
            }
        }
        return $categoryName;
    }

    /**
     * GetUrlAddNewEnquiry
     */
    public function getUrlAddNewEnquiry(){
        $order = $this->getOrder();
        $orderIncrementId = $order->getIncrementId();

        return $this->_urlBuilder->getUrl(
            'customer/enquiryheader/new',
            ['_secure' => true, 'orderid' => $orderIncrementId]
        );
    }

    /**
     * @param $item
     *
     * @return string
     */
    public function getItemText($item){
        return $this->escapeHtml($item['enquiry_text']);
    }

    /**
     * @param $item
     *
     * @return string
     */
    public function getItemTitle($item){
        return $this->escapeHtml($item['enquiry_title']);
    }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Customer Enquiry');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Customer Enquiry');
    }

    /**
     * Get Tab Class
     *
     * @return string
     */
    public function getTabClass()
    {
        return 'ajax only';
    }

    /**
     * Get Class
     *
     * @return string
     */
    public function getClass()
    {
        return $this->getTabClass();
    }

    /**
     * Get Tab Url
     *
     * @return string
     */
    public function getTabUrl()
    {
        return $this->getUrl('riki_sales/*/commentsEnquiry', ['_current' => true]);
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }


    /**
     * Get order admin date
     *
     * @param int $createdAt
     * @return \DateTime
     */
    public function getOrderAdminDate($createdAt)
    {
        return $this->_localeDate->date(new \DateTime($createdAt));
    }

    /**
     * Convert data json
     *
     * @param array $data
     * @return string
     */
    public function convertDataToJson($data = []) {
        return \Zend_Json::encode($data);
    }

}
