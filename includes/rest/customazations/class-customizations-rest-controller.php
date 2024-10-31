<?php

namespace platy\etsy\rest;
use platy\etsy\PlatysService;

class CustomizationsRestController extends SQLRestBaseController {

    public function __construct($tbl_name) {
        parent::__construct($tbl_name, [PlatysService::get_instance(), 'check_rest_request'], false);
        $this->resource_name = "customizations";
    }

    public function register_routes() {
        parent::register_routes();
        \register_rest_route( $this->namespace, '/' . $this->resource_name . '/(?P<name>[a-zA-Z0-9_]+)', array(
            // Here we register the readable endpoint for collections.
            array(
                'methods'   => ['POST', 'PUT'],
                'callback'  => function($request) {
                    $code = $request->get_body();
                    $name = $request->get_url_params()['name'];
                    $this->update_data_item(['code' => $code], ['name' => $name]);
                },
                'permission_callback' => $this->permission_callback
            )
        ) );
    }
}
