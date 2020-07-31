<?php
namespace Bluecom\Paygent\Ui\Component\Error\Listing\Grid\Column;

use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;

class ReasonCode extends Column
{
    /**
     * @var \Bluecom\Paygent\Model\Error
     */
    protected $_errorHandling;

    public  function  __construct(
        \Bluecom\Paygent\Model\Error $errorHandling,
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    )
    {
        $this->_errorHandling = $errorHandling;
        parent::__construct($context, $uiComponentFactory, $components, $data);

    }

    /**
     * @param array $dataSource
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as $key=> &$item) {
            if(isset($item['payment_error_code']) && $item['payment_error_code'] !=''){
                $errorCode = $item['payment_error_code'];
                $errorHandling = $this->_errorHandling->getCollection()
                                                      ->addFieldToFilter('error_code',$errorCode)
                                                      ->setPageSize(1)
                                                      ->setCurPage(1);
                if($errorHandling && $errorHandling->getSize()>0){
                    $item['payment_error_code'] = $errorHandling->getFirstItem()->getData('backend_message');
                }else{
                    $item['payment_error_code'] = null;
                }
            }
        }
        return $dataSource;
    }
}