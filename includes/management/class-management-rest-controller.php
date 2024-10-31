<?php
namespace platy\management;
use platy\etsy\EtsyDataService;
use platy\etsy\EtsySyncer;
use platy\etsy\api\EtsyApi;
use platy\etsy\PlatysService;
class PlatyManagementController extends \WP_REST_Controller{

    

    function __construct() {
        $this->data_service = EtsyDataService::get_instance();
        $this->namesapce = 'platy-syncer/v1';
        $this->resource_name = 'management';
        $this->permission_callback = [PlatysService::get_instance(), 'check_rest_request'];
    }

	public function register_routes() {
            \register_rest_route($this->namesapce , "/" . $this->resource_name . "/woocommerce", [
                [
                    'callback' => function($request) {
                        $params = $request->get_json_params();
                        $request_params = $params['params'];
                        return $this->use_woocommerce_rest($request, $params['route'], $params['method'] ?? 'GET', $request_params);
                    },
                    'methods' => ['GET', 'POST', 'DELETE', 'PUT'],
                    'permission_callback' => $this->permission_callback
                ]
            ]
            );

            \register_rest_route($this->namesapce , "/" . $this->resource_name . "/etsy", [
                    'callback' => function($request) {
                        $params = $request->get_json_params();
                        return $this->use_etsy_api($params['method'], $params['params'] ?? [], $params['data'] ?? []);
                    },
                    'methods' => ['POST'],
                    'permission_callback' => $this->permission_callback
                ]
            );

            \register_rest_route($this->namesapce , "/" . $this->resource_name . "/apply_filters/(?P<filter>[a-zA-Z0-9_]+)", [
                    'callback' => function($request) {
                        $params = $request->get_json_params();
                        $filter = $request->get_url_params()['filter']; 
                        return $this->apply_filters($filter, $params ?? []);
                    },
                    'methods' => ['POST'],
                    'permission_callback' => $this->permission_callback
                ]
            );

            \register_rest_route($this->namesapce , "/" . $this->resource_name . "/do_action/(?P<action>[a-zA-Z0-9_]+)", [
                    'callback' => function($request) {
                        $params = $request->get_json_params();
                        $action = $request->get_url_params()['action']; 
                        $this->do_action($action, $params ?? []);
                    },
                    'methods' => ['POST'],
                    'permission_callback' => $this->permission_callback
                ]
            );
	}

    private function apply_filters($action, $parameters) {
        array_unshift($parameters, $action);
        return call_user_func_array('apply_filters', $parameters);
	}

    private function do_action($action, $parameters) {
        array_unshift($parameters, $action);
        call_user_func_array('do_action', $parameters);
	}

	public function use_etsy_api($method, $params, $data =[]) {
        if(!$this->data_service->has_current_shop()){
          return "No authenticaed shop";  
        }

        $token = $this->data_service->get_token_credentials();
        $legacy_token = $this->data_service->get_shop_legacy_token();
        $api_key = EtsySyncer::API_KEY;
        $this->api = EtsyApi::get_instance($api_key, $token, $legacy_token, "ddd");
        return $this->api->$method([
            'params' => $params,
            'data' => $data
        ]);
	}

	public function use_woocommerce_rest($request, $route, $method = 'GET', $request_params = []) {

	    add_filter('woocommerce_rest_check_permissions', '__return_true');
        
        $wp_request = new \WP_REST_Request($method, $route);
        $wp_request->set_body(json_encode($request_params, JSON_UNESCAPED_SLASHES));
        $wp_request->set_query_params($request->get_query_params());
        
        
        $wp_request->set_url_params($request->get_url_params());
        $wp_request->set_headers($request->get_headers());
	    return rest_do_request($wp_request);

	}

}
