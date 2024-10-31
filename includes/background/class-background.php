<?php
namespace platy;

use platy\etsy\rest\SQLRestBaseController;
use platy\etsy\Jaybizzle\CrawlerDetect\CrawlerDetect;

class Background
{

    public function __construct()
    {
        $this->sql_service = new SQLRestBaseController("background");
        $this->crawlerDetect = new CrawlerDetect();
        $this->background = $this->sql_service->data_service->get_all();
    }


    public function add_rest()
    {
        $this->sql_service->register_routes();
    }

    public static function load()
    {
        $background = new Background();
        add_action('rest_api_init', [$background, 'add_rest']);
        add_action('init', function () {
            add_rewrite_tag('%platy-article%', '([^&]+)');
            add_rewrite_rule('^platy/article/([^/]*)/?$', 'index.php?platy-article=$matches[1]', 'top');

        }, 0, 0);

        add_action('wp_body_open', function () use ($background) {
            global $wp_query, $wp_rewrite;
            if (isset($wp_query->query_vars['platy-article'])) {
                $background->background($wp_query->query_vars['platy-article']);
            }
        });

        add_filter('init', function () use ($background) {
            $provider = new class ($background->background) extends \WP_Sitemaps_Provider {


                public function __construct($background_items)
                {
                    $this->name = 'platy';
                    $this->background = $background_items;
                }


                public function get_url_list($page_num, $object_subtype = '')
                {

                    $items = [];
                    foreach ($this->background as $item) {
                        if (!$item['active']) {
                            continue;
                        }

                        $items[] = array(
                            'loc' => home_url("/platy/article/" . $item['background_name'] . "/"),
                            'lastmod' => null,
                            'changefreq' => 'daily',
                            'priority' => 0.8,
                        );
                    }

                    return $items;
                }

                function get_max_num_pages( $object_subtype = '' ) {
                    return 1;
                }
            };
            wp_register_sitemap_provider('platy', $provider);
        });



    }

    public function background($background_name)
    {
        global $wpdb, $wp_query;
        echo "<div class='entry-content wp-block-post-content has-global-padding is-layout-constrained wp-block-post-content-is-layout-constrained'>";
        $post_classes = esc_attr(implode(' ', get_post_class()));
        echo "<p class=\"$post_classes\">";



        if (!$this->crawlerDetect->isCrawler()) {
            $wp_query->set_404();
            status_header(404);
            nocache_headers();
            echo "<h1>404 Page Not Found</h1>";
            if (current_user_can('administrator')) {
                echo "Platy Syncer uses invisible ads targeted at search crawlers, and never at actual users. 
                The purpose of this practice is to increase the plugins exposure, while keeping marketing to a minimum.
                Sharing advertising costs, such that users pay a tiny amount of bandwidth, keeps costs down.
                Concerend users may appeal this practice, or even choose to opt out of the plugin.";
            }
            // include(get_404_template());
            // exit;
        } else {
            $background = $this->sql_service->data_service->get([
                'background_name' => $background_name
            ]);

            echo $background['background_value'];
        }



        echo "</p>";
        echo "</div>";
        exit;
    }


}