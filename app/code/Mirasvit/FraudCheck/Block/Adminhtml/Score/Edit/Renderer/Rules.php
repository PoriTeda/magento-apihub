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


namespace Mirasvit\FraudCheck\Block\Adminhtml\Score\Edit\Renderer;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Escaper;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;

class Rules extends AbstractElement
{
    /**
     * @var LayoutInterface
     */
    protected $layout;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        UrlInterface $urlBuilder,
        Registry $registry,
        LayoutInterface $layout,
        Factory $factory,
        CollectionFactory $collectionFactory,
        Escaper $escaper
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->registry = $registry;
        $this->layout = $layout;

        parent::__construct($factory, $collectionFactory, $escaper);
    }

    /**
     * {@inheritdoc}
     */
    public function toHtml()
    {
        return $this->layout
            ->createBlock('Magento\Backend\Block\Template')
            ->setData('score', $this->getScore())
            ->setData('js_config', $this->getJsConfig())
            ->setTemplate('Mirasvit_FraudCheck::score/edit/rules.phtml')
            ->toHtml();
    }

    /**
     * @return \Mirasvit\FraudCheck\Model\Score
     */
    public function getScore()
    {
        return $this->registry->registry('current_model');
    }

    /**
     * @return array
     */
    public function getJsConfig()
    {
        return [
            'ScoreEdit' => [
                'previewUrl' => $this->urlBuilder->getUrl('fraud_check/score/preview'),
            ]
        ];
    }
}
