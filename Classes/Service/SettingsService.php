<?php
declare(strict_types = 1);
namespace Derhansen\FeChangePwd\Service;

/*
 * This file is part of the Extension "fe_change_pwd" for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

/**
 * Class SettingsService
 */
class SettingsService
{
    /**
     * @var mixed
     */
    protected $settings = null;

    /**
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
     */
    protected $configurationManager;

    /**
     * Injects the Configuration Manager and loads the settings
     *
     * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
     */
    public function injectConfigurationManager(
        \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
    ) {
        $this->configurationManager = $configurationManager;
    }

    /**
     * Returns the settings
     *
     * @return array
     */
    public function getSettings()
    {
        if ($this->settings === null) {
            $this->settings = $this->configurationManager->getConfiguration(
                \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS,
                'FeChangePwd'
            );
        }
        return $this->settings;
    }

    /**
     * Returns the password expiry timestamp depending on the configured setting switch. If password expiry is not
     * enabled, 0 is returned. If no password validity in days is configured, 90 days is taken as fallback
     *
     * @param null|\DateTime $currentDate
     * @return int
     */
    public function getPasswordExpiryTimestamp($currentDate = null)
    {
        if (!$currentDate) {
            $currentDate = new \DateTime();
        }
        $result = 0;
        $settings = $this->getSettings();
        if (isset($settings['passwordExpiration']['enabled']) && (bool)$settings['passwordExpiration']['enabled']) {
            $validityInDays = $settings['passwordExpiration']['validityInDays'] ?? 90;
            $currentDate->modify('+' . $validityInDays . 'days');
            $result = $currentDate->getTimestamp();
        }
        return $result;
    }
}
