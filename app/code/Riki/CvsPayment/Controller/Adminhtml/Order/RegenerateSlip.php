<?php
/**
 * CvsPayment
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\CvsPayment
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\CvsPayment\Controller\Adminhtml\Order;

/**
 * Class RegenerateSlip
 *
 * @category  RIKI
 * @package   Riki\CvsPayment\Controller
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class RegenerateSlip extends \Magento\Backend\App\Action
{
    /**
     * OrderRepository
     *
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;
    /**
     * Datetime
     *
     * @var \Riki\Framework\Helper\Datetime
     */
    protected $datetimeHelper;

    /**
     * RegenerateSlip constructor.
     *
     * @param \Riki\Framework\Helper\Datetime             $datetimeHelper  helper
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository repo
     * @param \Magento\Backend\App\Action\Context         $context         context
     */
    public function __construct(
        \Riki\Framework\Helper\Datetime $datetimeHelper,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->datetimeHelper = $datetimeHelper;
        $this->orderRepository = $orderRepository;
        parent::__construct($context);
    }

    /**
     * Execute
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        /**
         * Type hinting
         *
         * @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect
         */
        $resultRedirect = $this->resultFactory
            ->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath($this->getUrl('cvspayment/order'));

        try {
            $order = $this->orderRepository
                ->get($this->getRequest()->getParam('order_id'));
            if ($order) {
                $order->setData('flag_cvs', 0);
                $order->setData('csv_start_date', $this->datetimeHelper->toDb());
                $this->orderRepository->save($order);
                $this->messageManager
                    ->addSuccess(__('Your order has been updated successfully'));
                $resultRedirect
                    ->setPath(
                        $this->getUrl(
                            'sales/order/view',
                            ['order_id' => $order->getEntityId()]
                        )
                    );
            }
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }

        return $resultRedirect;
    }
}
