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
 * Class MassRegenerateSlip
 *
 * @category  RIKI
 * @package   Riki\CvsPayment\Controller
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class MassRegenerateSlip extends \Magento\Backend\App\Action
{
    /**
     * Filter
     *
     * @var \Magento\Ui\Component\MassAction\Filter
     */
    protected $filter;
    /**
     * Collection
     *
     * @var \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    protected $collection;
    /**
     * Order Repository
     *
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;
    /**
     * Datetime Helper
     *
     * @var \Riki\Framework\Helper\Datetime
     */
    protected $datetimeHelper;

    /**
     * MassRegenerateSlip constructor.
     *
     * @param \Riki\Framework\Helper\Datetime                     $datetimeHelper  x
     * @param \Magento\Sales\Api\OrderRepositoryInterface         $orderRepository x
     * @param \Magento\Sales\Model\ResourceModel\Order\Collection $collection      x
     * @param \Magento\Ui\Component\MassAction\Filter             $filter          x
     * @param \Magento\Backend\App\Action\Context                 $context         x
     */
    public function __construct(
        \Riki\Framework\Helper\Datetime $datetimeHelper,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\ResourceModel\Order\Collection $collection,
        \Magento\Ui\Component\MassAction\Filter $filter,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->datetimeHelper = $datetimeHelper;
        $this->orderRepository = $orderRepository;
        $this->filter = $filter;
        $this->collection = $collection;
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
        $resultRedirect->setPath('cvspayment/order');

        try {
            $collection = $this->filter->getCollection($this->collection);
            foreach ($collection as $item) {
                $item->setData('flag_cvs', 0);
                $item->setData('csv_start_date', $this->datetimeHelper->toDb());
                $this->orderRepository->save($item);
            }
            $msg = __(
                'Total %1 record(s) have been updated successfully',
                $collection->getSize()
            );
            $this->messageManager->addSuccess($msg);
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }

        return $resultRedirect;
    }
}
