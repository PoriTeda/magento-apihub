<?php
/**
 * Framework
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Framework
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\Framework\Helper\Cache;
/**
 * Best practices:
 *  Function cache is implement singleton pattern, then use it to prevent:
namespace Riki\Framework\Helper\Cache;
/**
 * Best practices:
 *  function cache is implement singleton pattern, then use it to prevent:
 *  - re-calc complex expression
 *  - re-fetch resource from db
 *  - do heavy job like read xml, IO again
 *
 *  But never use it on every function, it will cause store duplicate data
 *  And should use it on:
 *  - Helper
 *  - Model
 *  - ResourceModel
 *  - ... (any thing which provide data)
 *
 *  Be careful when use it on changeable data,
 *  (should invalidate before cache to prevent bug hole)
 */

/**
 * Class FunctionCache
 *
 * @category  RIKI
 * @package   Riki\Framework\Helper
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class FunctionCache extends AppCache
{
    /**
     * {@inheritdoc}
     *
     * @param $params
     *
     * @return string
     */
    public function getCacheKey($params)
    {
        $cacheKey = parent::getCacheKey($params);
        if (!isset($params['funcKey'])) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
            list(, , $target) = $trace;
            $funcKey = $target['class'] . $target['type'] . $target['function'];
        } else {
            $funcKey = $params['funcKey'];
        }

        return $funcKey . '_' . $cacheKey;
    }
}