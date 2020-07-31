<?php
namespace Riki\Rma\Model\Config\Source\Rma;

class Warehouse extends \Riki\Framework\Model\Source\AbstractOption
{
    /**
     * @var \Riki\Rma\Helper\Data
     */
    protected $dataHelper;

    /**
     * Warehouse constructor.
     * @param \Riki\Rma\Helper\Data $dataHelper
     */
    public function __construct(
        \Riki\Rma\Helper\Data $dataHelper
    )
    {
        $this->dataHelper = $dataHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function prepare()
    {
        $options = [];
        /** @var \Wyomind\PointOfSale\Model\PointOfSale $warehouse */
        foreach ($this->dataHelper->getWarehouses() as $warehouse) {
            $options[$warehouse->getId()] = $warehouse->getName();
        }

        return $options;
    }

}