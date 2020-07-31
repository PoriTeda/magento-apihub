<?php

namespace Riki\CatalogRule\Model\Indexer;

/**
 * Reindex product prices according rule settings.
 */
class ReindexRuleProductPrice extends \Magento\CatalogRule\Model\Indexer\ReindexRuleProductPrice
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\CatalogRule\Model\Indexer\RuleProductsSelectBuilder
     */
    private $ruleProductsSelectBuilder;

    /**
     * @var \Riki\CatalogRule\Model\Indexer\ProductPriceCalculator
     */
    private $productPriceCalculator;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $dateTime;

    /**
     * @var \Magento\CatalogRule\Model\Indexer\RuleProductPricesPersistor
     */
    private $pricesPersistor;

    /**
     * ReindexRuleProductPrice constructor.
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\CatalogRule\Model\Indexer\RuleProductsSelectBuilder $ruleProductsSelectBuilder
     * @param \Riki\CatalogRule\Model\Indexer\ProductPriceCalculator $productPriceCalculator
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\CatalogRule\Model\Indexer\RuleProductPricesPersistor $pricesPersistor
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\CatalogRule\Model\Indexer\RuleProductsSelectBuilder $ruleProductsSelectBuilder,
        \Riki\CatalogRule\Model\Indexer\ProductPriceCalculator $productPriceCalculator,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\CatalogRule\Model\Indexer\RuleProductPricesPersistor $pricesPersistor
    ) {
        $this->storeManager = $storeManager;
        $this->ruleProductsSelectBuilder = $ruleProductsSelectBuilder;
        $this->productPriceCalculator = $productPriceCalculator;
        $this->dateTime = $dateTime;
        $this->pricesPersistor = $pricesPersistor;

        parent::__construct(
            $storeManager,
            $ruleProductsSelectBuilder,
            $productPriceCalculator,
            $dateTime,
            $pricesPersistor
        );
    }

    /**
     * Reindex product prices.
     *
     * @param int $batchCount
     * @param \Magento\Catalog\Model\Product|null $product
     * @param bool $useAdditionalTable
     * @return bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute(
        $batchCount,
        \Magento\Catalog\Model\Product $product = null,
        $useAdditionalTable = false
    ) {

        /**
         * Update products rules prices per each website separately
         * because of max join limit in mysql
         */
        foreach ($this->storeManager->getWebsites() as $website) {

            /**
             * Find month and day for the time zone of the current website.
             * $defaultTimezone should be 'UTC' from app/bootstrap.php.
             */
            $defaultTimezone = date_default_timezone_get();

            date_default_timezone_set($website->getConfig('general/locale/timezone'));

            $dateMonth = date('n');
            $dateDay = date('j');
            $dateYear = date('Y');

            date_default_timezone_set($defaultTimezone);

            /**
             * Calculate time stamps in UTC with website dates.
             */
            $fromDate = mktime(0, 0, 0, $dateMonth, $dateDay - 1, $dateYear);
            $toDate = mktime(0, 0, 0, $dateMonth, $dateDay + 1, $dateYear);

            $productsStmt = $this->ruleProductsSelectBuilder->build($website->getId(), $product, $useAdditionalTable);
            $dayPrices = [];
            $stopFlags = [];
            $prevKey = null;

            while ($ruleData = $productsStmt->fetch()) {
                $ruleProductId = $ruleData['product_id'];
                $productKey = $ruleProductId .
                    '_' .
                    $ruleData['website_id'] .
                    '_' .
                    $ruleData['customer_group_id'].
                    '_' .
                    $ruleData['course_id'] .
                    '_' .
                    $ruleData['frequency_id'];

                if ($prevKey && $prevKey != $productKey) {
                    $stopFlags = [];
                    if (count($dayPrices) > $batchCount) {
                        $this->pricesPersistor->execute($dayPrices, $useAdditionalTable);
                        $dayPrices = [];
                    }
                }

                $ruleData['from_time'] = $this->roundTime($ruleData['from_time']);
                $ruleData['to_time'] = $this->roundTime($ruleData['to_time']);
                /**
                 * Build prices for each day
                 */
                for ($time = $fromDate; $time <= $toDate; $time += IndexBuilder::SECONDS_IN_DAY) {
                    if (($ruleData['from_time'] == 0 ||
                            $time >= $ruleData['from_time']) && ($ruleData['to_time'] == 0 ||
                            $time <= $ruleData['to_time'])
                    ) {
                        $priceKey = $time . '_' . $productKey;

                        if (isset($stopFlags[$priceKey])) {
                            continue;
                        }

                        if (!isset($dayPrices[$priceKey . '_' . $ruleData['rule_id']])) {
                            $dayPrices[$priceKey . '_' . $ruleData['rule_id']] = [
                                'rule_date' => $time,
                                'website_id' => $ruleData['website_id'],
                                'customer_group_id' => $ruleData['customer_group_id'],
                                'product_id' => $ruleProductId,
                                'rule_price' => $this->productPriceCalculator->calculate($ruleData),
                                'latest_start_date' => $ruleData['from_time'],
                                'earliest_end_date' => $ruleData['to_time'],
                                'course_id' => $ruleData['course_id'],
                                'frequency_id' => $ruleData['frequency_id'],
                                'rule_id' => $ruleData['rule_id'],
                                'base_price' => $ruleData['default_price']
                            ];
                        } else {
                            $dayPrices[$priceKey . '_' . $ruleData['rule_id']]['rule_price'] = $this->productPriceCalculator->calculate(
                                $ruleData,
                                $dayPrices[$priceKey . '_' . $ruleData['rule_id']]
                            );
                            $dayPrices[$priceKey . '_' . $ruleData['rule_id']]['latest_start_date'] = max(
                                $dayPrices[$priceKey . '_' . $ruleData['rule_id']]['latest_start_date'],
                                $ruleData['from_time']
                            );
                            $dayPrices[$priceKey . '_' . $ruleData['rule_id']]['earliest_end_date'] = min(
                                $dayPrices[$priceKey . '_' . $ruleData['rule_id']]['earliest_end_date'],
                                $ruleData['to_time']
                            );
                        }

                        if ($ruleData['action_stop']) {
                            $stopFlags[$priceKey] = true;
                        }
                    }
                }

                $prevKey = $productKey;
            }
            $this->pricesPersistor->execute($dayPrices, $useAdditionalTable);
        }
        return true;
    }

    /**
     * @param int $timeStamp
     * @return int
     */
    private function roundTime($timeStamp)
    {
        if (is_numeric($timeStamp) && $timeStamp != 0) {
            $timeStamp = $this->dateTime->timestamp($this->dateTime->date('Y-m-d 00:00:00', $timeStamp));
        }
        return $timeStamp;
    }
}
