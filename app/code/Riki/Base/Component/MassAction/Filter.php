<?php
namespace Riki\Base\Component\MassAction;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProviderInterface;

class Filter extends \Magento\Ui\Component\MassAction\Filter
{
    /**
     * @var DataProviderInterface
     */
    private $dataProvider;

    /**
     * Adds filters to collection using DataProvider filter results
     *
     * @param AbstractDb $collection
     * @return AbstractDb
     * @throws LocalizedException
     */
    public function getCollection(AbstractDb $collection)
    {
        $selected = $this->request->getParam(static::SELECTED_PARAM);
        $excluded = $this->request->getParam(static::EXCLUDED_PARAM);

        $isExcludedIdsValid = (is_array($excluded) && !empty($excluded));
        $isSelectedIdsValid = (is_array($selected) && !empty($selected));

        if ('false' !== $excluded) {
            if (!$isExcludedIdsValid && !$isSelectedIdsValid) {
                throw new LocalizedException(__('Please select item(s).'));
            }
        }
        $idsArray = $this->getFilterIds();
        if (!empty($idsArray)) {
            $collection->addFieldToFilter(
                $collection->getIdFieldName(),
                ['in' => $idsArray]
            );
        }
        return $collection;
    }

    /**
     * Apply selection by Excluded Included to Search Result
     *
     * @throws LocalizedException
     * @return void
     */
    public function applySelectionOnTargetProvider()
    {
        $selected = $this->request->getParam(static::SELECTED_PARAM);
        $excluded = $this->request->getParam(static::EXCLUDED_PARAM);
        if ('false' === $excluded) {
            return;
        }
        $dataProvider = $this->getDataProvider();
        try {
            if (is_array($excluded) && !empty($excluded)) {
                $this->filterBuilder->setConditionType('nin')
                    ->setField($dataProvider->getPrimaryFieldName())
                    ->setValue($excluded);
                $dataProvider->addFilter($this->filterBuilder->create());
            } elseif (is_array($selected) && !empty($selected)) {
                $this->filterBuilder->setConditionType('in')
                    ->setField($dataProvider->getPrimaryFieldName())
                    ->setValue($selected);
                $dataProvider->addFilter($this->filterBuilder->create());
            }
        } catch (\Exception $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
    }

    /**
     * Get data provider
     *
     * @return DataProviderInterface
     */
    private function getDataProvider()
    {
        if (!$this->dataProvider) {
            $component = $this->getComponent();
            $this->prepareComponent($component);
            $this->dataProvider = $component->getContext()->getDataProvider();
        }
        return $this->dataProvider;
    }

    /**
     * Get filter ids as array
     *
     * @return int[]
     */
    private function getFilterIds()
    {
        $this->applySelectionOnTargetProvider();
        if ($this->getDataProvider()->getSearchResult()) {
            return $this->getDataProvider()->getSearchResult()->getAllIds();
        }
        return [];
    }
}
