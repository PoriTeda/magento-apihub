<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-fraud-check
 * @version   1.0.38
 * @copyright Copyright (C) 2019 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\FraudCheck\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Mirasvit\FraudCheck\Api\Service\RenderServiceInterface;

class FraudScore extends Column
{
    /**
     * @var RenderServiceInterface
     */
    private $renderService;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        RenderServiceInterface $renderService,
        array $components = [],
        array $data = []
    ) {
        $this->renderService = $renderService;

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
                $score = isset($item['fraud_score']) ? $item['fraud_score'] : null;
                $status = isset($item['fraud_status']) ? $item['fraud_status'] : null;

                $item['fraud_score'] = $this->renderService->getScoreBadgeHtml($status, $score);
            }
        }

        return $dataSource;
    }
}
