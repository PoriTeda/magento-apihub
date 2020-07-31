<?php

namespace Riki\NpAtobarai\Ui\Component\Listing\Columns;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;

class ShipmentIdAction extends \Magento\Ui\Component\Listing\Columns\Column
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /** Url Path */
    const PATH_URL_VIEW = 'sales/shipment/view';
    const PARAMETER_URL_VIEW = 'shipment_id';

    /**
     * ShipmentIdAction constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $name = $this->getData('name');
                if (isset($item[$name])) {
                    $item[$name] =
                        '<a href="'.
                        $this->urlBuilder->getUrl(
                            self::PATH_URL_VIEW,
                            [self::PARAMETER_URL_VIEW  => $item[$name]]
                        ). '">#'.
                        $item['shipment_increment_id'].'</a>'
                    ;
                }
            }
        }
        return $dataSource;
    }
}
