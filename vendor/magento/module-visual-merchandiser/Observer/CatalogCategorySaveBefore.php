<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VisualMerchandiser\Observer;

use Magento\Framework\Event\Observer;

class CatalogCategorySaveBefore implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\VisualMerchandiser\Model\Category\Builder
     */
    protected $categoryBuilder;

    /**
     * @var \Magento\VisualMerchandiser\Model\Rules
     */
    protected $_rules;

    /**
     * Constructor
     *
     * @param \Magento\VisualMerchandiser\Model\Category\Builder $categoryBuilder
     * @param \Magento\VisualMerchandiser\Model\Rules $rules
     */
    public function __construct(
        \Magento\VisualMerchandiser\Model\Category\Builder $categoryBuilder,
        \Magento\VisualMerchandiser\Model\Rules $rules
    ) {
        $this->categoryBuilder = $categoryBuilder;
        $this->_rules = $rules;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /* @var \Magento\Catalog\Model\Category $category */
        $category = $observer->getEvent()->getDataObject();

        // Disable smart category rule after application
        $rule = $this->_rules->loadByCategory($category);
        if ($rule->getId() && $rule->getIsActive()) {
            $this->categoryBuilder->rebuildCategory($category);
            $rule->setData([
                'rule_id' => $rule->getId(),
                'category_id' => $category->getId(),
                'is_active' => $rule->getIsActive()
            ]);
            $rule->save();
        }
    }
}
