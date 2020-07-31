<?php
/**
 * Jobs Abstract Controller
 *
 * PHP version 7
 *
 * @category  Bluecom\Scheduler
 * @package   Bluecom\Scheduler\Controller\Adminhtml\Jobs
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Bluecom\Scheduler\Controller\Adminhtml\Jobs;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Bluecom\Scheduler\Model\JobsFactory;
use Bluecom\Scheduler\Model\SchedulesFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\App\State;
/**
 * Class JobsAbstract
 *
 * @category  Bluecom\Scheduler
 * @package   Bluecom\Scheduler\Controller\Adminhtml\Jobs
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
abstract class JobsAbstract extends Action
{
    /**
     *
     */
    const ADMIN_RESOURCE = 'Bluecom_Scheduler::jobs';
    /**
     * @var Registry
     */
    protected $registry;
    /**
     * @var \Magento\Backend\Model\View\Result\ForwardFactory
     */
    protected $resultForwardFactory;
    /**
     * @var JobsFactory
     */
    protected $jobsFactory;
    /**
     * @var SchedulesFactory
     */
    protected $scheduleFactory;
    /**
     * @var TimezoneInterface
     */
    protected $timezone;
    /**
     * @var
     */
    protected $datetime;
    /**
     * @var
     */
    protected $state;
    /**
     * @var
     */
    protected $objectManager;

    /**
     * JobsAbstract constructor.
     * @param Action\Context $context
     * @param Registry $registry
     * @param ForwardFactory $resultForwardFactory
     * @param JobsFactory $jobsFactory
     * @param SchedulesFactory $schedulesFactory
     * @param TimezoneInterface $timezone
     * @param State $state
     * @param ObjectManager $objectManager
     */
    public function __construct(
        Action\Context $context,
        Registry $registry,
        ForwardFactory $resultForwardFactory,
        JobsFactory $jobsFactory,
        SchedulesFactory $schedulesFactory,
        TimezoneInterface $timezone,
        DateTime $dateTime,
        State $state
    )
    {
        $this->registry = $registry;
        $this->resultForwardFactory = $resultForwardFactory;
        $this->jobsFactory = $jobsFactory;
        $this->scheduleFactory = $schedulesFactory;
        $this->timezone = $timezone;
        $this->datetime = $dateTime;
        $this->state = $state;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function initResultPage()
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu(self::ADMIN_RESOURCE);
        $resultPage->getConfig()->getTitle()->prepend(__('Jobs Configuration'));
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
