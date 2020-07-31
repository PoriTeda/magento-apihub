<?php
/**
 * PHP version 7
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 *
 * @category  RIKI
 * @package   Riki_CatalogFreeShipping
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\CatalogFreeShipping\Controller\Adminhtml;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Registry;

/**
 * Class Rule
 *
 * @category RIKI
 * @package  Riki_CatalogFreeShipping
 * @author   Nestle.co.jp <support@nestle.co.jp>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/rikibusiness/riki-ecommerce
 */
abstract class Rule extends \Magento\Backend\App\Action
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry = null;

    /**
     * Page factory
     *
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * Rule
     *
     * @var \Riki\CatalogFreeShipping\Model\RuleFactory
     */
    protected $ruleFactory;


    /**
     * Constructor
     *
     * @param Context                                     $context           context
     * @param Registry                                    $coreRegistry      core registry
     * @param PageFactory                                 $resultPageFactory result
     * @param \Riki\CatalogFreeShipping\Model\RuleFactory $ruleFactory       rule
     *
     * @return self
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        PageFactory $resultPageFactory,
        \Riki\CatalogFreeShipping\Model\RuleFactory $ruleFactory
    ) {
        parent::__construct($context);
        $this->coreRegistry      = $coreRegistry;
        $this->resultPageFactory = $resultPageFactory;
        $this->ruleFactory       = $ruleFactory;

    }//end __construct()


    /**
     * Define redirect result
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function initRedirectResult()
    {
        /*
            * @var \Magento\Backend\Model\View\Result\Redirect $result
        */

        $result = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);

        return $result;

    }//end initRedirectResult()


    /**
     * Load Rule from request
     *
     * @param string $idFieldName field name
     *
     * @return \Riki\CatalogFreeShipping\Model\Rule $model
     */
    protected function _initRule($idFieldName = 'id')
    {
        $id    = (int) $this->getRequest()->getParam($idFieldName);
        $model = $this->ruleFactory->create();
        if ($id) {
            $model->load($id);
        }

        if (!$this->coreRegistry->registry('_current_rule')) {
            $this->coreRegistry->register('_current_rule', $model);
        }

        if (!$this->coreRegistry->registry('_current_rule_id')) {
            $this->coreRegistry->register('_current_rule_id', $id);
        }

        return $model;

    }//end _initRule()


    /**
     * Check permission
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_CatalogFreeShipping::catalog_free_shipping');

    }//end _isAllowed()


}//end class
