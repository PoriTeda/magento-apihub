<?php
/**
 * Riki Basic Setup
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\BasicSetup\Setup
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\BasicSetup\Model;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory;
use Magento\Sales\Model\Order\StatusFactory;
/**
 * Class OrderStatusSetup
 *
 * @category  RIKI
 * @package   Riki\BasicSetup\Setup
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class OrderStatusSetup{

    /**
     * @var CollectionFactory
     */
    protected $statusCollectionFactory;
    /**
     * @var StatusFactory
     */
    protected $statusModelFactory;

    /**
     * OrderStatusSetup constructor.
     * @param CollectionFactory $collectionFactory
     * @param StatusFactory $statusFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        StatusFactory $statusFactory
    )
    {
        $this->statusCollectionFactory = $collectionFactory;
        $this->statusModelFactory = $statusFactory;
    }

    /**
     * @throws \Exception
     */
    public function setupBasic()
    {
        $statusData = array(

            'pending'=> array(
                'status' => 'NEW_ORDER',
                'status_code' => 'pending',
                'default' => '1',
                'visible' => '1',
                'state' => 'new',
            ),
            'pending_cvs_payment'=> array(
                'status' => 'PENDING_CVS',
                'status_code' => 'pending_cvs_payment',
                'default' => '0',
                'visible' => '1',
                'state' => 'new',
            ),
            'pending_payment'=> array(
                'status' => 'PENDING_CC',
                'status_code' => 'pending_payment',
                'default' => '1',
                'visible' => '1',
                'state' => 'pending_payment',
            ),
            'capture_failed'=> array(
                'status' => 'CAPTURE_FAILED',
                'status_code' => 'capture_failed',
                'default' => '0',
                'visible' => '1',
                'state' => 'processing',
            ),
            'fraud'=> array(
                'status' => 'SUSPECTED_FRAUD',
                'status_code' => 'fraud',
                'default' => '0',
                'visible' => '0',
                'state' => 'processing',
            ),
            'suspicious'=> array(
                'status' => 'SUSPICIOUS',
                'status_code' => 'suspicious',
                'default' => '0',
                'visible' => '0',
                'state' => 'processing',
            ),
            'waiting_for_shipping'=> array(
                'status' => 'NOT_SHIPPED',
                'status_code' => 'waiting_for_shipping',
                'default' => '1',
                'visible' => '1',
                'state' => 'processing',
            ),
            'preparing_for_shipping'=> array(
                'status' => 'IN_PROCESSING',
                'status_code' => 'preparing_for_shipping',
                'default' => '0',
                'visible' => '1',
                'state' => 'processing',
            ),
            'partially_shipped'=> array(
                'status' => 'PARTIALLY_SHIPPED',
                'status_code' => 'partially_shipped',
                'default' => '0',
                'visible' => '1',
                'state' => 'processing',
            ),
            'shipped_all'=> array(
                'status' => 'SHIPPED_ALL',
                'status_code' => 'shipped_all',
                'default' => '0',
                'visible' => '1',
                'state' => 'processing',
            ),
            'holded'=> array(
                'status' => 'PENDING_CRD_REVIEW',
                'status_code' => 'holded',
                'default' => '0',
                'visible' => '0',
                'state' => 'holded',
            ),
            'feedback_crd'=> array(
                'status' => 'CRD_FEEDBACK',
                'status_code' => 'feedback_crd',
                'default' => '0',
                'visible' => '0',
                'state' => 'holded',
            ),
            'complete'=> array(
                'status' => 'COMPLETE',
                'status_code' => 'complete',
                'default' => '1',
                'visible' => '1',
                'state' => 'complete',
            ),
            'canceled'=> array(
                'status' => 'CANCELED',
                'status_code' => 'canceled',
                'default' => '1',
                'visible' => '1',
                'state' => 'canceled',
            )
        );

        //import data
        $existStatus = array();
        $statusCollection = $this->statusCollectionFactory->create();
        if($statusCollection->getSize()) {
            foreach($statusCollection as $_status){
                $statusKey = $_status->getStatus();
                if(array_key_exists($statusKey,$statusData )){
                    //update data
                    $existStatus[] = $statusKey;
                    $_status->setLabel($statusData[$statusKey]['status']);
                    $_status->assignState(
                        $statusData[$statusKey]['state'],
                        $statusData[$statusKey]['default'],
                        $statusData[$statusKey]['visible']
                    );
                    try{
                        $_status->save();
                    } catch(\Exception $e){
                        throw $e;
                    }
                }else{
                    //remove
                    $_status->delete();
                }

            }

        }
        // create new status
        if($existStatus){
            foreach($statusData as $_newKey => $_newStatus){
                if(!in_array($_newKey, $existStatus)){
                    $statusModel = $this->statusModelFactory->create();
                    try{
                        $statusModel->setStatus($_newKey);
                        $statusModel->setLabel($_newStatus['status']);
                        $statusModel->save();
                        $statusModel->assignState
                        (
                            $_newStatus['state'],
                            $_newStatus['default'],
                            $_newStatus['visible']
                        );
                    }catch(\Exception $e){
                        throw $e;
                    }
                }
            }
        }

    }//end function

    /**
     * @throws \Exception
     */
    public function addCloseState()
    {
        $statusModel = $this->statusModelFactory->create();
        try{
            $statusModel->setStatus(\Magento\Sales\Model\Order::STATE_CLOSED);
            $statusModel->setLabel('Closed');
            $statusModel->save();
            $statusModel->assignState
            (
                \Magento\Sales\Model\Order::STATE_CLOSED,
                1,
                1
            );
        }catch(\Exception $e){
            throw $e;
        }
    }

    /**
     * @throws \Exception
     */
    public function addCvsHold()
    {
        $statusModel = $this->statusModelFactory->create();
        try{
            $statusModel->setStatus('hold_cvs');
            $statusModel->setLabel('Hold_CVS ');
            $statusModel->save();
            $statusModel->assignState
            (
                \Magento\Sales\Model\Order::STATE_CANCELED,
                0,
                1
            );
        }catch(\Exception $e){
            throw $e;
        }
    }

    /**
     * Add new order status for CVS order
     *
     * @throws \Exception
     */
    public function addCVScancelationStatus()
    {
        $statusModel = $this->statusModelFactory->create();
        try{
            $statusModel->setStatus('hold_cvs_nopayment');
            $statusModel->setLabel('Hold - CVS cancellation without payment');
            $statusModel->save();
            $statusModel->assignState
            (
                \Magento\Sales\Model\Order::STATE_CANCELED,
                0,
                1
            );
        }catch(\Exception $e){
            throw $e;
        }
    }

    /**
     * Add new order status for order shipment exported
     *
     * @throws \Exception
     */
    public function addProcessingCanceledStatus()
    {
        $statusModel = $this->statusModelFactory->create();
        try{
            $statusModel->setStatus('processing_canceled');
            $statusModel->setLabel('PROCESSING_CANCELED');
            $statusModel->save();
            $statusModel->assignState
            (
                \Magento\Sales\Model\Order::STATE_CANCELED,
                0,
                1
            );
        }catch(\Exception $e){
            throw $e;
        }
    }

    /**
     * @throws \Exception
     */
    public function addCVScancelationRefundStatus()
    {
        $statusModel = $this->statusModelFactory->create();
        try{
            $statusModel->setStatus('cvs_cancellation_with_payment');
            $statusModel->setLabel('CVS cancellation with payment - To refund');
            $statusModel->save();
            $statusModel->assignState
            (
                \Magento\Sales\Model\Order::STATE_CANCELED,
                0,
                1
            );
        }catch(\Exception $e){
            throw $e;
        }
    }
}//end class