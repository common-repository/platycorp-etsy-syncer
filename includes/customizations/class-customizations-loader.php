<?php

namespace platy\etsy\customizations;
use platy\etsy\data\SQLDataService;
use platy\etsy\logs\PlatyLogger;
use platy\etsy\PlatysService;

class CustomizationsLoader {
    const LOG_TYPE = "customization";

    private function  __construct() {
        $this->logger = PlatyLogger::get_instance();
        $this->data_service = new SQLDataService(\Platy_Syncer_Etsy::CUSTOMIZATION_TABLE_NAME);
        $this->platy_service = PlatysService::get_instance();
    }

    public static function get_instance() {
        return self::$instance ?? new CustomizationsLoader(); 
    }

    public function has_permission($user) {
        return current_user_can($user) || empty($user);
    }

    public function load() {
        $customizations = $this->data_service->get(['name' => '%', '_compare' => 'like'], false);
        foreach($customizations as $customization) {
            if(!$this->has_permission($customization['user'])) {
                continue;
            }

            if($customization['active'] ) {
                try {
                    eval($customization['code']);
                }catch(\Throwable $e){
                    $this->logger->log_general("customization exception for customization " . $customization['name'], self::LOG_TYPE);
                    $this->logger->log_exception($e, self::LOG_TYPE);
                }
            }
        }
    }

}
