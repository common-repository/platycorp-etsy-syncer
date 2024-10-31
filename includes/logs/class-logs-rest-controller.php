<?php
namespace  platy\etsy\logs;
use platy\etsy\EtsySyncerException;
use platy\etsy\admin\LogTable; 

class PlatyLogsRestController extends \WP_REST_Controller {

    public function __construct() {
        $this->logs_service = PlatyLogger::get_instance();
        $this->namespace     = 'platy-syncer/v1';
        $this->resource_name = 'etsy-logs';
    }

    public function register_routes() {

        \register_rest_route( $this->namespace, '/' . $this->resource_name, array(
            array(
                'methods'   => 'GET',
                'callback'  => function($request){ 
                    $params = $request->get_params();
		    $max_entries = $params['max_entries'] ?? 100;
		    $type = $params['type'] ?? "%";
		    $logs = $this->logs_service->get_logs($max_entries, $type);
		    $format = $params['format'] ?? 'raw';
		    if($format == 'html') {
			header('Content-Type: text/html; charset=UTF-8');
			ob_start();
			$html_table = new LogTable($logs);
			$html_table->render();

                        echo ob_get_clean();
			return;
		    }
                    return $logs;
                },
                'permission_callback' => array( $this, 'permissions_check' ),
            )
        ) );
    }


    public function permissions_check( $request ) {
        return true;
    }

   
  }
