<?php

/*
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;

/**
 * Render column block in the order grid
 */
class AssignedTo extends Column
{

    protected $_blockColumn = null;

    /**
     *
     * @param \Magento\Framework\View\Element\UiComponent\ContextInterface $context
     * @param \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory
     * @param \Magento\Framework\UrlInterface $urlInterface
     * @param \Magento\Sales\Model\Order $order
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Wyomind\AdvancedInventory\Block\Adminhtml\Assignation\Column $blockColumn,
        array $components = [],
        array $data = []
    ) {


        $this->_blockColumn = $blockColumn;

        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     *
     * @param array $dataSource
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $item[$this->getData('name')] = $this->_blockColumn->getAssignation($item);
            }
        }

        return $dataSource;
    }
}
