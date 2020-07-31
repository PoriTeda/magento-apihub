<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Customer\Block\Adminhtml\Edit\Tab;

use Magento\Customer\Controller\RegistryConstants;
use Magento\Ui\Component\Layout\Tabs\TabInterface;

/**
 * Customer account form block
 */
class Enquiry extends \Magento\Backend\Block\Template implements TabInterface
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Riki\Customer\Model\EnquiryHeaderFactory
     */
    protected $enquiryHeaderFactory;

    /**
     * @var \Riki\Customer\Model\ResourceModel\CategoryEnquiry\CollectionFactory
     */
    protected $enquiryHeaderCategoryFactory;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Riki\Customer\Model\EnquiryHeaderFactory $enquiryHeader,
        \Riki\Customer\Model\ResourceModel\CategoryEnquiry\CollectionFactory $enquiryHeaderCategory,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->enquiryHeaderFactory = $enquiryHeader;
        $this->enquiryHeaderCategoryFactory = $enquiryHeaderCategory;
        $this->timezone = $context->getLocaleDate();
        parent::__construct($context, $data);
    }

    /**
     * @return string|null
     */
    public function getCustomerId()
    {
        return $this->_coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
    }

    /**
     * GetFullEnquiry
     *
     * @return array
     */
    public function getFullEnquiry()
    {
        $customerId = $this->getCustomerId();
        $enquires = $this->enquiryHeaderFactory->create()->getCollection()
            ->join(
                ['c'=>'customer_entity'],
                'c.entity_id = main_table.customer_id',
                'CONCAT( c.lastname," ",c.firstname) as consumer_name'
            )
            ->setOrder('enquiry_updated_datetime','DESC')
            ->addFieldToFilter('customer_id',$customerId)->getData();
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
        $categoryName = '';
        $categoryEnquiry = $this->enquiryHeaderCategoryFactory->create()->getItemById($categoryId);
        if ($categoryEnquiry){
            $categoryData = $categoryEnquiry->getData();
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
        }
        return $categoryName;
    }

    /**
     * GetUrlAddNewEnquiry
     */
    public function getUrlAddNewEnquiry(){

        return $this->_urlBuilder->getUrl(
            'customer/enquiryheader/new',
            ['_secure' => true, 'customerid' => $this->getCustomerId()]
        );
    }


    /**
     *GetItemOrderNumber
     *
     * @param array $item
     */
    public function getItemOrderNumber(array $item){
        if ($item['order_id']){
            return $item['order_id'];
        }
        else{
            return __('None');
        }
    }

    /**
     * GetItemText
     *
     * @param $item
     *
     * @return string
     */
    public function getItemText($item){
        return $this->escapeHtml($item['enquiry_text']);
    }

    /**
     * GetItemTitle
     *
     * @param $item
     *
     * @return string
     */
    public function getItemTitle($item){
        return $this->escapeHtml($item['enquiry_title']);
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Customer Enquiry');
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Customer Enquiry');
    }

    /**
     * @return bool
     */
    public function canShowTab()
    {
        if ($this->getCustomerId()) {
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isHidden()
    {
        if ($this->getCustomerId()) {
            return false;
        }
        return true;
    }

    /**
     * Tab class getter
     *
     * @return string
     */
    public function getTabClass()
    {
        return '';
    }

    /**
     * Return URL link to Tab content
     *
     * @return string
     */
    public function getTabUrl()
    {
        return '';
    }

    /**
     * Tab should be loaded trough Ajax call
     *
     * @return bool
     */
    public function isAjaxLoaded()
    {
        return false;
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
