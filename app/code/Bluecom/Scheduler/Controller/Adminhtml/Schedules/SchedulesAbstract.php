<?php
/**
 * SchedulesAbstract Controller
 *
 * PHP version 7
 *
 * @category  Bluecom\Scheduler
 * @package   Bluecom\Scheduler\Controller\Adminhtml\Schedules
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Bluecom\Scheduler\Controller\Adminhtml\Schedules;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Bluecom\Scheduler\Model\SchedulesFactory;
use Psr\Log\LoggerInterface;
/**
 * Class SchedulesAbstract
 *
 * @category  Bluecom\Scheduler
 * @package   Bluecom\Scheduler\Controller\Adminhtml\Schedules
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
abstract class SchedulesAbstract extends Action
{
    /**
     *
     */
    const ADMIN_RESOURCE = 'Bluecom_Scheduler::schedules';
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var \Magento\Backend\Model\View\Result\ForwardFactory
     */
    protected $resultForwardFactory;
    /**
     * @var SchedulesFactory
     */
    protected $schedulerFactory;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * StockStatusAbstract constructor.
     * @param Action\Context $context
     * @param Registry $registry
     * @param ForwardFactory $resultForwardFactory
     */
    public function __construct(
        Action\Context $context,
        Registry $registry,
        ForwardFactory $resultForwardFactory,
        SchedulesFactory $schedulesFactory,
        LoggerInterface $logger
    )
    {
        $this->registry = $registry;
        $this->resultForwardFactory = $resultForwardFactory;
        $this->schedulerFactory = $schedulesFactory;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function initResultPage()
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu(self::ADMIN_RESOURCE);
        $resultPage->getConfig()->getTitle()->prepend(__('Cron scheduler'));
        return $resultPage;

    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }
}
