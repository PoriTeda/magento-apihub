<?php

namespace Riki\Catalog\Plugin;

class FilterInGridMultiSelectCustomer
{
    public function aroundCanBeFilterableInGrid(\Magento\Customer\Model\Attribute $subject, callable $proceed)
    {
       if($subject->getName() == 'membership'){
           return $subject->getData('is_filterable_in_grid')
            && in_array($subject->getFrontendInput(), ['text', 'date', 'select', 'boolean', 'multiselect']);
       }
       else{
           return $proceed();
       }
    }
}
