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
 * to servicedesk@totalinternetgroup.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact servicedesk@totalinternetgroup.nl for more information.
 *
 * @copyright   Copyright (c) 2014 Total Internet Group B.V. (http://www.totalinternetgroup.nl)
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
class TIG_PostNL_Helper_Parcelware extends TIG_PostNL_Helper_Data
{
    /**
     * XML path to auto confirm setting
     */
    const XML_PATH_AUTO_CONFIRM = 'postnl/parcelware_export/auto_confirm';

    /**
     * XML path to the active/inactive setting
     */
    const XML_PATH_ACTIVE = 'postnl/parcelware_export/active';

    /**
     * AutoConfirmEnabled flag
     *
     * @var boolean|null $_autoConfirmEnabled
     */
    protected $_autoConfirmEnabled = null;

    /**
     * Gets the autoConfirmEnabled flag
     *
     * @return boolean|null
     */
    public function getAutoConfirmEnabled()
    {
        return $this->_autoConfirmEnabled;
    }

    /**
     * Sets the autoConfirmEnabled flag
     *
     * @param boolean $autoConfirmEnabled
     *
     * @return TIG_PostNL_Helper_Parcelware
     */
    public function setAutoConfirmEnabled($autoConfirmEnabled)
    {
        $this->_autoConfirmEnabled = $autoConfirmEnabled;

        return $this;
    }

    /**
     * Check to see if parcelware export functionality is enabled.
     *
     * @todo implement this method
     */
    public function isParcelwareExportEnabled($storeId = null)
    {
        if ($storeId === null) {
            $storeId = Mage::app()->getStore()->getId();
        }

        $active = Mage::getStoreConfigFlag(self::XML_PATH_ACTIVE, $storeId);

        return $active;
    }

    /**
     * Checks if auto confirm is enabled
     *
     * @return boolean
     */
    public function isAutoConfirmEnabled()
    {
        if ($this->getAutoConfirmEnabled() !== null) {
            return $this->getAutoConfirmEnabled();
        }

        $autoConfirmEnabled = Mage::getStoreConfigFlag(self::XML_PATH_AUTO_CONFIRM, Mage_Core_Model_App::ADMIN_STORE_ID);

        $this->setAutoConfirmEnabled($autoConfirmEnabled);
        return $autoConfirmEnabled;
    }
}
