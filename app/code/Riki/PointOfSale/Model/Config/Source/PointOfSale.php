<?php
namespace Riki\PointOfSale\Model\Config\Source;

class PointOfSale implements \Magento\Framework\Option\ArrayInterface
{
    protected $options;

    /**
     * @var \Riki\PointOfSale\Api\PointOfSaleRepositoryInterface
     */
    protected $pointOfSaleRepositoryInterface;

    /**
     * Search criteria builder
     *
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * PointOfSale constructor.
     * @param \Riki\PointOfSale\Api\PointOfSaleRepositoryInterface $pointOfSaleRepositoryInterface
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        \Riki\PointOfSale\Api\PointOfSaleRepositoryInterface $pointOfSaleRepositoryInterface,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
    )
    {
        $this->pointOfSaleRepositoryInterface = $pointOfSaleRepositoryInterface;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @return mixed
     */
    protected function getOptions()
    {
        if (is_null($this->options)) {

            $searchCriteria = $this->searchCriteriaBuilder->create();

            $result = $this->pointOfSaleRepositoryInterface->getList($searchCriteria);

            $places = $result->getItems();

            foreach ($places as $place) {
                $this->options[$place->getId()] = $place->getName();
            }
        }

        return $this->options;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $result = [];

        foreach ($this->getOptions() as $id =>  $name) {
            $result[] = [
                'value' =>  $id,
                'label' =>  $name
            ];
        }

        return $result;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return $this->getOptions();
    }
}
