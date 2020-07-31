<?php
/**
 * MassSchenow Controller
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
use Magento\Backend\App\Action\Context;
use Magento\Cron\Model\ScheduleFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;
use Bluecom\Scheduler\Model\ResourceModel\Jobs\CollectionFactory;
use Bluecom\Scheduler\Model\SchedulesFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Cron\Model\Schedule;
/**
 * Class MassSchenow
 *
 * @category  Bluecom\Scheduler
 * @package   Bluecom\Scheduler\Controller\Adminhtml\Jobs
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class MassSchenow extends \Magento\Backend\App\Action
{
    /**
     * @var Filter
     */
    protected $filter;
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;
    /**
     * @var TimezoneInterface
     */
    protected $timezone;
    /**
     * @var ScheduleFactory
     */
    protected $schedulesFactory;

    /**
     * MassSchedule constructor.
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param TimezoneInterface $timezone
     * @param SchedulesFactory $scheduleFactory
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        TimezoneInterface $timezone,
        SchedulesFactory $schedulesFactory
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->timezone = $timezone;
        $this->schedulesFactory = $schedulesFactory;
    }
    /**
     * @return $this
     */
    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $collection->addFieldToFilter('active',1);
        $collectionSize = $collection->getSize();
        $createdTime = strftime('%Y-%m-%d %H:%M:%S', $this->timezone->scopeTimeStamp());
        $executeTime = strftime('%Y-%m-%d %H:%M:%S', $this->timezone->scopeTimeStamp()+30);
        foreach ($collection as $item)
        {
            try {
                $schedules = $this->schedulesFactory->create();
                $schedules->setStatus(Schedule::STATUS_PENDING)
                    ->setJobCode($item->getJobCode())
                    ->setCreatedAt($createdTime)
                    ->setScheduledAt($executeTime);
                $schedules->save();
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        }
        $this->messageManager->addSuccess(__('A total of %1 record(s) have been scheduled.', $collectionSize));
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }
}
