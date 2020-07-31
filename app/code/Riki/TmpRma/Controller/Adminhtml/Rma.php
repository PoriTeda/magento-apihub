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
namespace Riki\TmpRma\Controller\Adminhtml;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Registry;

/**
 * Class Rma
 *
 * @category  RIKI
 * @package   Riki\TmpRma\Controller
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
abstract class Rma extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Riki_TmpRma::rma_actions';

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $registry;
    /**
     * ResultPageFactory
     *
     * @var PageFactory
     */
    protected $resultPageFactory;
    /**
     * RmaFactory
     *
     * @var \Riki\TmpRma\Model\RmaFactory
     */
    protected $rmaFactory;

    /**
     * Rma constructor.
     *
     * @param Context                       $context           context
     * @param Registry                      $registry          registry
     * @param PageFactory                   $resultPageFactory factory
     * @param \Riki\TmpRma\Model\RmaFactory $rmaFactory        factory
     */
    public function __construct(
        Context $context,
        Registry $registry,
        PageFactory $resultPageFactory,
        \Riki\TmpRma\Model\RmaFactory $rmaFactory
    ) {
        parent::__construct($context);
        $this->registry = $registry;
        $this->resultPageFactory = $resultPageFactory;
        $this->rmaFactory = $rmaFactory;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    protected function _isAllowed() //@codingStandardsIgnoreLine
    {
        return $this->_authorization->isAllowed(static::ADMIN_RESOURCE);
    }
}