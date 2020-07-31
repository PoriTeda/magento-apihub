<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-fraud-check
 * @version   1.0.38
 * @copyright Copyright (C) 2019 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\FraudCheck\Block\Adminhtml;

use Magento\Framework\DataObject;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Mirasvit\Core\Block\Adminhtml\AbstractMenu;

class Menu extends AbstractMenu
{
    /**
     * @param Context $context
     */
    public function __construct(
        Context $context
    ) {
        $this->visibleAt(['fraud_check']);

        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    protected function buildMenu()
    {
        $this->addItem([
            'resource' => 'Mirasvit_FraudCheck::fraud_check_score',
            'title'    => __('Fraud Risk Score'),
            'url'      => $this->urlBuilder->getUrl('fraud_check/score/view'),
        ])->addItem([
            'resource' => 'Mirasvit_FraudCheck::fraud_check_rule',
            'title'    => __('Custom Rules'),
            'url'      => $this->urlBuilder->getUrl('fraud_check/rule'),
        ]);

        $this->addSeparator();

        $this->addItem([
            'resource' => 'Mirasvit_FraudCheck::fraud_check_score',
            'title'    => __('User Manual'),
            'url'      => 'http://docs.mirasvit.com/module-fraud-check/current',
        ])->addItem([
            'resource' => 'Mirasvit_FraudCheck::fraud_check_score',
            'title'    => __('Get Support'),
            'url'      => 'https://mirasvit.com/support/',
        ]);

        return $this;
    }
}