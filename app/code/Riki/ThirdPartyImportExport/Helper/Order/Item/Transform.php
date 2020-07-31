<?php
namespace Riki\ThirdPartyImportExport\Helper\Order\Item;

use Magento\Framework\Exception\LocalizedException;

class Transform extends \Riki\ThirdPartyImportExport\Helper\Transform
{
    /**
     * @var array
     */
    protected $_loadedData;
    /**
     * @var \Magento\Sales\Model\Order\Item
     */
    protected $_subject;

    public function setSubject($subject)
    {
        if (!$subject instanceof \Magento\Sales\Model\Order\Item) {
            throw new LocalizedException(__('$subject must instance of \Magento\Sales\Model\Order\Item'));
        }

        $this->_loadedData = null;

        return parent::setSubject($subject);
    }
}