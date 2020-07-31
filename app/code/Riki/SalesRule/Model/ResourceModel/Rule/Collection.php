<?php
namespace Riki\SalesRule\Model\ResourceModel\Rule;

use Magento\Quote\Model\Quote\Address;

class Collection extends \Magento\SalesRule\Model\ResourceModel\Rule\Collection
{

    /**
     * Initialize select object
     *
     * @return $this
     */
    public function _initSelect()
    {
        parent::_initSelect();
        $this->getSelect()->joinLeft(
            ['amrule' => $this->getTable('amasty_ampromo_rule')],
            'main_table.rule_id = amrule.salesrule_id',
            ['sku', 'type', 'att_visible_cart', 'att_visible_user_account']
        );

        return $this;
    }


    /**
     * Filter collection by website(s), customer group(s) and date.
     * Filter collection to only active rules.
     * Sorting is not involved
     *
     * @param int $websiteId
     * @param int $customerGroupId
     * @param string|null $now
     * @use $this->addWebsiteFilter()
     *
     * @return $this
     */
    public function addWebsiteGroupDateFilter($websiteId, $customerGroupId, $now = null)
    {
        if (!$this->getFlag('website_group_date_filter')) {
            if ($now === null) {
                $now = $this->_date->date()->format('Y-m-d H:i:s');
            }

            $this->addWebsiteFilter($websiteId);

            $entityInfo = $this->_getAssociatedEntityInfo('customer_group');
            $connection = $this->getConnection();
            $this->getSelect()->joinInner(
                ['customer_group_ids' => $this->getTable($entityInfo['associations_table'])],
                $connection->quoteInto(
                    'main_table.' .
                    $entityInfo['rule_id_field'] .
                    ' = customer_group_ids.' .
                    $entityInfo['rule_id_field'] .
                    ' AND customer_group_ids.' .
                    $entityInfo['entity_id_field'] .
                    ' = ?',
                    (int)$customerGroupId
                ),
                []
            )->where(
                'from_time is null or from_time <= ?',
                $now
            )->where(
                'to_time is null or to_time >= ?',
                $now
            );
            $this->addIsActiveFilter();

            $this->setFlag('website_group_date_filter', true);
        }

        return $this;
    }

    /**
     * Filter collection by specified website, customer group, coupon code, date.
     * Filter collection to use only active rules.
     * Involved sorting by sort_order column.
     *
     * @param int $websiteId
     * @param int $customerGroupId
     * @param string $couponCode
     * @param string|null $now
     * @use $this->addWebsiteGroupDateFilter()
     * @return $this
     */
    public function setValidationFilter(
        $websiteId,
        $customerGroupId,
        $couponCode = '',
        $now = null,
        Address $address = null
    ) {
        if (!$this->getFlag('validation_filter')) {
            /* We need to overwrite joinLeft if coupon is applied */
            $this->getSelect()->reset();

            $this->getSelect()->from(['main_table' => $this->getMainTable()]);

            $this->addWebsiteGroupDateFilter($websiteId, $customerGroupId, $now);
            $select = $this->getSelect();

            $connection = $this->getConnection();

            $select->joinLeft(
                ['rule_coupons' => $this->getTable('salesrule_coupon')],
                $connection->quoteInto(
                    'main_table.rule_id = rule_coupons.rule_id AND main_table.coupon_type != ?',
                    \Magento\SalesRule\Model\Rule::COUPON_TYPE_NO_COUPON
                ),
                ['code']
            );

            $orWhereConditions = $connection->quoteInto(
                'main_table.coupon_type = ? ',
                \Magento\SalesRule\Model\Rule::COUPON_TYPE_NO_COUPON
            );
            $includeNoCoupon = [
                $connection->quoteInto(
                    '(main_table.coupon_type = ? AND rule_coupons.type = 0)',
                    \Magento\SalesRule\Model\Rule::COUPON_TYPE_AUTO
                ),
                $connection->quoteInto(
                    '(main_table.coupon_type = ? AND main_table.use_auto_generation = 1 AND rule_coupons.type = 1)',
                    \Magento\SalesRule\Model\Rule::COUPON_TYPE_SPECIFIC
                ),
                $connection->quoteInto(
                    '(main_table.coupon_type = ? AND main_table.use_auto_generation = 0 AND rule_coupons.type = 0)',
                    \Magento\SalesRule\Model\Rule::COUPON_TYPE_SPECIFIC
                )
            ];
            $andWhereConditions = [
                $connection->quoteInto(
                    'rule_coupons.code = ?',
                    $couponCode
                ),
                $connection->quoteInto(
                    '(rule_coupons.expiration_date IS NULL OR rule_coupons.expiration_date >= ?)',
                    $this->_date->date()->format('Y-m-d')
                ),
            ];

            $includeNoCoupon = implode(' OR ', $includeNoCoupon);
            $andWhereCondition = implode(' AND ', $andWhereConditions);
            $select->where('(' . $orWhereConditions . ' OR ' . '((' . $includeNoCoupon . ')' . ' AND ' . $andWhereCondition . '))');
            $this->setOrder('sort_order', self::SORT_ORDER_ASC);
            $this->setFlag('validation_filter', true);
        }

        return $this;
    }

    /**
     * @return DateApplier
     * @deprecated 100.1.0
     */
    private function getDateApplier()
    {
        if (null === $this->dateApplier) {
            $this->dateApplier = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\SalesRule\Model\ResourceModel\Rule\DateApplier::class);
        }

        return $this->dateApplier;
    }
}