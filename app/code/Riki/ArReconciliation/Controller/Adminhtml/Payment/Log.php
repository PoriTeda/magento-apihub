<?php
namespace Riki\ArReconciliation\Controller\Adminhtml\Payment;

use Magento\Framework\App\Filesystem\DirectoryList;

class Log extends \Magento\Backend\App\Action
{
    protected $_fileFactory;
    protected $_orderPaymentStatusLogCollection;
    protected $_helperData;
    protected $_paymentStatus;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Riki\ArReconciliation\Helper\Data $helper,
        \Riki\Shipment\Model\ResourceModel\Status\Options\Payment $paymentStatus,
        \Riki\ArReconciliation\Model\ResourceModel\OrderPaymentStatusLog\CollectionFactory $orderPaymentStatusLogCollection
    ){
        $this->_fileFactory = $fileFactory;
        $this->_orderPaymentStatusLogCollection = $orderPaymentStatusLogCollection;
        $this->_helperData = $helper;
        $this->_paymentStatus = $paymentStatus;
        parent::__construct($context);
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('order_id');

        $fileName = 'change_log_data.csv';

        $model = $this->_orderPaymentStatusLogCollection->create();

        $order = $model->addFieldToFilter( 'order_id', $id )
                    ->setOrder( 'id', 'DESC');

        $content = $this->getCsvContent( $order );

        return $this->_fileFactory->create(
            $fileName,
            $content,
            DirectoryList::VAR_DIR
        );
    }

    /*get content to export*/
    private function getCsvContent( $order )
    {
        $rs = $this->getCsvHeader();

        if( !empty( $order->getData() ) )
        {
            foreach ( $order as $or )
            {
                $rs .= $this->getCsvItem( $or );
            }
        }
        return $rs;
    }

    /*generate header for csv*/
    private function getCsvHeader()
    {
        return "Username, Order Id, Payment Status, Previous Payment Status, Change Date, Time" ."\n";
    }

    /*generate csv data for each change log*/
    private function getCsvItem( $rt )
    {
        $rs = array(
            $rt->getData('user_name'),
            $rt->getData('order_increment_id'),
            $this->_paymentStatus->getOptionText($rt->getData('payment_status')),
            $this->_paymentStatus->getOptionText($rt->getData('previous_status')),
            $this->_helperData->getWebDate($rt->getData('created')),
            $this->_helperData->getWebTime($rt->getData('created'))
        );
        return  implode( ',', $rs ) ."\n";
    }

}
