<?php
namespace Riki\ThirdPartyImportExport\Block\Adminhtml\Order\View;

class Items extends Generic
{
    /**
     * Get multi shipping
     *
     * @return \Riki\ThirdPartyImportExport\Model\ResourceModel\Shipping\Collection
     */
    public function getShippings()
    {
        return $this->getOrder()->getShippings();
    }
}
