<?php

namespace Riki\Customer\Controller\Adminhtml\Index;

use Magento\Backend\App\Action\Context;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class MassDelete
 */
class MassDelete extends \Magento\Customer\Controller\Adminhtml\Index\MassDelete
{
    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $profileHelper;

    public function __construct
    (
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        CustomerRepositoryInterface $customerRepository,
        \Riki\Subscription\Helper\Profile\Data $profileHelper
    )
    {
        $this->profileHelper = $profileHelper;
        parent::__construct($context, $filter, $collectionFactory, $customerRepository);
    }

    /**
     * @param AbstractCollection $collection
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    protected function massAction(AbstractCollection $collection)
    {
        $customersDeleted = 0;
        foreach ($collection->getAllIds() as $customerId) {
            $checkIsExistInProfile = $this->profileHelper->checkCustomerIsExistedInProfile($customerId);
            if(!$checkIsExistInProfile){
                $this->messageManager->addError(__('We cannot delete customer').' #'.$customerId.__(' because it exist in subscription profiles'));
            }else {
                $this->customerRepository->deleteById($customerId);
                $customersDeleted++;
            }
        }

        if ($customersDeleted) {
            $this->messageManager->addSuccess(__('A total of %1 record(s) were deleted.', $customersDeleted));
        }
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath($this->getComponentRefererUrl());

        return $resultRedirect;
    }

    /**
     * Customer access rights checking
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Customer::delete');
    }
}
