<?php
namespace Bluecom\Paygent\Ui\Component\DataProvider\SearchResult\Column;

use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;

class ReasonCode extends Column
{
    const DEFAULT_ERROR_CODE = 'Others';

    /**
     * @var \Bluecom\Paygent\Model\ErrorFactory
     */
    protected $errorHandlingFactory;

    /**
     * ReasonCode constructor.
     * @param \Bluecom\Paygent\Model\ErrorFactory $errorHandlingFactory
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        \Bluecom\Paygent\Model\ErrorFactory $errorHandlingFactory,
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        $this->errorHandlingFactory = $errorHandlingFactory;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }
        foreach ($dataSource['data']['items'] as $key => &$item) {
            if (isset($item['payment_error_code'])) {
                $errorCode = $item['payment_error_code'];
                $errorHandling = $this->errorHandlingFactory->create();
                $errorHandlingCollection = $errorHandling->getCollection()
                    ->addFieldToFilter('error_code', $errorCode);
                if ($errorHandlingCollection->getSize() > 0) {
                    $item['backend_message'] = $errorHandlingCollection->getFirstItem()->getData('backend_message');
                } else {
                    $errorHandling = $errorHandling->load(self::DEFAULT_ERROR_CODE, 'error_code');
                    if ($errorHandling->getId()) {
                        $item['backend_message'] = $errorHandling->getData('backend_message');
                    }
                }
            }
        }
        return $dataSource;
    }
}
