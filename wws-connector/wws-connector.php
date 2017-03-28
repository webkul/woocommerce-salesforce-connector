<?php
/**
 * Plugin Name: WooCommerce Salesforce Connector
 * Plugin URI: http://webkul.com
 * Description: Woordpress Wooocommerce Salesforce Connector
 * Version: 1.0.0
 * Author: Webkul
 * Author URI: http://webkul.com
 * Requires at least: 4.1
 * Tested up to: 4.6
 *
 * Text Domain: webkul
 * Domain Path: /i18n/languages/
 *
 * @package WooCommerce Salesforce Connector
 * @category Core
 * @author Webkul
 */

require_once trailingslashit(dirname(__FILE__)) . 'includes/wpdk.php';
if (!class_exists('WWSCONNECTOR')) {
    final class WWSCONNECTOR extends WPDKWordPressPlugin
    {

        public $saleforceConnection;

        /**
         * Create an instance of WWSCONNECTOR class
         *
         * @brief Construct
         *
         * @param string $file The main file of this plugin. Usually __FILE__
         *
         * @return WWSCONNECTOR object instance
         */
        public function __construct($file)
        {
            parent::__construct($file);
            // Build my own internal defines
            $this->defines();
            // Build environment of plugin autoload of internal classes - this is ALWAYS the first thing to do
            $this->registerClasses();
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'wws_plugin_action_links'));
            add_action('admin_notices1', 'my_admin_notice');
        }

        /**
         * Adds plugin action links
         *
         * @since 1.0.0
         */
        public function wws_plugin_action_links($links)
        {
            $setting_link = $this->wws_get_setting_link();

            $plugin_links = array(
                '<a href="' . $setting_link . '">' . __('Settings', 'woocommerce-salesforce-connector') . '</a>',
                '<a href="http://webkul.com/blog/wordpress-woocommerce-salesforce-connector/">' . __('Docs', 'woocommerce-salesforce-connector') . '</a>',
                '<a href="https://webkul.com/ticket/index.php">' . __('Support', 'woocommerce-salesforce-connector') . '</a>',
            );
            return array_merge($plugin_links, $links);
        }

        /**
         * Get setting link.
         *
         * @since 1.0.0
         *
         * @return string Setting link
         */
        public function wws_get_setting_link()
        {
            return admin_url('admin.php?page=wws_connector-submenu-8');
        }
        /**
         * Register all autoload classes
         *
         * @brief Autoload classes
         */
        private function registerClasses()
        {
            $includes = array(
                $this->classesPath . 'admin/WWSAdminMenu.php'                 => 'WWSAdminMenu',
                $this->classesPath . 'preferences/preferences.php'            => 'PreferencesModel',
                $this->classesPath . 'views/wws-ViewProductsController.php'   => 'WWSProductsViewController',
                $this->classesPath . 'views/wws-ViewCategoriesController.php' => 'WWSCategoriesViewController',
                $this->classesPath . 'views/wws-ViewUsersController.php'      => 'WWSUsersViewController',
                $this->classesPath . 'views/wws-ViewOrdersController.php'     => 'WWSOrdersViewController',
                $this->classesPath . 'views/wws-ViewsettingsController.php'   => 'WWSSetingsViewController',
                //$this->classesPath . 'other/about-viewcontroller.php'                  => 'AboutViewController',
            );
            $this->registerAutoloadClass($includes);
        }

        /**
         * Include the external defines file
         *
         * @brief Defines
         */
        private function defines()
        {
            include_once 'defines.php';
        }

        /**
         * Catch for activation. This method is called one shot.
         *
         * @brief Activation
         */
        public function activation()
        {
            // When you update your plugin it is re-activate. In this place you can update your preferences
            PreferencesModel::init()->delta();
            $this->create_wws_tables();
        }

        /**
         * Catch for admin
         *
         * @brief Admin backend
         */
        public function admin()
        {
            WWSAdminMenu::init($this);
        }

        /**
         * Init your own preferences settings
         *
         * @brief Preferences
         */
        public function preferences()
        {
            PreferencesModel::init();
        }

        /**
         * Catch for deactivation. This method is called when the plugin is deactivate.
         *
         * @brief Deactivation
         */
        public function deactivation()
        {
            global $wpdb;
            $wpdb->hide_errors();
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}wws_products");
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}wws_categories");
            // To override
        }

        private function create_wws_tables()
        {
            global $wpdb;
            $wpdb->hide_errors();
            $collate = '';
            if ($wpdb->has_cap('collation')) {
                if (!empty($wpdb->charset)) {
                    $collate .= "DEFAULT CHARACTER SET $wpdb->charset";
                }
                if (!empty($wpdb->collate)) {
                    $collate .= " COLLATE $wpdb->collate";
                }
            }
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            $wws_tables = "
            CREATE TABLE {$wpdb->prefix}wws_products (
            id bigint(20) NOT NULL auto_increment,
            sf_product_id varchar(255) NOT NULL,
            woo_product_id bigint(20) NOT NULL,
            status tinyint(1) NULL,
            sync_time varchar(150) NOT NULL,
            PRIMARY KEY  (id)
            )
            $collate;
            CREATE TABLE {$wpdb->prefix}wws_categories (
            id bigint(20) NOT NULL auto_increment,
            sf_category_id varchar(255) NOT NULL,
            woo_category_id bigint(20) NOT NULL,
            status tinyint(1) NULL,
            sync_time varchar(150) NOT NULL,
            PRIMARY KEY  (id)
            )$collate;";
            dbDelta($wws_tables);
        }

        public static function insertWwsTable($table_name, $dataArray)
        {
            global $wpdb;
            $table_name = "{$wpdb->prefix}$table_name";
            $wpdb->insert($table_name, $dataArray);
            return $wpdb->insert_id;
        }
        public static function updateWwsTable($table_name, $dataArray, $updateKeyArray)
        {
            global $wpdb;
            $table_name = "{$wpdb->prefix}$table_name";
            $wpdb->update($table_name, $dataArray, $updateKeyArray);
        }
        public static function getFieldOfWwsTable($table_name, $fieldName, $conditionfield, $conditionvalue)
        {
            global $wpdb;
            $table_name = "{$wpdb->prefix}$table_name";
            return $wpdb->get_var("select $fieldName from $table_name where $conditionfield=$conditionvalue");
        }
        public static function getFieldOfWwsTableWithMultipleCondition($table_name, $fieldName, $conditiondata)
        {
            global $wpdb;
            $table_name = "{$wpdb->prefix}$table_name";
            return $wpdb->get_var("select $fieldName from $table_name where $conditiondata");
        }
        /*
        checkIfPostExists: Returns if post id exists or not
         */
        public static function checkIfPostExists($post_id = 0)
        {
            global $wpdb;
            if (!$post_id) {
                return false;
            }
            $post_exists = $wpdb->get_row("SELECT * FROM $wpdb->posts WHERE id = '" . $post_id . "'", 'ARRAY_A');
            if ($post_exists) {
                return true;
            }
            return false;
        }

        public static function wws_connector_get_existing_sf_product_ids()
        {
            global $wpdb;
            $table_name1         = $wpdb->prefix . 'wws_products';
            $wooProduct_data_arr = $wpdb->get_results("SELECT sf_product_id FROM $table_name1");
            $old_ids             = ("('01t28000001sy0YAAQ','01t28000001sy0TAAQ','01t28000001sy1HAAQ')");
            // $wooProduct_data = array();
            $wooProduct_data = "(";
            for ($i = 0; $i < count($wooProduct_data_arr); $i++) {
                $wooProduct_data .= "'" . $wooProduct_data_arr[$i]->sf_product_id . "'";
                if ($i != count($wooProduct_data_arr) - 1) {
                    $wooProduct_data .= ",";
                }
            }
            $wooProduct_data .= ")";
            return $wooProduct_data;
        }

        public function getAllCategoryIds()
        {
            global $wpdb;
            $all_cat = $wpdb->get_col("SELECT trms.term_id as cat_id FROM $wpdb->terms as trms INNER JOIN $wpdb->term_taxonomy trms_tax ON trms.term_id = trms_tax.term_id  WHERE trms_tax.taxonomy = 'product_cat'");
            /*$args = array(
            'fields'         => 'ids',
            'posts_per_page' => -1,
            'post_type'      => 'product',
            'post_status'    => 'publish',
            );*/
            $allCategories = $all_cat;
            echo json_encode($allCategories);
            exit;
        }
        public function getAllProductsIds()
        {
            $args = array(
                'fields'         => 'ids',
                'posts_per_page' => -1,
                'post_type'      => 'product',
                'post_status'    => 'publish',
            );
            $allProducts = get_posts($args);
            echo json_encode($allProducts);
            exit;
        }
        public static function getsfTotalCategories()
        {
            $saleforceConnection = WWSCONNECTOR::createConnection();
            $username            = esc_attr(get_option('wwsconnector_username'));
            $pass                = esc_attr(get_option('wwsconnector_password'));
            $token               = esc_attr(get_option('wwsconnector_token'));
            try {
                $saleforceConnection->login($username, $pass . $token);
                $total_count = $saleforceConnection->query('SELECT COUNT() FROM webkul_wws__woo_commerce_categories__c');
                if (!empty($total_count->size)) {
                    echo json_encode(array('total_count' => $total_count->size));
                    exit;
                } else {
                    echo json_encode(array('total_count' => 0));
                    exit;
                }
            } catch (Exception $e) {
                header("HTTP/1.0 404 Not Found");
                $error = $e->getMessage();
                echo $error;
                exit;
            }
        }
        public static function getsfTotalProducts()
        {
            $saleforceConnection = WWSCONNECTOR::createConnection();
            $username            = esc_attr(get_option('wwsconnector_username'));
            $pass                = esc_attr(get_option('wwsconnector_password'));
            $token               = esc_attr(get_option('wwsconnector_token'));
            try {
                $saleforceConnection->login($username, $pass . $token);
                $total_count = $saleforceConnection->query('SELECT count() FROM Product2');
                if (!empty($total_count->size)) {
                    echo json_encode(array('total_count' => $total_count->size));
                    exit;
                } else {
                    echo json_encode(array('total_count' => 0));
                    exit;
                }
            } catch (Exception $e) {
                header("HTTP/1.0 404 Not Found");
                $error = $e->getMessage();
                echo $error;
                exit;
            }
        }
        /*------------------- import function ------------------ */
        public static function importProduct()
        {
            $limit = 20;
            if (!empty($_REQUEST['limit'])) {
                $limit = $_REQUEST['limit'];
            }
            $cols              = 'Description,IsActive,Name,ProductCode,webkul_wws__woo_Stock__c,webkul_wws__woo_Product_SKu__c,webkul_wws__woo_Post_type__c,webkul_wws__woo_Thumbnail_ID__c,webkul_wws__woo_Post_Title__c,webkul_wws__woo_product_width__c,webkul_wws__woo_product_weight__c,webkul_wws__woo_Post_Name__c,webkul_wws__woo_post_Mime_Type__c,webkul_wws__woo_Post_id__c,webkul_wws__woo_product_height__c,webkul_wws__woo_Post_excerpt__c,webkul_wws__woo_Post_Date__c,webkul_wws__woo_Post_Content__c,webkul_wws__woo_Post_Author__c,webkul_wws__woo_Menu_Order__c,webkul_wws__woo_Comment_Status__c,webkul_wws__woo_product_length__c,webkul_wws__woo_comment_count__c';
            $offset            = $_REQUEST['offset'];
            $get_product_query = 'SELECT Id,' . $cols . ' FROM Product2 ORDER BY Id ASC  LIMIT ' . $limit . ' OFFSET ' . $offset;
            try {
                $processed_item      = array('total' => 0, 'updated' => 0, 'added' => 0);
                $saleforceConnection = WWSCONNECTOR::createConnection();
                $username            = esc_attr(get_option('wwsconnector_username'));
                $pass                = esc_attr(get_option('wwsconnector_password'));
                $token               = esc_attr(get_option('wwsconnector_token'));
                $saleforceConnection->login($username, $pass . $token);
                $sfproduct = $saleforceConnection->query($get_product_query);
                foreach ($sfproduct->records as $product) {
                    $processed_item['total'] += 1;
                    if (isset($product->webkul_wws__woo_Post_excerpt__c)) {
                        $productArrray['post_excerpt'] = $product->webkul_wws__woo_Post_excerpt__c;
                    } else {
                        if (isset($product->Description)) {
                            $productArrray['post_excerpt'] = $product->Description;
                        }
                    }
                    if (isset($product->webkul_wws__woo_Post_id__c)) {
                        $productArrray['ID'] = $product->webkul_wws__woo_Post_id__c;
                        if (!WWSCONNECTOR::checkIfPostExists($productArrray['ID'])) {
                            unset($productArrray['ID']);
                        }
                    }
                    if (isset($product->webkul_wws__woo_post_Mime_Type__c)) {
                        $productArrray['post_mime_type'] = $product->webkul_wws__woo_post_Mime_Type__c;
                    }

                    if (isset($product->webkul_wws__woo_Post_Name__c)) {
                        $productArrray['post_name'] = $product->webkul_wws__woo_Post_Name__c;
                    } else {
                        $productArrray['post_name'] = $product->Name;
                    }

                    if (isset($product->webkul_wws__woo_Post_type__c)) {
                        $productArrray['post_type'] = $product->webkul_wws__woo_Post_type__c;
                    } else {
                        $productArrray['post_type'] = 'product';
                    }
                    if (isset($product->webkul_wws__woo_Post_Author__c)) {
                        $productArrray['post_author'] = $product->webkul_wws__woo_Post_Author__c;
                    } else {
                        $productArrray['post_author'] = '1';
                    }
                    if (isset($product->IsActive)) {
                        $productArrray['post_status'] = 'publish';
                    }

                    if (isset($product->webkul_wws__woo_Post_Content__c)) {
                        $productArrray['post_content'] = $product->webkul_wws__woo_Post_Content__c;
                    }

                    if (isset($product->webkul_wws__woo_Post_Title__c)) {
                        $productArrray['post_title'] = $product->webkul_wws__woo_Post_Title__c;
                    } else {
                        $productArrray['post_title'] = $product->Name;
                    }

                    if (isset($product->webkul_wws__woo_Post_Date__c)) {
                        $productArrray['post_date'] = $product->webkul_wws__woo_Post_Date__c;
                    }

                    if (isset($product->webkul_wws__woo_Menu_Order__c)) {
                        $productArrray['menu_order'] = $product->webkul_wws__woo_Menu_Order__c;
                    }

                    if (isset($product->webkul_wws__woo_Comment_Status__c)) {
                        $productArrray['comment_status'] = $product->webkul_wws__woo_Comment_Status__c;
                    }

                    if (isset($product->comment_count)) {
                        $productArrray['comment_count'] = $product->comment_count;
                    }

                    // Insert the post into the database
                    if (isset($productArrray['ID']) && $productArrray['ID'] != 0) {
                        $product_ID = wp_update_post($productArrray);
                    } else {
                        $product_ID = wp_insert_post($productArrray);
                    }

                    if ($product_ID) {
                        $allSalesforceProduct = $saleforceConnection->query("SELECT Id, UnitPrice FROM PricebookEntry where Product2Id='" . $product->Id . "' and Pricebook2Id='" . esc_attr(get_option('wwsconnector_pricebook')) . "'");
                        $UnitPrice            = 0;
                        if (!empty($allSalesforceProduct->records)) {
                            $UnitPrice = $allSalesforceProduct->records[0]->UnitPrice;
                        }
                        add_post_meta($product_ID, '_regular_price', $UnitPrice);
                        add_post_meta($product_ID, '_sale_price', $UnitPrice);
                        add_post_meta($product_ID, '_price', $UnitPrice);
                        if (isset($product->webkul_wws__woo_Stock__c)) {
                            add_post_meta($product_ID, '_stock', $product->webkul_wws__woo_Stock__c);
                            if ($product->webkul_wws__woo_Stock__c > 0) {
                                add_post_meta($product_ID, '_manage_stock', 'yes');
                                add_post_meta($product_ID, '_stock_status', 'instock');
                            }
                        }
                        if (isset($product->webkul_wws__woo_Product_SKu__c)) {
                            add_post_meta($product_ID, '_sku', $product->webkul_wws__woo_Product_SKu__c);
                        }

                        if (isset($product->webkul_wws__woo_Thumbnail_ID__c)) {
                            add_post_meta($product_ID, '_thumbnail_id', $product->webkul_wws__woo_Thumbnail_ID__c);
                        }

                        if (isset($product->webkul_wws__woo_product_weight__c)) {
                            add_post_meta($product_ID, '_weight', $product->webkul_wws__woo_product_weight__c);
                        }

                        if (isset($product->webkul_wws__woo_product_length__c)) {
                            add_post_meta($product_ID, '_length', $product->webkul_wws__woo_product_length__c);
                        }

                        if (isset($product->webkul_wws__woo_product_width__c)) {
                            add_post_meta($product_ID, '_width', $product->webkul_wws__woo_product_width__c);
                        }

                        if (isset($product->webkul_wws__woo_product_height__c)) {
                            add_post_meta($product_ID, '_height', $product->webkul_wws__woo_product_height__c);
                        }

                        // $wwsproduct_id=WWSCONNECTOR::getFieldOfWwsTable('wws_products',$fieldName='id',$conditionfield='sf_product_id',$conditionvalue="'".$product->Id."'");
                        $wwsproduct_id = WWSCONNECTOR::getFieldOfWwsTableWithMultipleCondition('wws_products', $fieldName = 'id', $conditiondata = 'sf_product_id="' . $product->Id . '"');
                        if ($wwsproduct_id) {
                            WWSCONNECTOR::updateWwsTable('wws_products', array('sf_product_id' => $product->Id, 'sync_time' => time()), array('woo_product_id' => $product_ID));
                            $processed_item['updated'] += 1;
                        } else {
                            $wwsObject                 = new stdclass();
                            $wwsObject->id             = 0;
                            $wwsObject->sf_product_id  = $product->Id;
                            $wwsObject->woo_product_id = $product_ID;
                            $wwsObject->status         = '1';
                            $wwsObject->sync_time      = time();
                            WWSCONNECTOR::insertWwsTable('wws_products', (array) $wwsObject);
                            $processed_item['added'] += 1;
                        }
                    }
                }
                echo json_encode($processed_item);
                exit;
            } catch (Exception $e) {
                header("HTTP/1.0 404 Not Found");
                $error = $e->getMessage();
                echo $error;
                exit;
            }
        }
        /*------------------- import function end  ------------------ */

        /*------------------- syncronoze function start ------------------ */

        public static function syncronizeProduct()
        {
            try {
                $saleforceConnection = WWSCONNECTOR::createConnection();
                $username            = esc_attr(get_option('wwsconnector_username'));
                $pass                = esc_attr(get_option('wwsconnector_password'));
                $token               = esc_attr(get_option('wwsconnector_token'));
                $saleforceConnection->login($username, $pass . $token);
                WWSCONNECTOR::exportProduct();
                WWSCONNECTOR::importProduct();
                exit;
            } catch (Exception $e) {
                header("HTTP/1.0 404 Not Found");
                $error = $e->getMessage();
                echo $error;
                exit;
            }
        }
        public static function wws_get_product_categories($product_id)
        {
            global $wpdb;
            $query  = "SELECT t.*,tt.* FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON tt.term_id = t.term_id INNER JOIN $wpdb->term_relationships AS tr ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy IN ('product_cat') AND tr.object_id IN ($product_id)";
            $_terms = $wpdb->get_results($query);
            return $_terms;
        }
        /*------------------- syncronoze function end ------------------ */

        public static function exportMultipleProduct()
        {
            if (!isset($_REQUEST['product_ids'])) {
                echo "No product id";
                exit;
            }
            $processed_item = array('total' => 0, 'updated' => 0, 'added' => 0, 'category_synchronised' => false);
            foreach ($product_ids as $product_id) {
                $res = exportProduct($product_id);
                if ($res['added']) {
                    $processed_item['added'] += $res['added'];
                } elseif ($res['updated']) {
                    $processed_item['updated'] += $res['updated'];
                }
                if ($res['category_synchronised']) {
                    $processed_item['category_synchronised'] = true;
                }
                $processed_item['total'] += 1;
            }
            setcookie('wp_sf_notices', serialize($processed_item), strtotime('+1 day'));
            exit;
        }

        public static function exportProduct($product_id = 0, $direct_call = false)
        {
            $product_id = isset($_REQUEST['product_id']) ? $_REQUEST['product_id'] : $product_id;
            if (!isset($product_id)) {
                echo "No product id";
                exit;
            }
            try {
                $saleforceConnection = WWSCONNECTOR::createConnection();
                $username            = esc_attr(get_option('wwsconnector_username'));
                $pass                = esc_attr(get_option('wwsconnector_password'));
                $token               = esc_attr(get_option('wwsconnector_token'));
                $saleforceConnection->login($username, $pass . $token);
                $processed_item = array('total' => 0, 'sf_product_id' => '', 'updated' => 0, 'added' => 0, 'category_synchronised' => false, 'syncc_time' => '');
                $product        = get_post($product_id);
                $processed_item['total'] += 1;
                $product_ID_cat = $product->ID;
                $category_array = WWSCONNECTOR::wws_get_product_categories($product_ID_cat);
                if (!empty($category_array)) {
                    foreach ($category_array as $key => $category) {
                        $cat_id          = $category->term_id;
                        $exists_merge_id = WWSCONNECTOR::getFieldOfWwsTableWithMultipleCondition('wws_categories', $fieldName = 'id', $conditiondata = 'woo_category_id="' . $cat_id . '"');
                        if (empty($exists_merge_id) && !$exists_merge_id) {
                            $sf_category_export                      = WWSCONNECTOR::exportCategory(true, $cat_id);
                            $sf_category_export                      = json_decode($sf_category_export);
                            $processed_item['category_synchronised'] = true;
                        }
                    }
                }
                $regularPrice  = get_post_meta($product->ID, '_regular_price', true);
                $salesPrice    = get_post_meta($product->ID, '_sale_price', true);
                $productPrice  = get_post_meta($product->ID, '_price', true);
                $manageStock   = get_post_meta($product->ID, '_manage_stock', true);
                $productSku    = get_post_meta($product->ID, '_sku', true);
                $thumbnailId   = get_post_meta($product->ID, '_thumbnail_id', true);
                $productStock  = get_post_meta($product->ID, '_stock', true);
                $stockStatus   = get_post_meta($product->ID, '_stock_status', true);
                $productWeight = get_post_meta($product->ID, '_weight', true);
                $productLength = get_post_meta($product->ID, '_length', true);
                $productWidth  = get_post_meta($product->ID, '_width', true);
                $productHeight = get_post_meta($product->ID, '_height', true);

                $productDetails = new stdClass();
                // webkul_wws__woo_Stock__c
                $productDetails->IsActive = 1;
                if (isset($product->comment_count)) {
                    $productDetails->webkul_wws__woo_comment_count__c = $product->comment_count;
                }

                if (isset($product->comment_status)) {
                    $productDetails->webkul_wws__woo_Comment_Status__c = $product->comment_status;
                }

                if (isset($product->menu_order)) {
                    $productDetails->webkul_wws__woo_Menu_Order__c = $product->menu_order;
                }

                if (isset($product->post_author)) {
                    $productDetails->webkul_wws__woo_Post_Author__c = $product->post_author;
                }

                if (isset($product->post_content) && !empty($product->post_content)) {
                    $productDetails->webkul_wws__woo_Post_Content__c = $product->post_content;
                }

                if (isset($product->post_date)) {
                    $productDetails->webkul_wws__woo_Post_Date__c = $product->post_date;
                }

                if (isset($product->post_excerpt) && !empty($product->post_excerpt)) {
                    $productDetails->webkul_wws__woo_Post_excerpt__c = $product->post_excerpt;
                }

                if (isset($product->ID)) {
                    $productDetails->webkul_wws__woo_Post_id__c = $product->ID;
                }

                if (isset($product->post_mime_type) && !empty($product->post_mime_type)) {
                    $productDetails->webkul_wws__woo_post_Mime_Type__c = $product->post_mime_type;
                }

                if (isset($product->post_name) && !empty($product->post_name)) {
                    $productDetails->webkul_wws__woo_Post_Name__c = $product->post_name;
                    $productDetails->name                         = $product->post_name;
                }
                if (isset($product->post_title) && !empty($product->post_title)) {
                    $productDetails->webkul_wws__woo_Post_Title__c = $product->post_title;
                }

                if (isset($product->post_type)) {
                    $productDetails->webkul_wws__woo_Post_type__c = $product->post_type;
                }

                if (isset($productSku) && !empty($productSku)) {
                    $productDetails->webkul_wws__woo_Product_SKu__c = $productSku;
                }

                if ($manageStock == 'yes') {
                    if (isset($productStock) && !empty($productStock)) {
                        $productDetails->webkul_wws__woo_Stock__c = $productStock;
                    }

                }
                if (isset($thumbnailId) && !empty($thumbnailId)) {
                    $imageDoc = WWSCONNECTOR::uploadMediaSalesForce($thumbnailId);
                    if ($imageDoc[1] == '1') {
                        $productDetails->webkul_wws__woo_Thumbnail_ID__c = $imageDoc[0];
                    }
                }
                if (isset($productWidth) && !empty($productWidth)) {
                    $productDetails->webkul_wws__woo_product_width__c = $productWidth;
                }

                if (isset($productWeight) && !empty($productWeight)) {
                    $productDetails->webkul_wws__woo_product_weight__c = $productWeight;
                }

                if (isset($productHeight) && !empty($productHeight)) {
                    $productDetails->webkul_wws__woo_product_height__c = $productHeight;
                }

                if (isset($productLength) && !empty($productLength)) {
                    $productDetails->webkul_wws__woo_product_length__c = $productLength;
                }

                $productId = $saleforceConnection->upsert('Name', array($productDetails), 'Product2');
                if (isset($productId[0]->id)) {
                    // category mapping table = webkul_wws__Product_Category_Mapping__c

                    if (!empty($category_array)) {
                        foreach ($category_array as $key => $category) {
                            $cat_id                                                 = $category->term_id;
                            $sf_category_id                                         = WWSCONNECTOR::getFieldOfWwsTableWithMultipleCondition('wws_categories', $fieldName = 'sf_category_id', $conditiondata = 'woo_category_id="' . $cat_id . '"');
                            $mappingDetails                                         = new stdClass();
                            $mappingDetails->webkul_wws__Product__c                 = $productId[0]->id;
                            $mappingDetails->webkul_wws__woo_commerce_categories__c = $sf_category_id;
                            $MappingData                                            = $saleforceConnection->query("SELECT id FROM webkul_wws__Product_Category_Mapping__c WHERE webkul_wws__Product__c = '" . $productId[0]->id . "' AND webkul_wws__woo_commerce_categories__c = '" . $sf_category_id . "'");
                            if (empty($MappingData->records)) {
                                $mappingData = $saleforceConnection->create(array($mappingDetails), 'webkul_wws__Product_Category_Mapping__c');
                            }
                        }
                    }
                    $wwsproduct_id                = WWSCONNECTOR::getFieldOfWwsTable('wws_products', $fieldName = 'id', $conditionfield = 'woo_product_id', $conditionvalue = $product->ID);
                    $ttime_nn                     = time();
                    $date_format                  = get_option('date_format');
                    $time_format                  = get_option('time_format');
                    $processed_item['syncc_time'] = date($date_format . ' ' . $time_format, $ttime_nn);
                    if (isset($wwsproduct_id)) {
                        WWSCONNECTOR::updateWwsTable('wws_products', array('sf_product_id' => $productId[0]->id, 'sync_time' => $ttime_nn), array('woo_product_id' => $product->ID));
                        $processed_item['updated'] += 1;
                        $processed_item['sf_product_id'] = $productId[0]->id;
                    } else {
                        $processed_item['sf_product_id'] = $productId[0]->id;
                        $wwsObject                       = new stdclass();
                        $wwsObject->id                   = 0;
                        $wwsObject->sf_product_id        = $productId[0]->id;
                        $wwsObject->woo_product_id       = $product->ID;
                        $wwsObject->status               = '1';
                        $wwsObject->sync_time            = $ttime_nn;
                        WWSCONNECTOR::insertWwsTable('wws_products', (array) $wwsObject);
                        $processed_item['added'] += 1;
                    }

                    /**---------------------------product Price Book ------------------------**/

                    $standardPriceBookId = $saleforceConnection->query("SELECT Id FROM Pricebook2 where IsStandard=true");
                    $standardPriceBookId = $standardPriceBookId->records[0]->Id;

                    $catId = $saleforceConnection->query("SELECT Id FROM pricebookentry where Product2Id='" . $productId[0]->id . "'");
                    $ids   = array();
                    foreach ($catId as $createResult) {
                        array_push($ids, $createResult->Id);
                    }

                    if (!empty($ids)) {
                        $deleteResult = $saleforceConnection->delete($ids);
                    }

                    $priceBookEntry               = new stdClass();
                    $priceBookEntry->IsActive     = 1;
                    $priceBookEntry->Pricebook2Id = $standardPriceBookId;
                    if (isset($productId[0]->id) && !empty($productId[0]->id)) {
                        $priceBookEntry->Product2Id = $productId[0]->id;
                    }

                    if (isset($regularPrice) && !empty($regularPrice)) {
                        $priceBookEntry->UnitPrice = $regularPrice;
                    }

                    $response = $saleforceConnection->create(array($priceBookEntry), 'pricebookentry');

                    /**----------------------this code for add product in another price book -------------------**/
                    $priceBookId                  = esc_attr(get_option('wwsconnector_pricebook'));
                    $priceBookEntry               = new stdClass();
                    $priceBookEntry->IsActive     = 1;
                    $priceBookEntry->Pricebook2Id = $priceBookId;
                    if (isset($productId[0]->id) && $productId[0]->id) {
                        $priceBookEntry->Product2Id = $productId[0]->id;
                    }

                    if (isset($regularPrice) && !empty($regularPrice)) {
                        $priceBookEntry->UnitPrice = $regularPrice;
                    }

                    $response = $saleforceConnection->create(array($priceBookEntry), 'pricebookentry');
                } elseif (isset($productId[0]->errors)) {
                    header("HTTP/1.0 404 Not Found");
                    echo "<b>" . $productId[0]->errors[0]->statusCode . "</b>" . ":" . $productId[0]->errors[0]->message;
                    exit;
                }
                if ($direct_call) {
                    return json_encode($processed_item);
                }
                echo json_encode($processed_item);
                exit;
            } catch (Exception $e) {
                header("HTTP/1.0 404 Not Found");
                $error = $e->getMessage();
                echo $error;
                exit;
            }
        }
        public function my_admin_notice()
        {
            ?>
            <div class="updated">
                <p><?php _e('Updated!', 'my-text-domain');?></p>
            </div>
            <?php
}

        public static function uploadMediaSalesForce($thumbnailId)
        {
            try {
                $saleforceConnection = WWSCONNECTOR::createConnection();
                $username            = esc_attr(get_option('wwsconnector_username'));
                $pass                = esc_attr(get_option('wwsconnector_password'));
                $token               = esc_attr(get_option('wwsconnector_token'));
                $saleforceConnection->login($username, $pass . $token);
                $priceBookId  = esc_attr(get_option('wwsconnector_pricebook'));
                $sfDocumentId = esc_attr(get_option('wwsconnector_imagefolder'));
                $thumbnailUrl = WWSCONNECTOR::wp_get_attachment_url_for_sf($thumbnailId);
                $imageTitle   = '';
                if ($thumbnailUrl) {
                    $imageTitle = substr($thumbnailUrl, strrpos($thumbnailUrl, '/') + 1);
                }
                $mediaImage = new stdclass();
                if ($imageTitle) {
                    $mediaImage->Name = $imageTitle;
                }

                $documentFolder          = $saleforceConnection->query("SELECT Id,Name,Type FROM Folder where Type='Document'");
                $mediaImage->FolderId    = $sfDocumentId;
                $mediaImage->Description = '';
                $mediaImage->IsPublic    = true;
                if ($thumbnailUrl) {
                    $mediaImage->Body = base64_encode(file_get_contents($thumbnailUrl));
                }
                $imageDoc        = $saleforceConnection->create(array($mediaImage), 'Document');
                return $imageDoc = array_values((array) $imageDoc[0]);
            } catch (Exception $e) {
                header("HTTP/1.0 404 Not Found");
                $error = $e->getMessage();
                echo $error;
                exit;
            }
        }
        public static function downloadMediaSalesForce($sf_image_id)
        {
            try {
                $saleforceConnection = WWSCONNECTOR::createConnection();
                $username            = esc_attr(get_option('wwsconnector_username'));
                $pass                = esc_attr(get_option('wwsconnector_password'));
                $token               = esc_attr(get_option('wwsconnector_token'));
                $saleforceConnection->login($username, $pass . $token);
                $sfDocumentId = esc_attr(get_option('wwsconnector_imagefolder'));
                $documentData = $saleforceConnection->query("SELECT Body,BodyLength,ContentType,Id,Name,Type,Url FROM Document where Id='" . $sf_image_id . "'");
                if (isset($documentData->records[0])) {
                    global $wpdb;
                    if (!function_exists('wp_get_current_user')) {
                        include ABSPATH . "wp-includes/pluggable.php";
                    }
                    require_once ABSPATH . 'wp-admin' . '/includes/plugin.php';
                    require_once ABSPATH . 'wp-admin' . '/includes/image.php';
                    require_once ABSPATH . 'wp-admin' . '/includes/file.php';
                    require_once ABSPATH . 'wp-admin' . '/includes/media.php';
                    file_put_contents('aaaa_data.jpg', $documentData->records[0]->Body);
                    $image_url = get_site_url() . '/wp-admin/aaaa_data.jpg';
                    $post_id   = $wpdb->get_var("SELECT max(id) FROM $wpdb->posts");
                    $post_id += 1;
                    $desc  = "The WordPress Logo";
                    $image = media_sideload_image($image_url, $post_id, $desc);
                    return $image;
                } else {
                    return 0;
                }
            } catch (Exception $e) {
                header("HTTP/1.0 404 Not Found");
                $error = $e->getMessage();
                echo $error;
                exit;
            }
        }

        /**
         * Retrieve the URL for an attachment.
         *
         * @since 2.1.0
         *
         * @global string $pagenow
         *
         * @param int $post_id Optional. Attachment ID. Default 0.
         * @return string|false Attachment URL, otherwise false.
         */
        public static function wp_get_attachment_url_for_sf($post_id = 0)
        {
            $post_id = (int) $post_id;
            if (!$post = get_post($post_id)) {
                return false;
            }

            if ('attachment' != $post->post_type) {
                return false;
            }

            $url = '';

            $parent = getcwd();
            $r_path = str_replace("wp-admin", "wp-content/uploads", $parent);
            // Get attached file.
            if ($file = get_post_meta($post->ID, '_wp_attached_file', true)) {
                // Get upload directory.
                if (($uploads = wp_upload_dir()) && false === $uploads['error']) {
                    // Check that the upload base exists in the file location.
                    if (0 === strpos($file, $uploads['basedir'])) {
                        // Replace file location with url location.
                        $url = str_replace($uploads['basedir'], $r_path, $file);
                    } elseif (false !== strpos($file, 'wp-content/uploads')) {
                        // Get the directory name relative to the basedir (back compat for pre-2.7 uploads)
                        $url = trailingslashit($r_path . '/' . _wp_get_attachment_relative_path($file)) . basename($file);
                    } else {
                        // It's a newly-uploaded file, therefore $file is relative to the basedir.
                        $url = $r_path . "/$file";
                    }
                }
            }

            /*
             * If any of the above options failed, Fallback on the GUID as used pre-2.7,
             * not recommended to rely upon this.
             */
            if (empty($url)) {
                $url = get_the_guid($post->ID);
            }

            // On SSL front-end, URLs should be HTTPS.
            if (is_ssl() && !is_admin() && 'wp-login.php' !== $GLOBALS['pagenow']) {
                $url = set_url_scheme($url);
            }

            /**
             * Filter the attachment URL.
             *
             * @since 2.1.0
             *
             * @param string $url     URL for the given attachment.
             * @param int    $post_id Attachment ID.
             */

            if (empty($url)) {
                return false;
            }

            return $url;
        }

        public static function wws_get_parent_sf_category_id($parent_category_id)
        {
            try {
                $saleforceConnection = WWSCONNECTOR::createConnection();
                $username            = esc_attr(get_option('wwsconnector_username'));
                $pass                = esc_attr(get_option('wwsconnector_password'));
                $token               = esc_attr(get_option('wwsconnector_token'));
                $saleforceConnection->login($username, $pass . $token);
                $exists_sf_parent_category_id = WWSCONNECTOR::getFieldOfWwsTableWithMultipleCondition('wws_categories', $fieldName = 'sf_category_id', $conditiondata = 'sf_category_id="' . $parent_category_id . '"');
                if (isset($exists_sf_parent_category_id) && !empty($exists_sf_parent_category_id) && $exists_sf_parent_category_id != '') {
                    return $exists_sf_parent_category_id;
                } else {
                    $category_data = WWSCONNECTOR::get_category_data($parent_category_id);
                    if (isset($category_data) && !empty($category_data)) {
                        $CategoryRecords = new stdclass();
                        if (isset($category_data[0]->name)) {
                            $CategoryRecords->Name = $category_data[0]->name;
                        }
                        if (isset($category_data[0]->slug) && !empty($category_data[0]->slug)) {
                            $CategoryRecords->webkul_wws__Slug__c = $category_data[0]->slug;
                        }
                        if (isset($category_data[0]->term_id)) {
                            $CategoryRecords->webkul_wws__woo_category_id__c = $category_data[0]->term_id;
                        }
                        if (isset($category_data[0]->parent) && $category_data[0]->parent != 0) {
                            $parent_sf_category_id = WWSCONNECTOR::wws_get_parent_sf_category_id($category_data[0]->parent);
                            if ($parent_sf_category_id != 0) {
                                $CategoryRecords->webkul_wws__Parent_category__c = $parent_sf_category_id;
                            }
                        }
                        if ($thumbnail_id = WWSCONNECTOR::get_category_thumbnail_id($category_data[0]->term_id)) {
                            if (isset($thumbnail_id) && !empty($thumbnail_id)) {
                                $imageDoc = WWSCONNECTOR::uploadMediaSalesForce($thumbnail_id);
                                if ($imageDoc[1] == '1') {
                                    $CategoryRecords->webkul_wws__Image_ID__c = $imageDoc[0];
                                }
                            }
                        }
                        if (isset($category_data[0]->description) && !empty($category_data[0]->description)) {
                            $CategoryRecords->webkul_wws__Description__c = $category_data[0]->description;
                        }
                        if (!empty($CategoryRecords)) {
                            $response      = $saleforceConnection->upsert('webkul_wws__woo_category_id__c', array($CategoryRecords), 'webkul_wws__woo_commerce_categories__c');
                            $exists_cat_id = WWSCONNECTOR::getFieldOfWwsTableWithMultipleCondition('wws_categories', $fieldName = 'id', $conditiondata = 'sf_category_id="' . $response[0]->id . '"');
                            if (isset($exists_cat_id)) {
                                $wwsObject                    = array();
                                $wwsObject['sf_category_id']  = $response[0]->id;
                                $wwsObject['woo_category_id'] = $CategoryRecords->webkul_wws__woo_category_id__c;
                                WWSCONNECTOR::updateWwsTable('wws_categories', $wwsObject, array('id' => $exists_cat_id));
                            } else {
                                $wwsObject                    = array();
                                $wwsObject['sf_category_id']  = $response[0]->id;
                                $wwsObject['woo_category_id'] = $CategoryRecords->webkul_wws__woo_category_id__c;
                                WWSCONNECTOR::insertWwsTable('wws_categories', $wwsObject);
                            }
                        }
                        return $response[0]->id;
                    } else {
                        return 0;
                    }
                }
            } catch (Exception $e) {
                header("HTTP/1.0 404 Not Found");
                $error = $e->getMessage();
                return false;
            }
        }

        public static function exportCategory($direct_call = false, $category_id = 0)
        {
            $cat_id = isset($_REQUEST['cat_id']) ? $_REQUEST['cat_id'] : $category_id;
            if (!isset($cat_id)) {
                echo "No category id";
                exit;
            }
            try {
                $saleforceConnection = WWSCONNECTOR::createConnection();
                $username            = esc_attr(get_option('wwsconnector_username'));
                $pass                = esc_attr(get_option('wwsconnector_password'));
                $token               = esc_attr(get_option('wwsconnector_token'));
                $saleforceConnection->login($username, $pass . $token);
                // $categories     = WWSCONNECTOR::get_all_categories();
                $category       = WWSCONNECTOR::get_category_data($cat_id);
                $category       = $category[0];
                $processed_item = array('total' => 0, 'updated' => 0, 'added' => 0, 'sf_category_id' => '', 'syncc_time' => '');
                $processed_item['total'] += 1;
                $CategoryRecords = new stdclass();
                if (isset($category->name)) {
                    $CategoryRecords->Name = $category->name;
                }
                if (isset($category->slug) && !empty($category->slug)) {
                    $CategoryRecords->webkul_wws__Slug__c = $category->slug;
                }
                if (isset($category->term_id)) {
                    $CategoryRecords->webkul_wws__woo_category_id__c = $category->term_id;
                }
                if (!empty($category->parent)) {
                    $parent_sf_category_id                           = WWSCONNECTOR::wws_get_parent_sf_category_id($category->parent);
                    $CategoryRecords->webkul_wws__Parent_category__c = $parent_sf_category_id;
                }
                if ($thumbnail_id = WWSCONNECTOR::get_category_thumbnail_id($category->term_id)) {
                    // $CategoryRecords->webkul_wws__Image_ID__c = $thumbnail_id;
                    if (isset($thumbnail_id) && !empty($thumbnail_id)) {
                        $imageDoc = WWSCONNECTOR::uploadMediaSalesForce($thumbnail_id);
                        if ($imageDoc[1] == '1') {
                            $CategoryRecords->webkul_wws__Image_ID__c = $imageDoc[0];
                        }
                    }
                }
                if (isset($category->description) && !empty($category->description)) {
                    $CategoryRecords->webkul_wws__Description__c = $category->description;
                }
                if (!empty($CategoryRecords)) {
                    $response = $saleforceConnection->upsert('webkul_wws__woo_category_id__c', array($CategoryRecords), 'webkul_wws__woo_commerce_categories__c');
                    if (isset($response[0]->id)) {
                        $ttime_nn                     = time();
                        $date_format                  = get_option('date_format');
                        $time_format                  = get_option('time_format');
                        $processed_item['syncc_time'] = date($date_format . ' ' . $time_format, $ttime_nn);
                        $exists_cat_id                = WWSCONNECTOR::getFieldOfWwsTableWithMultipleCondition('wws_categories', $fieldName = 'id', $conditiondata = 'sf_category_id="' . $response[0]->id . '"');
                        if (isset($exists_cat_id)) {
                            $processed_item['sf_category_id'] = $response[0]->id;
                            $wwsObject                        = array();
                            $wwsObject['sf_category_id']      = $response[0]->id;
                            $wwsObject['woo_category_id']     = $CategoryRecords->webkul_wws__woo_category_id__c;
                            $wwsObject['sync_time']           = $ttime_nn;
                            WWSCONNECTOR::updateWwsTable('wws_categories', $wwsObject, array('id' => $exists_cat_id));
                            $processed_item['updated'] += 1;
                        } else {
                            $processed_item['sf_category_id'] = $response[0]->id;
                            $wwsObject                        = array();
                            $wwsObject['sf_category_id']      = $response[0]->id;
                            $wwsObject['woo_category_id']     = $CategoryRecords->webkul_wws__woo_category_id__c;
                            $wwsObject['sync_time']           = $ttime_nn;
                            WWSCONNECTOR::insertWwsTable('wws_categories', $wwsObject);
                            $processed_item['added'] += 1;
                        }
                    }
                }
                if ($direct_call) {
                    return json_encode($processed_item);
                } else {
                    echo json_encode($processed_item);
                    exit;
                }
            } catch (Exception $e) {
                header("HTTP/1.0 404 Not Found");
                $error = $e->getMessage();
                echo $error;
                exit;
            }
        }
        public static function syncronizeCategory($direct_call = false)
        {
            try {
                WWSCONNECTOR::exportCategory($direct_call);
                WWSCONNECTOR::importCategory($direct_call);
            } catch (Exception $e) {
                header("HTTP/1.0 404 Not Found");
                $error = $e->getMessage();
                echo $error;
                exit;
            }
        }

        public static function wws_fetch_sf_user_id($user_id)
        {
            try {
                $saleforceConnection = WWSCONNECTOR::createConnection();
                $username            = esc_attr(get_option('wwsconnector_username'));
                $pass                = esc_attr(get_option('wwsconnector_password'));
                $token               = esc_attr(get_option('wwsconnector_token'));
                $saleforceConnection->login($username, $pass . $token);
                $args = array(
                    'orderby'     => 'login',
                    'order'       => 'ASC',
                    'offset'      => '',
                    'search'      => '',
                    'number'      => '',
                    'count_total' => true,
                    'fields'      => 'all',
                    'who'         => '',
                );
                $User = get_user_by('id', $user_id);
                if (isset($User) && !empty($User)) {
                    $firstName       = get_user_meta($User->data->ID, 'first_name', true);
                    $lastName        = get_user_meta($User->data->ID, 'last_name', true);
                    $nickName        = $User->data->user_nicename;
                    $fullName        = $firstName . " " . $lastName;
                    $billingCompany  = get_user_meta($User->data->ID, 'billing_company', true);
                    $shippingCompany = get_user_meta($User->data->ID, 'shipping_company', true);
                    $billingPhone    = get_user_meta($User->data->ID, 'billing_phone', true);
                    $shippingPhone   = get_user_meta($User->data->ID, 'shipping_phone', true);

                    $sObject[0] = new stdclass();

                    if (empty($firstName)) {
                        $sObject[0]->Name = $nickName;
                    } else {
                        $sObject[0]->Name = $fullName;
                    }

                    if (empty($billingCompany) && empty($firstName)) {
                        $sObject[0]->webkul_wws__woo_Company_Name__c = $nickName;
                    } else if (!empty($billingCompany)) {
                        $sObject[0]->webkul_wws__woo_Company_Name__c = $billingCompany;
                    } else {
                        $sObject[0]->webkul_wws__woo_Company_Name__c = $firstName;
                    }
                    $sObject[0]->phone = $billingPhone;
                    $accDetails        = $saleforceConnection->upsert('webkul_wws__woo_Company_Name__c', $sObject, 'Account');
                    $accountId         = $accDetails[0]->id;

                    $contacts            = new stdClass;
                    $contacts->FirstName = $firstName;

                    if (!empty($lastName)) {
                        $contacts->LastName = $lastName;
                    } else {
                        $contacts->LastName = $nickName;
                    }
                    $contacts->Title = '';
                    $contacts->Phone = $billingPhone;
                    $contacts->Email = $User->data->user_email;
                    if ($shippingPhone) {
                        $contacts->OtherPhone = $shippingPhone;
                    }
                    $contacts->MobilePhone = '';
                    $contacts->HomePhone   = '';
                    $contactDetails        = $saleforceConnection->upsert('Email', array($contacts), 'Contact');
                    $contactId             = $contactDetails[0]->id;
                    $contact               = new stdClass;
                    $contact->Id           = $contactId;
                    $contact->AccountId    = $accountId;
                    $saleforceConnection->update(array($contact), 'Contact');
                    $user_ID = WWSCONNECTOR::getFieldOfWwsTable('wws_users', $fieldName = 'id', $conditionfield = 'woo_user_id', $conditionvalue = $User->data->ID);
                    if (isset($user_ID)) {
                        WWSCONNECTOR::updateWwsTable('wws_users', array('sf_user_id' => $contactDetails[0]->id, 'sync_time' => time()), array('woo_user_id' => $User->data->ID));
                    } else {
                        $wwsObject              = new stdclass();
                        $wwsObject->id          = 0;
                        $wwsObject->sf_user_id  = $contactDetails[0]->id;
                        $wwsObject->woo_user_id = $User->data->ID;
                        $wwsObject->status      = '1';
                        $wwsObject->sync_time   = time();
                        WWSCONNECTOR::insertWwsTable('wws_users', (array) $wwsObject);
                    }
                    return $contactDetails[0]->id;
                }
                return 0;
            } catch (Exception $e) {
                header("HTTP/1.0 404 Not Found");
                $error = $e->getMessage();
                return false;
            }
        }

        public static function importCategory($direct_call = false)
        {
            $limit = 200;
            if (!empty($_REQUEST['limit'])) {
                $limit = $_REQUEST['limit'];
            }
            $offset = 0;
            if (!empty($_REQUEST['offset'])) {
                $offset = $_REQUEST['offset'];
            }
            try {
                $cols                = 'Name,webkul_wws__woo_category_id__c,webkul_wws__Description__c,webkul_wws__Parent_category__c,webkul_wws__Image_ID__c,webkul_wws__Slug__c';
                $sf_cat_qry          = 'SELECT Id,' . $cols . ' FROM webkul_wws__woo_commerce_categories__c ORDER BY Id ASC  LIMIT ' . $limit . ' OFFSET ' . $offset;
                $saleforceConnection = WWSCONNECTOR::createConnection();
                $username            = esc_attr(get_option('wwsconnector_username'));
                $pass                = esc_attr(get_option('wwsconnector_password'));
                $token               = esc_attr(get_option('wwsconnector_token'));
                $saleforceConnection->login($username, $pass . $token);
                $allSfCategories = $saleforceConnection->query($sf_cat_qry);
                $processed_item  = array('total' => 0, 'updated' => 0, 'added' => 0);
                foreach ($allSfCategories->records as $sfCat) {
                    if ($sfCat->Id) {
                        $processed_item['total'] += 1;
                        $cat_id = WWSCONNECTOR::getFieldOfWwsTable('wws_categories', $fieldName = 'id', $conditionfield = 'sf_category_id', $conditionvalue = "'" . $sfCat->Id . "'");
                        if (isset($cat_id)) {
                            $data_update = array(
                                'name'               => '',
                                'slug'               => '',
                                'woo_category_id'    => '',
                                'parent_category_id' => '',
                                'image_id'           => '',
                                'description'        => '',
                            );
                            if (isset($sfCat->Name)) {
                                $data_update['name'] = $sfCat->Name;
                            }
                            if (isset($sfCat->webkul_wws__Slug__c)) {
                                $data_update['slug'] = $sfCat->webkul_wws__Slug__c;
                            }
                            if (isset($sfCat->webkul_wws__woo_category_id__c)) {
                                $data_update['woo_category_id'] = $sfCat->webkul_wws__woo_category_id__c;
                            }
                            if (isset($sfCat->webkul_wws__Parent_category__c)) {
                                // check if parent exist if not then show message
                                $data_update['parent_category_id'] = $sfCat->webkul_wws__Parent_category__c;
                            }
                            if (isset($sfCat->webkul_wws__Image_ID__c)) {
                                $data_update['image_id'] = $sfCat->webkul_wws__Image_ID__c;
                            }
                            if (isset($sfCat->webkul_wws__Description__c)) {
                                $data_update['description'] = $sfCat->webkul_wws__Description__c;
                            }
                            if (!empty($data_update)) {
                                $wwsObject = array(
                                    'sf_category_id'  => $sfCat->Id,
                                    'woo_category_id' => $data_update['woo_category_id'],
                                );
                                WWSCONNECTOR::updateWwsTable('wws_categories', $wwsObject, array('woo_category_id' => $data_update['woo_category_id']));
                                $processed_item['updated'] += 1;
                            }
                        } else {
                            $data_update = array(
                                'name'               => '',
                                'slug'               => '',
                                'woo_category_id'    => '',
                                'parent_category_id' => '',
                                'image_id'           => '',
                                'description'        => '',
                            );
                            if (isset($sfCat->Name)) {
                                $data_update['name'] = $sfCat->Name;
                            }
                            if (isset($sfCat->webkul_wws__Slug__c)) {
                                $data_update['slug'] = $sfCat->webkul_wws__Slug__c;
                            }
                            if (isset($sfCat->webkul_wws__woo_category_id__c)) {
                                $data_update['woo_category_id'] = $sfCat->webkul_wws__woo_category_id__c;
                            }
                            if (isset($sfCat->webkul_wws__Parent_category__c)) {
                                // check if parent exist if not then show message
                                $data_update['parent_category_id'] = $sfCat->webkul_wws__Parent_category__c;
                            }
                            if (isset($sfCat->webkul_wws__Image_ID__c)) {
                                $data_update['image_id'] = $sfCat->webkul_wws__Image_ID__c;
                            }
                            if (isset($sfCat->webkul_wws__Description__c)) {
                                $data_update['description'] = $sfCat->webkul_wws__Description__c;
                            }
                            $existing_cat_id = WWSCONNECTOR::getFieldOfWwsTable('terms', $fieldName = 'term_id', $conditionfield = 'name', $conditionvalue = "'" . $data_update['name'] . "'");
                            if ($existing_cat_id) {
                                $wwsObject = array(
                                    'sf_category_id'  => $sfCat->Id,
                                    'woo_category_id' => $existing_cat_id,
                                );
                                WWSCONNECTOR::insertWwsTable('wws_categories', $wwsObject);
                                $processed_item['updated'] += 1;
                            } elseif (!empty($data_update)) {
                                $term_data = array(
                                    'name'       => $data_update['name'],
                                    'slug'       => $data_update['name'],
                                    'term_group' => 0,
                                );
                                $term_id   = WWSCONNECTOR::insertWwsTable('terms', $term_data);
                                $wwsObject = array(
                                    'sf_category_id'  => $sfCat->Id,
                                    'woo_category_id' => $term_id,
                                );
                                WWSCONNECTOR::insertWwsTable('wws_categories', $wwsObject);
                                $taxonomy_data = array(
                                    'term_id'     => $term_id,
                                    'taxonomy'    => 'product_cat',
                                    'description' => $data_update['description'],
                                    'parent'      => $data_update['parent_category_id'],
                                    'count'       => 0,
                                );
                                $term_taxonomy_id = WWSCONNECTOR::insertWwsTable('term_taxonomy', $taxonomy_data);
                                /*$attachment_id = 0;
                                $attachment_id = WWSCONNECTOR::downloadMediaSalesForce($data_update['image_id']);
                                $thumbnail_data = array(
                                'meta_key' => 'thumbnail_id',
                                'woocommerce_term_id' => $term_id,
                                'meta_value' => $attachment_id,
                                );
                                WWSCONNECTOR::insertWwsTable('woocommerce_termmeta',$thumbnail_data);*/
                                $processed_item['added'] += 1;
                            }
                        }
                    }
                }
                if (!$direct_call) {
                    echo json_encode($processed_item);
                    exit;
                }
            } catch (Exception $e) {
                header("HTTP/1.0 404 Not Found");
                $error = $e->getMessage();
                echo $error;
                exit;
            }
        }
        public static function create_new_category($cat_data)
        {
            return WWSCONNECTOR::insertWwsTable('wws_products', $cat_data);
        }
        public static function get_category_id($term_id)
        {
            $catid = WWSCONNECTOR::getFieldOfWwsTable('terms', $fieldName = 'id', $conditionfield = 'sf_product_id', $conditionvalue = "'" . $product->Id . "'");
        }
        public static function get_all_categories($search = '')
        {
            global $wpdb;
            $condition = " AND trms_tax.taxonomy = 'product_cat'";
            $all_cat   = $wpdb->get_results("SELECT * FROM $wpdb->terms as trms INNER JOIN $wpdb->term_taxonomy trms_tax ON trms.term_id = trms_tax.term_id  WHERE 1 $condition $search");
            return $all_cat;
        }
        public static function get_category_data($category_id)
        {
            global $wpdb;
            $cat_data = $wpdb->get_results("SELECT * FROM $wpdb->terms as trms INNER JOIN $wpdb->term_taxonomy trms_tax ON trms.term_id = trms_tax.term_id  WHERE trms_tax.taxonomy = 'product_cat' AND trms.term_id=" . $category_id);
            return $cat_data;
        }
        public static function get_category_thumbnail_id($term_id)
        {
            global $wpdb;
            // $thumbnail_id = WWSCONNECTOR::getFieldOfWwsTableWithMultipleCondition('woocommerce_termmeta',$fieldName='meta_value',$conditiondata="meta_key = 'thumbnail_id' AND woocommerce_term_id = ".$term_id);
            $thumbnail_id = get_woocommerce_term_meta($term_id, 'thumbnail_id', true);
            if ($thumbnail_id) {
                return $thumbnail_id;
            } else {
                return false;
            }
        }

        public static function createSalsforceLogin()
        {
            $saleforceConnection = WWSCONNECTOR::createConnection();
            $username            = esc_attr(get_option('wwsconnector_username'));
            $pass                = esc_attr(get_option('wwsconnector_password'));
            $token               = esc_attr(get_option('wwsconnector_token'));
            return $saleforceConnection->login($username, $pass . $token);
        }

        public static function createConnection()
        {
            require_once 'classes/services/soapclient/SforcePartnerClient.php';
            require_once 'classes/services/soapclient/SforceEnterpriseClient.php';
            try {
                $saleforceConnection = new SforceEnterpriseClient();
                $cwd                 = __DIR__;
                $saleforceConnection->createConnection($cwd . "/classes/services/wsdl.jsp.xml");
            } catch (Exception $e) {
                header("HTTP/1.0 404 Not Found");
                $error = $e->getMessage();
                echo $error;
                exit;
            }
            return $saleforceConnection;
        }
    }
    // Set an instance of your plugin in order to make it ready to user activation and deactivation
    $GLOBALS['WWSCONNECTOR'] = new WWSCONNECTOR(__FILE__);
}
add_action('init', 'codex_book_init');

/**
 * Register a book post type.
 *
 * @link http://codex.wordpress.org/Function_Reference/register_post_type
 */

function codex_book_init()
{
    $labels = array(
        'name'               => _x('Books', 'post type general name', 'your-plugin-textdomain'),
        'singular_name'      => _x('Book', 'post type singular name', 'your-plugin-textdomain'),
        'menu_name'          => _x('Books', 'admin menu', 'your-plugin-textdomain'),
        'name_admin_bar'     => _x('Book', 'add new on admin bar', 'your-plugin-textdomain'),
        'add_new'            => _x('Add New', 'book', 'your-plugin-textdomain'),
        'add_new_item'       => __('Add New Book', 'your-plugin-textdomain'),
        'new_item'           => __('New Book', 'your-plugin-textdomain'),
        'edit_item'          => __('Edit Book', 'your-plugin-textdomain'),
        'view_item'          => __('View Book', 'your-plugin-textdomain'),
        'all_items'          => __('All Books', 'your-plugin-textdomain'),
        'search_items'       => __('Search Books', 'your-plugin-textdomain'),
        'parent_item_colon'  => __('Parent Books:', 'your-plugin-textdomain'),
        'not_found'          => __('No books found.', 'your-plugin-textdomain'),
        'not_found_in_trash' => __('No books found in Trash.', 'your-plugin-textdomain'),
    );

    $args = array(
        'labels'             => $labels,
        'description'        => __('Description.', 'your-plugin-textdomain'),
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug' => 'book123'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments'),
    );

    register_post_type('book', $args);
}

add_action('wp_ajax_syncronizeProduct', array('WWSCONNECTOR', 'syncronizeProduct'));
add_action('wp_ajax_importProduct', array('WWSCONNECTOR', 'importProduct'));
add_action('wp_ajax_exportProduct', array('WWSCONNECTOR', 'exportProduct'));
add_action('wp_ajax_syncronizeCategory', array('WWSCONNECTOR', 'syncronizeCategory'));
add_action('wp_ajax_importCategory', array('WWSCONNECTOR', 'importCategory'));
add_action('wp_ajax_exportCategory', array('WWSCONNECTOR', 'exportCategory'));
add_action('wp_ajax_categoryDelete', array('WWSCONNECTOR', 'categoryDelete'));
add_action('wp_ajax_getsfTotalProducts', array('WWSCONNECTOR', 'getsfTotalProducts'));
add_action('wp_ajax_getsfTotalCategories', array('WWSCONNECTOR', 'getsfTotalCategories'));
add_action('wp_ajax_getAllProductsIds', array('WWSCONNECTOR', 'getAllProductsIds'));
add_action('wp_ajax_getAllCategoryIds', array('WWSCONNECTOR', 'getAllCategoryIds'));