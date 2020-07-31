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
namespace Riki\TmpRma\Block\Adminhtml\Rma\Edit;

use Magento\Framework\Api;
use Riki\TmpRma\Api\CommentRepositoryInterface;
use Magento\Backend\Block;

/**
 * Class Comment
 *
 * @category  RIKI
 * @package   Riki\TmpRma\Block
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Comment extends \Magento\Backend\Block\Template
{
    /**
     * CommentRepository
     *
     * @var \Riki\TmpRma\Api\CommentRepositoryInterface
     */
    protected $commentRepository;
    /**
     * SearchCriteriaBuilder
     *
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;
    /**
     * Registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * Comment constructor.
     *
     * @param \Magento\Framework\Registry $registry              registry
     * @param Api\SearchCriteriaBuilder   $searchCriteriaBuilder api
     * @param CommentRepositoryInterface  $commentRepository     repository
     * @param Block\Template\Context      $context               context
     * @param array                       $data                  data
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Riki\TmpRma\Api\CommentRepositoryInterface $commentRepository,
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->commentRepository = $commentRepository;
        parent::__construct($context, $data);
    }

    /**
     * Get current temp rma
     *
     * @return \Riki\TmpRma\Model\Rma|null
     */
    public function getTmpRma()
    {
        return $this->registry->registry('_current_rma');
    }

    /**
     * Get comments
     *
     * @return array|\Magento\Framework\Api\ExtensibleDataInterface[]
     */
    public function getComments()
    {
        /**
         * Type hinting
         *
         * @var \Riki\TmpRma\Model\Rma $rma
         */
        $rma = $this->getTmpRma();
        if (!$rma || !$rma->getId()) {
            return [];
        }

        $search = $this->searchCriteriaBuilder
            ->addFilter('parent_id', $rma->getId())
            ->create();

        $result = $this->commentRepository->getList($search);

        return $result->getItems();
    }
}
