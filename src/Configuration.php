<?php namespace Wc1c\Main;

defined('ABSPATH') || exit;

/**
 * Configuration
 *
 * @package Wc1c\Main
 */
class Configuration extends \Wc1c\Main\Data\Entities\Configuration
{
    /**
     * Returns upload directory for configuration.
     *
     * @param string $context
     *
     * @return string
     */
    public function getUploadDirectory(string $context = 'main'): string
    {
        $upload_directory = wc1c()->environment()->get('wc1c_configurations_directory') . '/' . $this->getSchema() . '-' . $this->getId();

        if($context === 'logs')
        {
            $upload_directory .= '/logs';
        }

        if($context === 'files')
        {
            $upload_directory .= '/files';
        }

        return $upload_directory;
    }
}