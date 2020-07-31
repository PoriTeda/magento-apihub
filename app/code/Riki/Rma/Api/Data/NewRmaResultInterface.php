<?php
namespace Riki\Rma\Api\Data;

interface NewRmaResultInterface
{

    /**
     * Get increment ID
     *
     * @return string
     */
    public function getReturnId();

    /**
     * Set increment ID
     *
     * @param string $returnId
     * @return \Magento\Framework\DataObject
     */
    public function setReturnId($returnId);
}