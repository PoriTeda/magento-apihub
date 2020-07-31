<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_Promo
 */

namespace Amasty\Promo\Model;


class Rule extends \Magento\Framework\Model\AbstractModel
{
    const SAME_PRODUCT  = 'ampromo_product';
    const PER_PRODUCT   = 'ampromo_items';
    const WHOLE_CART    = 'ampromo_cart';
    const SPENT         = 'ampromo_spent';

    const RULE_TYPE_ALL = 0;
    const RULE_TYPE_ONE = 1;

    /**
     * Set resource model and Id field name
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('Amasty\Promo\Model\Resource\Rule');
        $this->setIdFieldName('entity_id');
    }

    public function loadBySalesrule(\Magento\SalesRule\Model\Rule $rule)
    {
        if ($ampromoRule = $rule->getData('ampromo_rule'))
            return $ampromoRule;

        $ampromoRule = \Magento\Framework\App\ObjectManager::getInstance()
            ->create('Amasty\Promo\Model\Rule');

        $ampromoRule->load($rule->getId(), 'salesrule_id');

        $rule->setData('ampromo_rule', $ampromoRule);

        return $ampromoRule;
    }
}
