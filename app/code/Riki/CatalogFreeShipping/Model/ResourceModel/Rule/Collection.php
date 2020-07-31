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

namespace Riki\CatalogFreeShipping\Model\ResourceModel\Rule;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 *
 * @category RIKI
 * @package  Riki_CatalogFreeShipping
 * @author   Nestle.co.jp <support@nestle.co.jp>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/rikibusiness/riki-ecommerce
 */
class Collection extends AbstractCollection
{
    protected $timeZone;


    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Riki\CatalogFreeShipping\Model\Rule',
            'Riki\CatalogFreeShipping\Model\ResourceModel\Rule'
        );

    }//end _construct()


    /**
     * Constructor
     *
     * @param \Magento\Framework\Data\Collection\EntityFactoryInterface    $entityFactory EntityFactoryInterface
     * @param \Psr\Log\LoggerInterface                                     $logger        LoggerInterface
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy FetchStrategyInterface
     * @param \Magento\Framework\Event\ManagerInterface                    $eventManager  ManagerInterface
     * @param \Magento\Framework\DB\Adapter\AdapterInterface|null          $connection    AdapterInterface
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb|null    $resource      * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb|null $resource
     *
     * @return self
     */
    public function __construct(
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {

        $this->timeZone = $timezone;

        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $connection,
            $resource
        );

    }//end __construct()


    /**
     * Add website ids to rules data
     *
     * @return $this
     */
    protected function _afterLoad()
    {
        parent::_afterLoad();
        if (count($this->_items) > 0) {
            /*
                * @var \Riki\CatalogFreeShipping\Model\Rule $item
            */

            foreach ($this->_items as $item) {
                $item->afterLoad();
            }
        }

        return $this;

    }//end _afterLoad()


    /**
     * Provide support for website id filter
     *
     * @param string            $field     field name
     * @param null|string|array $condition condition
     *
     * @return self
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if ($field === 'website_ids'
            || $field === 'customer_group_ids'
            || $field === 'memberships'
        ) {
            if (is_array($condition)) {
                if (isset($condition['eq'])) {
                    $condition['finset'] = $condition['eq'];
                    unset($condition['eq']);
                }
            } else if (is_numeric($condition)) {
                $condition = ['finset' => $condition];
            }
        }

        parent::addFieldToFilter($field, $condition);
        return $this;

    }//end addFieldToFilter()


    /**
     * Add website to filter
     *
     * @param int $websiteId website id
     *
     * @return $this
     */
    public function addWebsiteToFilter($websiteId = 0)
    {
        $this->addFieldToFilter('website_ids', ['eq' => $websiteId]);
        return $this;

    }//end addWebsiteToFilter()


    /**
     * Add customer group to filter
     *
     * @param int $customerGroupId customer group id
     *
     * @return $this
     */
    public function addCustomerGroupToFilter($customerGroupId = 0)
    {
        $this->addFieldToFilter('customer_group_ids', ['eq' => $customerGroupId]);
        return $this;

    }//end addCustomerGroupToFilter()


    /**
     * Add membership id to filter
     *
     * @param int $membership membership id
     *
     * @return $this
     */
    public function addMembershipToFilter($membership = 0)
    {
        if(is_array($membership) && count($membership)){

            $conditionList = [];

            foreach($membership as $mbs){
                if(empty($mbs))
                    $mbs = 0;

                $conditionList[] = 'FIND_IN_SET('. $mbs .', memberships)';
            }

            $this->getSelect()
                ->where(implode(' OR ', $conditionList));

        }else{
            if(empty($membership))
                $membership = 0;

            $this->addFieldToFilter('memberships', ['eq' => $membership]);
        }

        return $this;

    }//end addMembershipToFilter()


    /**
     * Add date fields to filter
     *
     * @return $this
     */
    public function addActiveFilter()
    {
        $now = $this->timeZone->date()->format('Y-m-d H:i:s');

        $this->getSelect()
            ->where(
                'from_date is null or from_date <= ?',
                $now
            )->where(
                'to_date is null or to_date >= ?',
                $now
            );

        return $this;

    }//end addActiveFilter()


    /**
     * Add product attribute to filter
     *
     * @param string $field attribute name
     * @param mixed  $value value
     *
     * @return $this
     */
    public function addProductAttributeToFilter($field, $value)
    {
        if (is_array($value)) {
            $this->getSelect()
                ->where(
                    "{$field} is null or {$field} = '' or {$field} IN (?)",
                    $value
                );
        } else {
            $this->getSelect()
                ->where(
                    "{$field} is null or {$field} = '' or {$field} = ?",
                    $value
                );
        }

        return $this;

    }//end addProductAttributeToFilter()


}//end class
