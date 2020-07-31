<?php
/**
 * MassDelete Controller
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
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;
use Bluecom\Scheduler\Model\ResourceModel\Schedules\CollectionFactory;

/**
 * Class MassDelete
 *
 * @category  Bluecom\Scheduler
 * @package   Bluecom\Scheduler\Controller\Adminhtml\Schedules
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class MassDelete extends \Magento\Backend\App\Action
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
     * MassDelete constructor.
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context);
    }
    /**
     * @return $this
     */
    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $collectionSize = $collection->getSize();

        foreach ($collection as $item)
        {
            try {
                $item->delete();
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }

        }
        $this->messageManager->addSuccess(__('A total of %1 record(s) have been deleted.', $collectionSize));
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Bluecom_SCheduler::delete');
    }
}