<?php
/**
 * CvsPayment
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\CvsPayment
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\CvsPayment\Helper;

use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Email
 *
 * @category  RIKI
 * @package   Riki\CvsPayment\Helper
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Email extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * InlineTranslation
     *
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $inlineTranslation;

    /**
     * TransportBuilder
     *
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $transportBuilder;

    /**
     * StoreManager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Email constructor.
     *
     * @param StateInterface                        $inlineTranslation helper
     * @param TransportBuilder                      $transportBuilder  helper
     * @param StoreManagerInterface                 $storeManager      model
     * @param \Magento\Framework\App\Helper\Context $context           helper
     */
    public function __construct(
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->inlineTranslation = $inlineTranslation;
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * Set sender
     *
     * @param string $name   name
     * @param string $sender sender
     *
     * @return $this
     */
    public function setFrom($name, $sender)
    {
        $this->transportBuilder->setFrom(
            [
                'name' => $name,
                'email' => $sender
            ]
        );
        return $this;
    }

    /**
     * Set receiver
     *
     * @param array $receivers receivers
     *
     * @return $this
     */
    public function setTo($receivers = [])
    {
        $this->transportBuilder->addTo($receivers);
        return $this;
    }

    /**
     * Set body
     *
     * @param string $template template
     * @param array  $vars     vars
     *
     * @return $this
     */
    public function setBody($template, $vars)
    {
        $this->transportBuilder
            ->setTemplateOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $this->storeManager->getStore()->getId(),
                ]
            )
            ->setTemplateIdentifier($template)
            ->setTemplateVars($vars);
        return $this;
    }

    /**
     * Send email
     *
     * @return $this
     */
    public function send()
    {
        $this->inlineTranslation->suspend();
        try {
            $this->transportBuilder
                ->getTransport()
                ->sendMessage();
        } catch (\Exception $e) {
            $this->_logger->error($e);
        }
        $this->inlineTranslation->resume();

        return $this;
    }
}
