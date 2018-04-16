<?php
/*
Plugin Name: Quick View Woo Product
Plugin URI: http://divertdigital.com
Description: Allows to add Quick view button to archive products, and display product in popup
Version: 1.0.0
Author: divertdigital.com
Author URI: http://divertdigital.com
*/

// don't load directly
if (!defined( 'ABSPATH')) {
	die( '-1' );
}

if (class_exists( 'QuickViewWooProduct', false ) ) {
    QuickViewWooProduct::getInstance();
    return;
}

class QuickViewWooProduct {
    //region Singleton
    /** @var QuickViewWooProduct */
    private static $instance;

    /** @return QuickViewWooProduct */
    public static function getInstance(){
        if (QuickViewWooProduct::$instance == null)
            QuickViewWooProduct::$instance = new QuickViewWooProduct();
        return QuickViewWooProduct::$instance;
    }

    /** @return QuickViewWooProduct */
    private function __construct(){
        $this->init();
    }
    //endregion

    public function init()
    {
        if (is_plugin_active( 'woocommerce/woocommerce.php')) {

            // Register script/style
            add_action('wp_enqueue_scripts', [$this, 'quick_view_enqueue_script']);

            // Register ajax action
            add_action('wp_ajax_qvwp_aaction', array($this, 'qvwp_aaction'));
            add_action('wp_ajax_nopriv_qvwp_aaction', array($this,'qvwp_aaction'));

            // Add button to archive and snippet in footer
            add_action('woocommerce_after_shop_loop_item', [$this, 'add_a_quick_view_button'], 20);
            add_action('wp_footer', [$this, 'add_a_quick_view_popup'], 20);
        }
    }

    function quick_view_enqueue_script() {
        wp_register_script('quick_view_woo_product_js', plugin_dir_url( __FILE__ ) . 'assets/js/qvwp.js', ['jquery']);
        wp_register_style('quick_view_woo_product_css', plugin_dir_url( __FILE__ ) . 'assets/css/qvwp.css');
        wp_enqueue_script( 'quick_view_woo_product_js' );
        wp_enqueue_style('quick_view_woo_product_css');


        if (1 || current_theme_supports('wc-product-gallery-zoom')) {
            wp_enqueue_script('zoom');
        }
        if (1 || current_theme_supports('wc-product-gallery-slider')) {
            wp_enqueue_script('flexslider');
        }
        if (1 || current_theme_supports('wc-product-gallery-lightbox')) {
            wp_enqueue_script('photoswipe-ui-default');
            wp_enqueue_style('photoswipe-default-skin');
            add_action('wp_footer', 'woocommerce_photoswipe');
        }
        wp_enqueue_script('wc-add-to-cart-variation');
        wp_enqueue_script('wc-single-product');
    }

    function qvwp_aaction(){
        if (empty($_REQUEST['id'])){
            echo 'Invalid product selection!';
            die;
        }
        $id = $_REQUEST['id'];
        echo $this->quick_view_render($id);
        die;
    }

    function quick_view_render($id) {
        if ( empty( $id ) ) {
            return '';
        }
        $atts = array();

        $atts['id'] = $id;

        if (!isset($atts['id']) && !isset($atts['sku'])) {
            return '';
        }

        $args = array(
            'posts_per_page'      => 1,
            'post_type'           => 'product',
            'post_status'         => 'publish',
            'ignore_sticky_posts' => 1,
            'no_found_rows'       => 1,
        );

        if ( isset( $atts['id'] ) ) {
            $args['p'] = absint( $atts['id'] );
        }

        // Don't render titles if desired.
        if ( isset( $atts['show_title'] ) && ! $atts['show_title'] ) {
            remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
        }

        $single_product = new \WP_Query( $args );

        $preselected_id = '0';

        // For "is_single" to always make load comments_template() for reviews.
        $single_product->is_single = true;

        ob_start();

        global $wp_query;

        // Backup query object so following loops think this is a product page.
        $previous_wp_query = $wp_query;
        // @codingStandardsIgnoreStart
        $wp_query          = $single_product;
        // @codingStandardsIgnoreEnd

        remove_action( 'woocommerce_after_single_product_summary','woocommerce_output_related_products', 20 );

        while ( $single_product->have_posts() ) {
            $single_product->the_post()
            ?>
            <div class="single-product clearfix" data-product-page-preselected-id="<?php echo esc_attr( $preselected_id ); ?>">
                <?php wc_get_template_part( 'content', 'single-product' ); ?>
            </div>
            <?php
        }

        // Restore $previous_wp_query and reset post data.
        // @codingStandardsIgnoreStart
        $wp_query = $previous_wp_query;
        // @codingStandardsIgnoreEnd
        wp_reset_postdata();

        // Re-enable titles if they were removed.
        if ( isset( $atts['show_title'] ) && ! $atts['show_title'] ) {
            add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
        }

        add_action( 'woocommerce_after_single_product_summary','woocommerce_output_related_products', 20 );

        return '<div class="woocommerce">' . ob_get_clean()
            . "<script>
            jQuery('.woocommerce-product-gallery').each(function(){if(jQuery.isFunction(jQuery.fn.wc_product_gallery)){jQuery(this).wc_product_gallery();}});
            jQuery(document).find('#productPopup').find('li#tab-title-description a').click();            
            jQuery(document).find('#productPopup').find('.variations_form select').change();
            if(jQuery.isFunction(jQuery.fn.wc_variation_form)){jQuery(document).find('#productPopup').find('.variations_form').wc_variation_form();};
            </script>"
            . '</div>';
    }

    function add_a_quick_view_button(){
        global $product;

        echo '
        <div class="qvwp-button">
            <a class="button qvwp-open-single-product" data-id="'.$product->get_id().'" href="#">' . __('Quick view') . '</a>
        </div>
        ';
    }

    function add_a_quick_view_popup(){
        echo'
        <!-- The Modal -->
        <div id="QuickViewProductPopup" class="modal popup" data-url="'.admin_url('admin-ajax.php').'">
            <!-- Modal content -->
            <div class="modal-content">
            </div>
        </div>
        ';
    }
}

QuickViewWooProduct::getInstance();


