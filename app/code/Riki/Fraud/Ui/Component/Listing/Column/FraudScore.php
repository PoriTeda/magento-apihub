<?php

namespace Riki\Fraud\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Sales\Model\OrderFactory;
use Riki\Fraud\Model\ScoreFactory;
use Mirasvit\FraudCheck\Helper\Data as DataHelper;

class FraudScore extends Column
{
    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var ScoreFactory
     */
    protected $scoreFactory;

    /**
     * @var DataHelper
     */
    protected $dataHelper;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param OrderFactory $orderFactory
     * @param ScoreFactory $scoreFactory
     * @param DataHelper $dataHelper
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        OrderFactory $orderFactory,
        ScoreFactory $scoreFactory,
        DataHelper $dataHelper,
        array $components = [],
        array $data = []
    )
    {
        $this->orderFactory = $orderFactory;
        $this->scoreFactory = $scoreFactory;
        $this->dataHelper = $dataHelper;

        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items']) && is_array($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                try {
                    $order = $this->orderFactory->create()->load($item['entity_id']);
                    if ($order->getId()) {
                        $score = $this->scoreFactory->create();
                        $score->setOrder($order);
                        $fraudScore = $score->getFraudScore();
                        $fraudStatus = $score->getFraudStatus($fraudScore);
                        $item['fraud_score'] = $this->dataHelper
                            ->getScoreBadgeHtml( $fraudStatus, $fraudScore );
                        $item['fraud_status'] = $fraudStatus;
                    }
                } catch (\Exception $e) {
                    $item['fraud_score'] = $e->getMessage();
                }
            }
        }
        return $dataSource;
    }
}
