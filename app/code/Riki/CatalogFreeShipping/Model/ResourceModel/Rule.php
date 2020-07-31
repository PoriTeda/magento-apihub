<?php
/**
 * PHP version 7
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 *
 * @category  RIKI
 * @package   Riki_CatalogFreeShipping
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\CatalogFreeShipping\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\AbstractModel;

/**
 * Class Rule
 *
 * @category RIKI
 * @package  Riki_CatalogFreeShipping
 * @author   Nestle.co.jp <support@nestle.co.jp>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/rikibusiness/riki-ecommerce
 */
class Rule extends AbstractDb
{
    protected $date;


    /**
     * Constructor
     *
     * @param \Magento\Framework\Stdlib\DateTime\DateTime       $date           date
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context        context
     * @param null                                              $connectionName connection name
     *
     * @return self
     */
    public function __construct(
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $connectionName = null
    ) {
        $this->date = $date;

        parent::__construct(
            $context,
            $connectionName
        );

    }//end __construct()


    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('riki_catalog_free_shipping', 'id');

    }//end _construct()


    /**
     * Add customer group ids and website ids to rule data after load
     *
     * @param AbstractModel $object Abstract Model
     *
     * @return $this
     */
    protected function _afterLoad(AbstractModel $object)
    {
        $this->loadCustomerGroupIds($object);
        $this->loadWebsiteIds($object);
        $this->loadMemberships($object);
        $object->setData('from_date', $this->formatRuleDateTime($object->getFromDate()));
        $object->setData('from_hour', (int) $this->formatRuleHour($object->getFromDate()));
        $object->setData('to_date', $this->formatRuleDateTime($object->getToDate()));
        $object->setData('to_hour', (int) $this->formatRuleHour($object->getToDate()));

        parent::_afterLoad($object);
        return $this;

    }//end _afterLoad()


    /**
     * Before save
     *
     * @param AbstractModel $object Abstract Model
     *
     * @return $this
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _beforeSave(AbstractModel $object)
    {
        $object->setWebsiteIds(implode(',', $object->getWebsiteIds()));
        $object->setCustomerGroupIds(implode(',', $object->getCustomerGroupIds()));
        $object->setMemberships(implode(',', $object->getMemberships()));
        if($object->getFromDate())
            $object->setFromDate($this->formatRuleDateTime($object->getFromDate().' '.$object->getFromHour().':00:00'));
        if($object->getToDate())
            $object->setToDate($this->formatRuleDateTime($object->getToDate().' '.$object->getToHour().':00:00'));

        if($object->getToDate() < $object->getFromDate())
            throw new \Magento\Framework\Exception\LocalizedException(__('The To Date/Hour value must be greater the From/Hour value'));
        return $this;

    }//end _beforeSave()


    /**
     * Set customer group ids as array to object data
     *
     * @param AbstractModel $object Abstract Model
     *
     * @return void
     */
    public function loadCustomerGroupIds(AbstractModel $object)
    {
        $object->setData('customer_group_ids', explode(',', $object->getCustomerGroupIds()));

    }//end loadCustomerGroupIds()


    /**
     * Set websites ids as array to object data
     *
     * @param AbstractModel $object Abstract Model
     *
     * @return void
     */
    public function loadWebsiteIds(AbstractModel $object)
    {
        $object->setData('website_ids', explode(',', $object->getWebsiteIds()));

    }//end loadWebsiteIds()


    /**
     * Set customer membership ids as array to object data
     *
     * @param AbstractModel $object Abstract Model
     *
     * @return void
     */
    public function loadMemberships(AbstractModel $object)
    {
        $object->setData('memberships', explode(',', $object->getMemberships()));

    }//end loadMemberships()


    /**
     * Format date time string
     *
     * @param string $dateTime date time
     *
     * @return null|string
     */
    protected function formatRuleDateTime($dateTime)
    {
        if(!empty($dateTime))
            return $this->date->date('Y-m-d H:00:00', $dateTime);
        return null;

    }//end formatRuleDateTime()


    /**
     * Get hour from datetime
     *
     * @param string $dateTime date time
     *
     * @return null|string
     */
    protected function formatRuleHour($dateTime)
    {
        if(!empty($dateTime))
            return $this->date->date('H', $dateTime);
        return null;

    }//end formatRuleHour()


}//end class
