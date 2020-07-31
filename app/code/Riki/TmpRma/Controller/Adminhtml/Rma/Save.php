<?php
/**
 * TmpRma
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\TmpRma
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\TmpRma\Controller\Adminhtml\Rma;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use \Magento\Framework\Registry;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Save
 *
 * @category  RIKI
 * @package   Riki\TmpRma\Controller
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Save extends \Riki\TmpRma\Controller\Adminhtml\Rma
{
    /**
     * RmaFactory
     *
     * @var \Riki\TmpRma\Model\RmaFactory
     */
    protected $rmaFactory;
    /**
     * RmaItemFactory
     *
     * @var \Riki\TmpRma\Model\Rma\ItemFactory
     */
    protected $rmaItemFactory;
    /**
     * StatusHelper
     *
     * @var \Riki\TmpRma\Helper\Status
     */
    protected $statusHelper;
    /**
     * CommentRepository
     *
     * @var \Riki\TmpRma\Model\Rma\CommentRepository
     */
    protected $commentRepository;

    /**
     * Save constructor.
     *
     * @param Context $context context
     * @param Registry $coreRegistry registry
     * @param \Riki\TmpRma\Model\RmaFactory $rmaFactory factory
     * @param \Riki\TmpRma\Model\Rma\ItemFactory $rmaItemFactory factory
     * @param PageFactory $resultPageFactory factory
     * @param \Riki\TmpRma\Helper\Status $statusHelper helper
     * @param \Riki\TmpRma\Model\Rma\CommentRepository $commentRepository repository
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        \Riki\TmpRma\Model\RmaFactory $rmaFactory,
        \Riki\TmpRma\Model\Rma\ItemFactory $rmaItemFactory,
        PageFactory $resultPageFactory,
        \Riki\TmpRma\Helper\Status $statusHelper,
        \Riki\TmpRma\Model\Rma\CommentRepository $commentRepository
    )
    {
        parent::__construct(
            $context,
            $coreRegistry,
            $resultPageFactory,
            $rmaFactory
        );
        $this->rmaFactory = $rmaFactory;
        $this->rmaItemFactory = $rmaItemFactory;
        $this->statusHelper = $statusHelper;
        $this->commentRepository = $commentRepository;
    }

    /**
     * {@inheritdoc}
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $returnToEdit = false;
        $originalRequestData = $this->getRequest()->getPostValue();
        $rmaId = isset($originalRequestData['rma']['id'])
            ? $originalRequestData['rma']['id']
            : null;
        if ($originalRequestData) {
            try {
                // optional fields might be set in
                // request for future processing by observers in other modules
                $rmaData = $originalRequestData['rma'];
                $itemsData = isset($originalRequestData['items'])
                    ? $originalRequestData['items']
                    : [];
                $request = $this->getRequest();
                $rma = $this->rmaFactory->create();
                if ($rmaId) {
                    $rma->load($rmaId);
                }
                //Check half-width numberic of home phone

                if ( $rmaData['phone_number'] != '' && !preg_match('/(^\d+(-|\d)+)$/', $rmaData['phone_number'])) {
                    $this->messageManager->addErrorMessage(__('Please enter half-width number.'));
                    $resultRedirect = $this->resultRedirectFactory->create();
                    $resultRedirect->setPath(
                        '*/*/edit',
                        ['id' => $rmaId, '_current' => true]
                    );
                    return $resultRedirect;
                }
                // check constraint status
                if (isset($rmaData['status'])) {
                    $currentStatus = $rma->getStatus();
                    $updatable = $this->statusHelper
                        ->isUpdatable($currentStatus, $rmaData['status']);
                    if ($currentStatus != $rmaData['status'] && !$updatable) {
                        $label = $this->statusHelper
                            ->getLabel($rmaData['status']);
                        $labels = $this->statusHelper
                            ->getDepBeforeLabel($rmaData['status']);

                        throw new LocalizedException(
                            __(
                                'Unable change status to %1, '
                                . 'must be status [%2] first!',
                                $label,
                                implode(",", $labels)
                            )
                        );
                    }
                }

                $this->_eventManager->dispatch(
                    'adminhtml_tmprma_prepare_save',
                    ['rma' => $rma, 'post_data' => $rmaData]
                );

                $rma->setItems($itemsData);
                $rma->addData($rmaData);

                $rma->save();
                $rmaId = $rma->getId();

                if (!empty($rmaData['comment'])) {
                    $comment = $this->commentRepository->get(null);
                    $comment->setParentId($rmaId)
                        ->setComment($rmaData['comment']);
                    $this->commentRepository->save($comment);
                }

                // After save
                $this->_eventManager->dispatch(
                    'adminhtml_tmprma_save_after',
                    ['rma' => $rma, 'request' => $request]
                );

                $this->registry->register('_current_rma_id', $rmaId);
                $this->messageManager->addSuccess(__('You saved the return.'));
                $returnToEdit = (bool)$this->getRequest()->getParam('back', false);
            } catch (\Magento\Framework\Validator\Exception $exception) {
                $this->messageManager->addError(nl2br($exception->getMessage()));
                $returnToEdit = true;
            } catch (LocalizedException $exception) {
                $this->messageManager->addError(nl2br($exception->getMessage()));
                $returnToEdit = true;
            } catch (\Exception $exception) {
                $this->messageManager
                    ->addException(
                        $exception,
                        __('Something went wrong while saving the return.')
                    );
                $returnToEdit = true;
            }
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($returnToEdit) {
            if ($rmaId) {
                $resultRedirect->setPath(
                    '*/*/edit',
                    ['id' => $rmaId, '_current' => true]
                );
            } else {
                $resultRedirect->setPath(
                    '*/*/newAction',
                    ['_current' => true]
                );
            }
        } else {
            $resultRedirect->setPath('*/*');
        }
        return $resultRedirect;
    }

    /**
     * @return bool
     */
    protected function _isAllowed() //@codingStandardsIgnoreLine
    {
        $originalRequestData = $this->getRequest()->getPostValue();
        if (isset($originalRequestData['rma']['id'])) {
            return $this->_authorization->isAllowed('Riki_TmpRma::rma_actions_edit');
        } else {
            return $this->_authorization->isAllowed('Riki_TmpRma::rma_actions_create');
        }
    }
}
