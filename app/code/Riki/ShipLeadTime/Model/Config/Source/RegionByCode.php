<?php
namespace Riki\ShipLeadTime\Model\Config\Source;

class RegionByCode implements \Magento\Framework\Option\ArrayInterface
{
    protected $options;

    /** @var \Magento\Directory\Model\ResourceModel\Region\CollectionFactory  */
    protected $regionCollection;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface  */
    protected $scopeConfig;

    /**
     * RegionByCode constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollection
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollection
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->regionCollection = $regionCollection;
    }

    /**
     * @return mixed
     */
    protected function getOptions()
    {
        if (is_null($this->options)) {

            $currentCountry = $this->scopeConfig->getValue('general/store_information/country_id');

            /** @var \Magento\Directory\Model\ResourceModel\Region\Collection $regionCollection */
            $regionCollection = $this->regionCollection->create();
            $regionCollection->addCountryFilter($currentCountry);

            /** @var \Magento\Directory\Model\Region $region */
            foreach ($regionCollection as $region) {
                $this->options[$region->getCode()] = $region->getName();
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

        foreach ($this->getOptions() as $code =>  $name) {
            $result[] = [
                'value' =>  $code,
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
