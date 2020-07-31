<?php
namespace Riki\Catalog\Controller\Adminhtml\Category;

class Delete extends \Magento\Catalog\Controller\Adminhtml\Category\Delete
{
    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $profileHelper;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;

    public function __construct
    (
        \Magento\Backend\App\Action\Context $context,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository,
        \Riki\Subscription\Helper\Profile\Data $profileHelper,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    )
    {
        $this->profileHelper = $profileHelper;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context, $categoryRepository);
    }

    /**
     * @return $this
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $categoryId = (int)$this->getRequest()->getParam('id');
        $parentId = null;
        if ($categoryId) {
            $checkIsExistInSubscription =  $this->profileHelper->checkCatalogIsExistedInCourseAndProfile($categoryId);
            if(!$checkIsExistInSubscription){
                $this->messageManager->addError(__('Cannot delete the category #').$categoryId.__(' because this exits in subscription courses and profiles'));
                return $resultRedirect->setPath('catalog/*/edit', ['_current' => true]);
            }else {
                try {
                    $category = $this->categoryRepository->get($categoryId);
                    $parentId = $category->getParentId();
                    $this->_eventManager->dispatch('catalog_controller_category_delete', ['category' => $category]);
                    $this->_auth->getAuthStorage()->setDeletedPath($category->getPath());
                    $this->categoryRepository->delete($category);

                    //Delete category in subscription_multiple_category_campaign_category
                    $connection = $this->resourceConnection->getConnection('sales');
                    $delete = $connection->delete('subscription_multiple_category_campaign_category', 'category_id = '.$categoryId);

                    $this->messageManager->addSuccess(__('You deleted the category.'));
                } catch (\Magento\Framework\Exception\LocalizedException $e) {
                    $this->messageManager->addError($e->getMessage());
                    return $resultRedirect->setPath('catalog/*/edit', ['_current' => true]);
                } catch (\Exception $e) {
                    $this->messageManager->addError(__('Something went wrong while trying to delete the category.'));
                    return $resultRedirect->setPath('catalog/*/edit', ['_current' => true]);
                }
            }
        }
        return $resultRedirect->setPath('catalog/*/', ['_current' => true, 'id' => $parentId]);
    }

}