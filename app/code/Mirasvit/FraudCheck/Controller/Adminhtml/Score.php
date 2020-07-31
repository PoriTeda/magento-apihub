<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-fraud-check
 * @version   1.0.38
 * @copyright Copyright (C) 2019 Mirasvit (https://mirasvit.com/)
 */


namespace Mirasvit\FraudCheck\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Framework\Registry;
use Mirasvit\FraudCheck\Model\Score as ScoreModel;

abstract class Score extends Action
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $session;

    /**
     * @var ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * @var ScoreModel
     */
    protected $score;

    /**
     * @param ScoreModel     $score
     * @param Context        $context
     * @param Registry       $registry
     * @param ForwardFactory $resultForwardFactory
     */
    public function __construct(
        ScoreModel $score,
        Context $context,
        Registry $registry,
        ForwardFactory $resultForwardFactory
    ) {
        $this->score = $score;
        $this->context = $context;
        $this->registry = $registry;
        $this->session = $context->getSession();
        $this->resultForwardFactory = $resultForwardFactory;

        parent::__construct($context);
    }

    /**
     * Initialize page
     *
     * @param \Magento\Backend\Model\View\Result\Page $resultPage
     * @return \Magento\Backend\Model\View\Result\Page
     */
    protected function initPage($resultPage)
    {
        $resultPage->setActiveMenu('Mirasvit_FraudCheck::fraud_check_score');

        $resultPage->getConfig()->getTitle()->prepend(__('Fraud Score'));

        return $resultPage;
    }

    /**
     * @return \Mirasvit\FraudCheck\Model\Score
     */
    protected function initModel()
    {
        $this->registry->register('current_model', $this->score);

        return $this->score;
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->context->getAuthorization()->isAllowed('Mirasvit_FraudCheck::fraud_check_score');
    }
}
