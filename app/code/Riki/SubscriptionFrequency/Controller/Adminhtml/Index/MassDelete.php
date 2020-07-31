<?php
/**
 * SubscriptionFrequency
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\SubscriptionFrequency
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\SubscriptionFrequency\Controller\Adminhtml\Index;

use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Riki\SubscriptionFrequency\Model\ResourceModel\Frequency\CollectionFactory;

/**
 * Class MassDelete
 *
 * @category  RIKI
 * @package   Riki\SubscriptionFrequency\Controller\Adminhtml\Index
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class MassDelete extends \Magento\Backend\App\Action
{
    /**
     * Filter
     *
     * @var Filter
     */
    protected $filter;

    /**
     * CollectionFactory
     *
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * LoggerInterface
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * ProfileHelper
     *
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $profileHelper;

    /**
     * MassDelete constructor.
     *
     * @param Context                                $context           Context
     * @param Filter                                 $filter            Filter
     * @param CollectionFactory                      $collectionFactory CollectionFactory
     * @param \Psr\Log\LoggerInterface               $logger            Logger
     * @param \Riki\Subscription\Helper\Profile\Data $profileHelper     ProfileHelper
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        \Psr\Log\LoggerInterface $logger,
        \Riki\Subscription\Helper\Profile\Data $profileHelper
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->logger = $logger;
        $this->profileHelper = $profileHelper;
        parent::__construct($context);
    }

    /**
     * IsAllowed
     *
     * @return bool
     */
    protected function _isAllowed() // @codingStandardsIgnoreLine
    {
        return $this->_authorization->isAllowed('Riki_SubscriptionFrequency::delete');
    }

    /**
     * Execute
     *
     * @return $this
     */
    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $collectionSize = $collection->getSize();
        $size = 0;

        foreach ($collection as $block) {
            try {
                $checkIsExistInProfile = $this->profileHelper->checkFrequencyIsExistedInProfile($block->getData('frequency_unit'), $block->getData('frequency_interval'));
                if (!$checkIsExistInProfile) {
                    $this->messageManager->addError(__('We cannot delete frequency').' #'.$block->getId().__(' exist in subscription profile'));
                } else {
                    $block->delete();
                    $size ++;
                }
            }catch(\Exception $e){
                $this->logger->error($e);
                $this->messageManager->addError(__('An error has occurred.'));
            }
        }
        if ($size > 0) {
            $this->messageManager->addSuccess(__('A total of %1 record(s) have been deleted.', $size));
        }

        /**
         * ResultRedirect
         *
         * @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect ResultRedirect
         */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }
}
