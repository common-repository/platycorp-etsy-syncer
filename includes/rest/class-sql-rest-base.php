<?php
namespace platy\etsy\rest;
use platy\etsy\data\SQLDataService;
use platy\etsy\PlatysService;


class SQLRestBaseController extends \WP_REST_Controller {

    public function __construct($tbl_name, $permission_callback = null, $strict_post = false, $table_prefix = "plty_") {
        $permission_callback = $permission_callback ?? [PlatysService::get_instance(), 'check_rest_request'];
        $this->namespace     = 'platy-syncer/v1/sql';
        $this->resource_name = $tbl_name;
        $this->data_service = new SQLDataService("$table_prefix$tbl_name");
        $this->permission_callback = $permission_callback;
        $this->strict_post = $strict_post;
    }

    protected function add_data_item($item) {
        try {
            $this->data_service->insert($item); 
        } catch(\RuntimeException $e) {
            return new \WP_Error($e->getMessage());
        }
    }

    protected function update_data_item($item, $where) {

        try {
            $this->data_service->update($item, $where);
        }catch (\RuntimeException $e) {
            return new \WP_Error($e->getMessage());
        }

    }

    protected function delete_data_item($item) {
        try {
            $this->data_service->delete($item);
        } catch(\RuntimeException $e) {
            return new \WP_Error($e->getMessage());
        }
    }

    public function register_routes() {

        \register_rest_route( $this->namespace, '/' . $this->resource_name, array(
            array(
                'methods'   => 'GET',
                'callback'  => function($request){ 
                    $params = $request->get_query_params();
                    $single = $params['_single'];
                    return $this->data_service->get($params, $single);
                },
                'permission_callback' => $this->permission_callback,
            )
        ) );

        \register_rest_route( $this->namespace, '/' . $this->resource_name, array(
            // Here we register the readable endpoint for collections.
            array(
                'methods'   => 'POST',
                'callback'  => function($request){ 
                    $params = $request->get_json_params();
                    $item = $params['_item'] ?? $params;
                    !empty($params['_where']) ? throw new \Exception("This is not PUT") : "";
                    $this->add_data_item($item);
                },
                'permission_callback' => $this->permission_callback,
            )
        ) );

        \register_rest_route( $this->namespace, '/' . $this->resource_name, array(
            // Here we register the readable endpoint for collections.
            array(
                'methods'   => 'PUT',
                'callback'  => function($request) {
                    $params = $request->get_json_params();
                    $item = $params['_item'] ?? $params;
                    $where = $params['_where'] ?? throw new \Exception("No _where");
                    $this->update_data_item($item, $where);
                },
                'permission_callback' => $this->permission_callback,
            )
        ) );


        \register_rest_route( $this->namespace, '/' . $this->resource_name, array(
            // Here we register the readable endpoint for collections.
            array(
                'methods'   => 'DELETE',
                'callback'  => function($request) {
                    $item = $request->get_query_params();
                    $this->delete_data_item($item);
                },
                'permission_callback' => $this->permission_callback,
            )
        ) );
        
    }

  }
