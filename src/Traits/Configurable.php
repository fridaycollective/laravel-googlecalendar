<?php

namespace FridayCollective\LaravelGoogleCalendar\Traits;

use Google_Service_Gmail;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

/**
 * Trait Configurable
 * @package FridayCollective\LaravelGoogleCalendar\Traits
 */
trait Configurable
{

    protected $additionalScopes = [];
    private $_config;
    public $_integrationConfig;

    public function __construct($config, $integrationConfig)
    {
        $this->_config = $config;
        $this->_integrationConfig = $integrationConfig;
    }

    public function config($string = null)
    {
        $credentials = $this->getClientGoogleCalendarCredentials();

        $allowJsonEncrypt = $this->_config['allow_json_encrypt'];

        if ($credentials) {
            if ($allowJsonEncrypt) {
                $config = json_decode(decrypt($credentials->config), true);
            } else {
                $config = json_decode($credentials->config, true);
            }

            if ($string) {
                if (isset($config[$string])) {
                    return $config[$string];
                }
            } else {
                return $config;
            }

        }

        return null;
    }

    private function getClientGoogleCalendarCredentials()
    {
        return $this->_integrationConfig;
    }

    /**
     * @return array
     */
    public function getConfigs()
    {
        return [
            'client_secret' => $this->_config['client_secret'],
            'client_id' => $this->_config['client_id'],
            'redirect_uri' => url($this->_config['redirect_url']),
            'state' => isset($this->_config['state']) ? $this->_config['state'] : null,
        ];
    }

    public function setAdditionalScopes(array $scopes)
    {
        $this->additionalScopes = $scopes;

        return $this;
    }

    private function configApi()
    {
        $type = $this->_config['access_type'];
        $approval_prompt = $this->_config['approval_prompt'];

        $this->setScopes($this->getScopes());
        $this->setAccessType($type);
        $this->setApprovalPrompt($approval_prompt);
    }

    public abstract function setScopes($scopes);

    public function getScopes()
    {
        $scopes = $this->_config['scopes'];

        return $scopes;
    }

    public abstract function setAccessType($type);

    public abstract function setApprovalPrompt($approval);

}
