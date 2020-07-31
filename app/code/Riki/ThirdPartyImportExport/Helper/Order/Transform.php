<?php
namespace Riki\ThirdPartyImportExport\Helper\Order;

use Magento\Framework\Exception\LocalizedException;

class Transform extends \Riki\ThirdPartyImportExport\Helper\Transform
{
    /**
     * @var array
     */
    protected $_loadedData;
    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $_subject;

    public function setSubject($subject)
    {
        if (!$subject instanceof \Magento\Sales\Model\Order) {
            throw new LocalizedException(__('$subject must instance of \Magento\Sales\Model\Order'));
        }

        $this->_loadedData = null;

        return parent::setSubject($subject);
    }
}
