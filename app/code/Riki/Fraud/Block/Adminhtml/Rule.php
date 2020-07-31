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
 * @version   1.0.3
 * @copyright Copyright (C) 2016 Mirasvit (https://mirasvit.com/)
 */


namespace Riki\Fraud\Block\Adminhtml;


class Rule extends \Mirasvit\FraudCheck\Block\Adminhtml\Rule
{
    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        parent::_construct();
        $this->addButton(
            'import',
            [
                'label' => __('Import Data'),
                'onclick' => 'setLocation(\'' . $this->getImportUrl() . '\')',
                'class' => 'add primary'
            ]
        );
    }
    public function getImportUrl(){
        return $this->getUrl('riki_fraud/import');
    }
}
