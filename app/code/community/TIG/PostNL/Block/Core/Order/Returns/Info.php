<?php
/**
 *                  ___________       __            __
 *                  \__    ___/____ _/  |_ _____   |  |
 *                    |    |  /  _ \\   __\\__  \  |  |
 *                    |    | |  |_| ||  |   / __ \_|  |__
 *                    |____|  \____/ |__|  (____  /|____/
 *                                              \/
 *          ___          __                                   __
 *         |   |  ____ _/  |_   ____ _______   ____    ____ _/  |_
 *         |   | /    \\   __\_/ __ \\_  __ \ /    \ _/ __ \\   __\
 *         |   ||   |  \|  |  \  ___/ |  | \/|   |  \\  ___/ |  |
 *         |___||___|  /|__|   \_____>|__|   |___|  / \_____>|__|
 *                  \/                           \/
 *                  ________
 *                 /  _____/_______   ____   __ __ ______
 *                /   \  ___\_  __ \ /  _ \ |  |  \\____ \
 *                \    \_\  \|  | \/|  |_| ||  |  /|  |_| |
 *                 \______  /|__|    \____/ |____/ |   __/
 *                        \/                       |__|
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License.
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to servicedesk@tig.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact servicedesk@tig.nl for more information.
 *
 * @copyright   Copyright (c) Total Internet Group B.V. https://tig.nl/copyright
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
class TIG_PostNL_Block_Core_Order_Returns_Info extends Mage_Sales_Block_Order_Info
{
    /**
     * Xpath to the 'return_label_instructions_block' setting.
     */
    const XPATH_RETURN_LABEL_INSTRUCTIONS_BLOCK = 'postnl/returns/return_label_instructions_block';

    /**
     * @var string
     */
    protected $_template = 'TIG/PostNL/core/order/returns/info.phtml';

    /**
     * Class constructor.
     */
    protected function _construct()
    {
        Mage_Core_Block_Template::_construct();
    }

    /**
     * Get the configured return info block HTML if available.
     *
     * @return string
     */
    public function getReturnInfoBlockHtml()
    {
        $infoBlockId = Mage::getStoreConfig(self::XPATH_RETURN_LABEL_INSTRUCTIONS_BLOCK);

        if (!is_numeric($infoBlockId) || '' === $infoBlockId) {
            return '';
        }

        /**
         * @var Mage_Cms_Block_Block $infoBlock
         */
        /** @noinspection PhpUndefinedMethodInspection */
        $infoBlock = $this->getLayout()
                          ->createBlock('cms/block')
                          ->setBlockId($infoBlockId);

        return $infoBlock->toHtml();
    }
}
