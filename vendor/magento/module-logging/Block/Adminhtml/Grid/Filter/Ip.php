<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Ip-address grid filter
 */
namespace Magento\Logging\Block\Adminhtml\Grid\Filter;

class Ip extends \Magento\Backend\Block\Widget\Grid\Column\Filter\Text
{
    /**
     * Construct
     *
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Logging\Model\ResourceModel\Helper $resourceHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Logging\Model\ResourceModel\Helper $resourceHelper,
        array $data = []
    ) {
        parent::__construct($context, $resourceHelper, $data);
    }

    /**
     * Collection condition filter getter
     *
     * @return array
     */
    public function getCondition()
    {
        $value = $this->getValue();
        if (preg_match('/^(\d+\.){3}\d+$/', $value)) {
            return ip2long($value);
        }

        $likeExpression = $this->_resourceHelper->addLikeEscape($value, ['position' => 'any']);
        return ['ntoa' => $likeExpression];
    }
}
