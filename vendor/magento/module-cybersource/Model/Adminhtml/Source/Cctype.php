<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Model\Adminhtml\Source;

/**
 * Authorize.net Payment CC Types Source Model
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Cctype extends \Magento\Payment\Model\Source\Cctype
{
    /**
     * @return string[]
     */
    public function getAllowedTypes()
    {
        return ['VI', 'MC', 'AE', 'DI', 'DN', 'JCB', 'MD', 'MI'];
    }
}
