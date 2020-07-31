<?php

/**
 * Copyright Â© 2015 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Ui\Component\DataProvider;

/**
 * Class DataProvider
 */
class DataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{

    protected $_helperPermissions = null;

    public function prepareUpdateUrl()
    {

        $object = \Magento\Framework\App\ObjectManager::getInstance();
        if ($this->_helperPermissions == null) {
            $this->_helperPermissions = $object->create(\Wyomind\AdvancedInventory\Helper\Permissions::class);
        }
        parent::prepareUpdateUrl();

        $session = $object->get('Magento\Backend\Model\Session');



        if ($session->getData('selected_ids')) {
            $value = new \Zend_Db_Expr($session->getData('selected_ids'));
            $this->addFilter(
                $this->filterBuilder->setField('entity_id')->setValue($value)->setConditionType('in')->create()
            );
        }


        if ($this->_helperPermissions->hasAllPermissions()) {
            return;
        }

        foreach ($this->_helperPermissions->getUserPermissions() as $p) {
            $filters[] = "FIND_IN_SET($p,`assigned_to`)";
        }

        if (!count($this->_helperPermissions->getUserPermissions())) {
            $filters[] = "FIND_IN_SET('0',`assigned_to`)";
        }

        $field = new \Zend_Db_Expr("1");
        $value = new \Zend_Db_Expr(implode(" OR ", $filters));

        $this->addFilter(
            $this->filterBuilder->setField($field)->setValue($value)->setConditionType('eq')->create()
        );
    }

    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        if ($filter->getField() == "assigned_to") {
            $filter = $this->filterBuilder->setField($filter->getField())->setValue($filter->getValue())->setConditionType('finset')->create();
        }

        $this->searchCriteriaBuilder->addFilter($filter);
    }
}
