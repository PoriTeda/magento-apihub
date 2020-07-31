<?php

namespace Riki\AdvancedInventory\Model\Config\Source\ReAssignation;

class Warehouse implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Riki\PointOfSale\Api\PointOfSaleRepositoryInterface
     */
    protected $pointOfSalesRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * Warehouse constructor.
     * @param \Riki\PointOfSale\Api\PointOfSaleRepositoryInterface $pointOfSaleRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        \Riki\PointOfSale\Api\PointOfSaleRepositoryInterface $pointOfSaleRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
    )
    {
        $this->pointOfSalesRepository = $pointOfSaleRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        $warehouses = $this->pointOfSalesRepository->getList($this->searchCriteriaBuilder->create())->getItems();

        $result = [];

        foreach ($warehouses as $warehouse) {
            $result[$warehouse->getStoreCode()] = $warehouse->getStoreCode();
        }

        return $result;
    }

    /**
     * @param $value
     * @return \Magento\Framework\Phrase|string
     */
    public function getLabel($value)
    {
        $options = $this->getOptions();

        if (isset($options[$value]))
            return $options[$value];

        return __('Unknown');
    }

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        $result = [];

        foreach ($this->getOptions() as $value  =>  $label) {
            $result[] = [
                'value' =>  $value,
                'label' =>  $label
            ];
        }

        return $result;
    }
}
