<?php
namespace Riki\Rma\Model;

class NewRmaResult extends \Magento\Framework\DataObject implements \Riki\Rma\Api\Data\NewRmaResultInterface
{

    /**
     * Get increment ID
     *
     * @return string
     */
    public function getReturnId()
    {
        return $this->getData('increment_id');
    }

    /**
     * @param string $returnId
     * @return $this
     */
    public function setReturnId($returnId)
    {
        return $this->setData('increment_id', $returnId);
    }
}
