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


namespace Mirasvit\FraudCheck\Block\Adminhtml\Score;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Mirasvit\FraudCheck\Model\Score;

class View extends Template
{
    /**
     * @var string
     */
    protected $_template = 'score/view.phtml';

    /**
     * @var Score
     */
    protected $score;

    /**
     * @param Score   $scoreFactory
     * @param Context $context
     */
    public function __construct(
        Score $scoreFactory,
        Context $context
    ) {
        $this->score = $scoreFactory;

        parent::__construct($context);
    }

    /**
     * @return Score
     */
    public function getScore()
    {
        return $this->score;
    }
}
