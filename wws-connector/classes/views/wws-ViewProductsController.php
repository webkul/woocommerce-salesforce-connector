<?php

/**
 * @class           WWSProductsViewController
 * @author          Webkul
 * @copyright       Copyright (C) 2015 webkul software pvt Ltd. All Rights Reserved.
 * @date            2015-12-24
 * @version         1.0.0
 *
 */
class WWSProductsViewController extends WPDKViewController
{

	/**
	 * Return a singleton instance of WWSProductsViewController class
	 *
	 * @brief Singleton
	 *
	 * @return WWSProductsViewController
	 */
	public static function init()
	{
		static $instance = null;
		if (is_null($instance)) {
			$instance = new self();
		}
		return $instance;
	}

	/**
	 * Create an instance of WWSProductsViewController class
	 *
	 * @brief Construct
	 *
	 * @return WWSProductsViewController
	 */
	public function __construct()
	{
		// Build the container, with default header
		parent::__construct('wws-ViewController', '');
		// add_screen_option(
		// 'per_page',
		// array('label' => _x( 'Comments', 'comments per page (screen options)' )) );
		add_filter('screen_settings', array(&$this, 'add_options'));
		$this->enqueue_scripts_styles_init();
		
		if (isset($_COOKIE['wp_sf_notices'])) {
			add_action('admin_notices', array(&$this, 'wpsf_my_error_notice'));
			// 
			// setcookie('wp_sf_notices', 'some default value', strtotime('+1 day'));
			// exit;
		}
	}

	public function wpsf_my_error_notice() {
		$cookieValue = $_COOKIE['wp_sf_notices'];
		setcookie('wp_sf_notices', null, strtotime('-1 day'));//delete
		$cookieValue_array = unserialize(stripslashes($cookieValue));
		$st_notice = '';
		if ($cookieValue_array['category_synchronised']) {
		    $st_notice .= 'Category Syncronization successfully completed';
		}
		if ($cookieValue_array['total']) {
		    $st_notice .= '<br>Product Successfully Exported';
		    $st_notice .= '<br>Items addedd = '.$cookieValue_array['added'];
		    $st_notice .= '<br>Items updated = '.$cookieValue_array['updated'];
		    $st_notice .= '<br>Total items processed = '.$cookieValue_array['total'];
		}
		?>
		<div class="updated notice">
		<p><?php echo $st_notice; ?></p>
		</div>
		<?php
	}

	public function append_screen_settings($current, $screen)
	{
		global $hook_suffix;

		//Sanity check
		if (!isset($screen->id)) {
			return $current;
		}

		//Are there any panels that want to appear on this page?
		$panels = $this->get_panels_for_screen($screen->id, $hook_suffix);
		if (empty($panels)) {
			return $current;
		}

		//Append all panels registered for this screen
		foreach ($panels as $panel_id) {
			$panel = $this->registered_panels[$panel_id];

			//Add panel title
			if (!empty($panel['title'])) {
				$current .= "\n<h5>" . $panel['title'] . "</h5>\n";
			}
			//Generate panel contents
			if (is_callable($panel['callback'])) {
				$contents = call_user_func($panel['callback']);
				$classes  = array(
					'metabox-prefs',
					'custom-options-panel',
				);
				if ($panel['autosave']) {
					$classes[] = 'requires-autosave';
				}

				$contents = sprintf(
					'<div id="%s" class="%s"><input type="hidden" name="_wpnonce-%s" value="%s" />%s</div>',
					esc_attr($panel_id),
					implode(' ', $classes),
					esc_attr($panel_id),
					wp_create_nonce('save_settings-' . $panel_id),
					$contents
				);

				$current .= $contents;
			}
		}

		return $current;
	}

	/**
	 * Display
	 *
	 * @brief Display
	 */
	public function display()
	{
		parent::display();
		/* custom  table code ------------------------------------ */
		$myListTable = new wwsListTable();
		echo '<div class="wrap" id="my_wrap">';
		echo '<div class="wpdk-view wrap clearfix" id="first-view-controller-view-root" data-type="wpdk-view">
			<div class="wpdk-view clearfix wpdk-header-view clearfix" id="first-view-controller-header-view" data-type="wpdk-view">
			<div class="wpdk-vc-header-icon" id="first-view-controller-header-view" data-type="wpdk-header-view"></div>
			<h1>Synchronize Products</h1>
			<div class="wpdk-vc-header-after-title"></div>
			<br>
			<a href="javascript:void(0);" id="syncronizeproduct_button" class="page-title-action" style="margin-right: 25px;">Synchronize All Products 
				<span class="spinner " id="syncronizeproduct_id" style="position: absolute; right: -31px; top: 0px;"></span>
			</a>
			<a href="javascript:void(0);" id="wwsexportproduct_button" class="page-title-action" style="margin-right: 25px;">Export All
				<span class="spinner " id="wwsexportproduct_id" style="position: absolute; right: -31px; top: 0px;"></span>
			</a>
			<a href="javascript:void(0);" id="wwsimportproduct_button" class="page-title-action" style="margin-right: 8px;">Import  Products
			<span class="spinner " id="wwsimportproduct_id" style="position: absolute; right: -132px; top: 0px;"></span>
			</a>
			<a href="javascript:void(0);" id="a_import_option">Import Option</a>
			<br>
			<div class="hidden" id="con_import_options">
			<br>
				<label><input type="checkbox" name="sfwp_limit_import_ch" id="sfwp_limit_import_ch" value="Y">Limit</label>
				<input type="text" value="100" name="sfwp_product_limit" size="6" class="input-text wc_input_decimal" placeholder="Length" id="sfwp_product_limit" disabled>
			</div>

			<br>
			</div>
			</div>';
		echo '<form method="get">';
		echo '<input type="hidden" name="page" value="wws_connector" />';
		$myListTable->search_box('Search', 'productsearch');
		$myListTable->prepare_items();
		$myListTable->display();
		echo '</form>';
		add_action('restrict_manage_posts', 'restrict_listings_by_business');
		echo '</div>';
		/* custom  table code ------------------------------------ */
	}

	public function restrict_listings_by_business()
	{
		global $typenow;
		global $wp_query;
		if ($typenow == 'listing') {
			$taxonomy          = 'business';
			$business_taxonomy = get_taxonomy($taxonomy);
			wp_dropdown_categories(array(
				'show_option_all' => __("Show All {$business_taxonomy->label}"),
				'taxonomy'        => $taxonomy,
				'name'            => 'business',
				'orderby'         => 'name',
				'selected'        => $wp_query->query['term'],
				'hierarchical'    => true,
				'depth'           => 3,
				'show_count'      => true, // Show # listings in parens
				'hide_empty'      => true, // Don't show businesses w/o listings
			));
		}

	}

	public function enqueue_scripts_styles_init()
	{
		wp_enqueue_script('ajax-script', plugins_url() . '/wws-connector/assets/js/js-script.js', array('jquery'), 1.0); // jQuery will be included automatically
		// get_template_directory_uri() . '/js/script.js'; // Inside a parent theme
		// get_stylesheet_directory_uri() . '/js/script.js'; // Inside a child theme
		// plugins_url( '/js/script.js', __FILE__ ); // Inside a plugin
		wp_localize_script('ajax-script', 'ajax_object', array('ajaxurl' => admin_url('admin-ajax.php'))); // setting ajaxurl
	}

	public static function exportProduct($saleforceConnection)
	{
		print_r($saleforceConnection);
		$p = $saleforceConnection->query("SELECT Id,Name,Type FROM Folder where Type='Document'");
		print_r($p);
		exit;
		$args = array(
			'posts_per_page' => -1,
			'post_type'      => 'product',
			'post_status'    => 'publish',
		);
		$allProducts = get_posts($args);

		foreach ($allProducts as $product) {
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

			/* some fields at salesforce end are common b/w virtuemart and wordpress as suggested by abhilash */

			//$data=get_the_category($wk_id);

			$productDetails           = new stdClass();
			$productDetails->IsActive = 1;
			if (isset($product->comment_count)) {
				$productDetails->webkul_ess__woo_comment_count__c = $product->comment_count;
			}

			if (isset($product->comment_status)) {
				$productDetails->webkul_ess__woo_Comment_Status__c = $product->comment_status;
			}

			if (isset($product->menu_order)) {
				$productDetails->webkul_ess__woo_Menu_Order__c = $product->menu_order;
			}

			if (isset($product->post_author)) {
				$productDetails->webkul_ess__woo_Post_Author__c = $product->post_author;
			}

			if (isset($product->post_content) && !empty($product->post_content)) {
				$productDetails->webkul_ess__Woo_Post_Content__c = $product->post_content;
			}

			if (isset($product->post_date)) {
				$productDetails->webkul_ess__Woo_Post_Date__c = $product->post_date;
			}

			if (isset($product->post_excerpt) && !empty($product->post_excerpt)) {
				$productDetails->webkul_ess__woo_Post_excerpt__c = $product->post_excerpt;
			}

			if (isset($product->ID)) {
				$productDetails->webkul_ess__woo_Post_id__c = $product->ID;
			}

			if (isset($product->post_mime_type) && !empty($product->post_mime_type)) {
				$productDetails->webkul_ess__woo_post_Mime_Type__c = $product->post_mime_type;
			}

			if (isset($product->post_name) && !empty($product->post_name)) {
				$productDetails->webkul_ess__Woo_Post_Name__c = $product->post_name;
				$productDetails->name                         = $product->post_name;
			}
			// if(isset($product->post_parent))
			//  $productDetails->webkul_ess__Woo_Post_parent__c=$product->post_parent;
			if (isset($product->post_title) && !empty($product->post_title)) {
				$productDetails->webkul_ess__woo_Post_Title__c = $product->post_title;
			}

			if (isset($product->post_type)) {
				$productDetails->webkul_ess__Woo_Post_type__c = $product->post_type;
			}

			if (isset($productSku) && !empty($productSku)) {
				$productDetails->webkul_ess__woo_Product_SKu__c = $productSku;
			}

			if ($manageStock == 'yes') {
				if (isset($productStock) && !empty($productStock)) {
					$productDetails->webkul_ess__Woo_Stock__c = $productStock;
				}

			}
			if (isset($thumbnailId) && !empty($thumbnailId)) {
				$imageDoc = WWSCONNECTOR::uploadMediaSalesForce($thumbnailId, $saleforceConnection);
				if ($imageDoc[1] == '1') {
					$productDetails->webkul_ess__woo_Thumbnail_ID__c = $imageDoc[0];
				}
			}
			if (isset($productWidth) && !empty($productWidth)) {
				$productDetails->webkul_ess__wk_vm_product_width__c = $productWidth;
			}

			if (isset($productWeight) && !empty($productWeight)) {
				$productDetails->webkul_ess__wk_vm_product_weight__c = $productWeight;
			}

			if (isset($productHeight) && !empty($productHeight)) {
				$productDetails->webkul_ess__wk_vm_product_height__c = $productHeight;
			}

			if (isset($productLength) && !empty($productLength)) {
				$productDetails->webkul_ess__wk_vm_product_length__c = $productLength;
			}

			// print_r($productDetails);

			try {
				$productId = $saleforceConnection->upsert('Name', array($productDetails), 'Product2');
				print_r($productId);
				exit;
			} catch (Exception $e) {
				$error_message = $e->faultstring;
			}
		}

		/**---------------------------prodcut Price Book ------------------------**/
		try {
			$standardPriceBookId = $saleforceConnection->query("SELECT Id FROM Pricebook2 where IsStandard=true");
			$standardPriceBookId = $standardPriceBookId->records[0]->Id;
		} catch (Exception $e) {
			$error_message = $e->faultstring;
		}
		try {
			$catId = $saleforceConnection->query("SELECT Id FROM pricebookentry where Product2Id='" . $productId[0]->id . "'");
			$ids   = array();
			foreach ($catId as $createResult) {
				array_push($ids, $createResult->Id);
			}
		} catch (Exception $e) {
			$this->jvsErrorLog($e->faultstring);
		}
		try {
			if (!empty($ids)) {
				$deleteResult = $saleforceConnection->delete($ids);
			}
		} catch (Exception $e) {
			$error_message = $e->faultstring;
		}
		$priceBookEntry               = new stdClass();
		$priceBookEntry->IsActive     = 1;
		$priceBookEntry->Pricebook2Id = $standardPriceBookId;
		if (isset($productId[0]->id) && !empty($productId[0]->id)) {
			$priceBookEntry->Product2Id = $productId[0]->id;
		}

		if (isset($salesPrice) && !empty($salesPrice)) {
			$priceBookEntry->UnitPrice = $salesPrice;
		}

		try {
			$response = $saleforceConnection->create(array($priceBookEntry), 'pricebookentry');
		} catch (Exception $e) {
			$error_message = $e->faultstring;
		}
		/**----------------------this code for add product in another price book -------------------**/
		$priceBookId                  = '01s28000004XISt';
		$priceBookEntry               = new stdClass();
		$priceBookEntry->IsActive     = 1;
		$priceBookEntry->Pricebook2Id = $priceBookId;
		if (isset($productId[0]->id) && $productId[0]->id) {
			$priceBookEntry->Product2Id = $productId[0]->id;
		}

		if (isset($salesPrice) && !empty($salesPrice)) {
			$priceBookEntry->UnitPrice = $salesPrice;
		}

		try {
			$response = $this->saleforceConnection->create(array($priceBookEntry), 'pricebookentry');
		} catch (Exception $e) {
			$error_message = $e->faultstring;
		}
		/**----------------------this code for add product in another price book end -------------------**/

		/**---------------------------prodcut Price Book ------------------------**/

		$wwsproduct_id = WWSCONNECTOR::getFieldOfWwsTable('wws_products', $fieldName = 'id', $conditionfield = 'woo_product_id', $conditionvalue = $product->ID);
		if (isset($wwsproduct_id)) {
			WWSCONNECTOR::updateWwsTable('wws_products', array('sf_product_id' => $productId[0]->id, 'sync_time' => time()), array('woo_product_id' => $product->ID));
		} else {
			$wwsObject                 = new stdclass();
			$wwsObject->id             = 0;
			$wwsObject->sf_product_id  = $productId[0]->id;
			$wwsObject->woo_product_id = $product->ID;
			$wwsObject->status         = '1';
			$wwsObject->sync_time      = '';
			WWSCONNECTOR::insertWwsTable('wws_products', (array) $wwsObject);
		}

	}

	public function add_options()
	{
		$option = 'per_page';
		$args   = array(
			'label'   => 'Product Per Page',
			'default' => 10,
			'option'  => 'product_per_page',
		);
		add_screen_option($option, $args);
	}

}

/* wp list table in wordpress */

if (!class_exists('WP_List_Table')) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class wwsListTable extends WP_List_Table
{

	private function table_data()
	{
		global $wpdb;
		$current_page = $this->get_pagenum();
		$table_name1  = $wpdb->prefix . 'wws_products';
		$table_name2  = $wpdb->prefix . 'posts';
		$data   = array();
		$posts_per_page = (int) get_user_option( 'product_per_page' );
		$posts_per_page = $posts_per_page != 0?$posts_per_page:10;
		$args = array(
			'posts_per_page' => $posts_per_page,
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'paged'          => $current_page,
			's'              => !empty($_REQUEST['s']) ? $_REQUEST['s'] : ''
		);
		$allProducts_array = new WP_Query($args);
		$count        = $allProducts_array->found_posts;
		$wooProduct        = array();
		foreach ($allProducts_array->posts as $key => $product_data) {
			$condition2      = ' AND wwp1.woo_product_id =' . $product_data->ID;
			$wooProduct_data = $wpdb->get_results("SELECT wwp1.*,wwp2.post_title,wwp2.ID FROM $table_name1 as wwp1 join $table_name2 as wwp2 on wwp1.woo_product_id=wwp2.ID WHERE 1 = 1 {$condition2}");
			if (!$wooProduct_data) {
				$woo_product_temp                 = new stdclass();
				$woo_product_temp->id             = '';
				$woo_product_temp->sf_product_id  = '<span id="add_sf_product_id_'.$product_data->ID.'">--</span>';
				$woo_product_temp->woo_product_id = $product_data->ID;
				$woo_product_temp->status         = '';
				$woo_product_temp->sync_time      = '';
				$woo_product_temp->post_title     = $product_data->post_title;
				$woo_product_temp->ID             = $product_data->ID;
				$wooProduct[]                     = $woo_product_temp;
			} else {
				$wooProduct[] = $wooProduct_data[0];
			}
		}
		// Set the pagination
		$this->set_pagination_args(array(
			'total_items' => $count,
			'per_page'    => $posts_per_page,
			'total_pages' => ceil($count / $posts_per_page),
		));
		$date_format = get_option( 'date_format' );
		$time_format = get_option( 'time_format' );

		$sf_id          = array();
		$productimage   = array();
		$productname    = array();
		$wooproductid   = array();
		$salesproductid = array();
		$woosfmergeid   = array();
		$woosync_time   = array();
		$i              = 0;
		foreach ($wooProduct as $wooProducts) {
			array_push($sf_id, $wooProducts->id);
			$img_src = wp_get_attachment_url(get_post_thumbnail_id($wooProducts->ID));
			if (!$img_src) {
				$img_src = wc_placeholder_img_src();
			}
			array_push($productimage, '<a href="'.get_edit_post_link( $wooProducts->woo_product_id ).'" ><img src="' . $img_src . '" width="50" height="50" /></a>');
			array_push($productname, $wooProducts->post_title);
			array_push($wooproductid, $wooProducts->woo_product_id);
			array_push($salesproductid, $wooProducts->sf_product_id);
			array_push($woosfmergeid, $wooProducts->id);
			if (!empty($wooProducts->sync_time)) {
				$time = date($date_format.' '.$time_format,$wooProducts->sync_time);
			}else{
				$time = '<span id="add_sf_sync_time_'.$wooProducts->woo_product_id.'">--</span>';;
			}
			array_push($woosync_time, $time);
			$data[] = array(
				'id'             => $sf_id[$i],
				'productimage'   => $productimage[$i],
				'productname'    => '<a href="'.get_edit_post_link( $wooProducts->woo_product_id ).'" > '.$productname[$i].'</a>',
				'wooproductid'   => $wooproductid[$i],
				'salesproductid' => $salesproductid[$i],
				'woosfmergeid'   => $woosfmergeid[$i],
				'woosync_time'   => $woosync_time[$i],
			);
			$i++;
		}
		return $data;
	}

	public function get_columns()
	{
		$columns = array(
			'cb'             => '<input type="checkbox" />',
			'productimage'   => 'Image',
			'productname'    => 'Product Name',
			'wooproductid'   => 'woocommerce Product Id',
			'salesproductid' => 'Salesforce Product Id',
			'woosync_time'   => 'Sync Time',
		);
		return $columns;
	}

	public function prepare_items()
	{
		global $wpdb;
		$products_data         = $this->table_data();
		$columns               = $this->get_columns();
		$hidden                = $this->get_hidden_columns();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);
		$this->process_bulk_action();
		usort($products_data, array(&$this, 'usort_reorder'));
		$this->items     = $products_data;
		// $user            = get_current_user_id();
		// $screen          = get_current_screen();
		// $option          = $screen->get_option('per_page', 'option');
		// $perpage         = get_user_meta($user, $option, true);
		// $per_page        = $this->get_items_per_page('per_page', get_option('posts_per_page'));
		// $current_page    = $this->get_pagenum();
		// $totalitems      = count($products_data);
		// $pagination_data = array_slice($products_data, (($current_page - 1) * $per_page), $per_page);
		//   $this->set_pagination_args( array(
		// 'total_items' => $totalitems,                  //WE have to calculate the total number of items
		// 'per_page'    => $per_page                     //WE have to determine how many items to show on a page
		//   ) );
		
		$this->items = $products_data;
	}

	public function get_hidden_columns()
	{
		return array();
	}

	public function column_default($item, $column_name)
	{
		switch ($column_name) {
			case 'productimage':
			case 'productname':
			case 'wooproductid':
			case 'salesproductid':
			case 'woosync_time':
				return $item[$column_name];
			default:
				return print_r($item, true); //Show the whole array for troubleshooting purposes
		}
	}
	public function get_sortable_columns()
	{
		$sortable_columns = array(
			'productname'    => array('productname', false),
			'wooproductid'   => array('wooproductid', false),
			'salesproductid' => array('salesproductid', false),
			'woosync_time'   => array('woosync_time', false),
		);
		return $sortable_columns;
	}

	public function usort_reorder($a, $b)
	{
		// If no sort, default to title
		$orderby = (!empty($_GET['orderby'])) ? $_GET['orderby'] : 'productname';
		// If no order, default to asc
		$order = (!empty($_GET['order'])) ? $_GET['order'] : 'asc';
		// Determine sort order
		$result = strcmp($a[$orderby], $b[$orderby]);
		// Send final sort direction to usort
		return ($order === 'asc') ? $result : -$result;
	}

	public function column_productname($item)
	{
		$actions = array(
			// 'edit'      => sprintf('<a href="?page=%s&action=%s&book=%s">Edit</a>',$_REQUEST['page'],'edit',$item['id']),
			'delete' => sprintf('<a href="?page=%s&action=%s&pmid=%s">Delete</a>', $_REQUEST['page'], 'delete', $item['wooproductid']),
		);
		return sprintf('%1$s %2$s', $item['productname'], $this->row_actions($actions));
	}

	public function get_bulk_actions()
	{
		$actions = array(
			'exportProduct'    => 'Export Selected Product',
			'delete' => 'Delete',
		);
		return $actions;
	}

	public function process_bulk_action()
	{
		global $wpdb;
		$current_user = wp_get_current_user();
		$action       = $this->current_action();
		if ('delete' === $action) {
			if (isset($_REQUEST['mergedproduct'])) {
				$delList = $_REQUEST['mergedproduct'];
			} else {
				if (!empty($_REQUEST['pmid'])) {
					$delList = array($_GET['pmid']);
				}
			}
			$prefix = $wpdb->prefix;
			if (!empty($delList)){
				foreach ($delList as $id) {
					$wpdb->query($wpdb->prepare("DELETE FROM " . $prefix . "wws_products WHERE woo_product_id = %d", $id));
				}
				$type = 'Deleted';
				$title = 'Deleted items id(s):';
				$de_blank = sprintf('<a href="?page=%s&action=%s&pmid=%s"> Refresh Page</a>', $_REQUEST['page'], 'delete', '');
				$msg = implode(',', $delList).' <br>'.$de_blank;
				echo '<div class="'.$type.' notice is-dismissible" id="error_response"> <p> <strong>'.$title.' </strong> '.$msg.'</p><button class="notice-dismiss" type="button"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
			}
			// add_action('admin_notices', 'Product Deleted');

		}else if ($action == 'exportProduct') {
			if (isset($_REQUEST['mergedproduct'])) {
				/*$delList = $_REQUEST['mergedproduct'];
				$prefix = $wpdb->prefix;
				if (!empty($delList))
				foreach ($delList as $id) {
					echo 'id = '.$id;
					echo "<br>";
				}
				$type = 'updated';
				$title = 'DMEO TITLE';
				$msg = 'DEMO MESSAGE';*/
				// echo '<div class="'.$type.' notice is-dismissible" id="error_response"> <p> <strong>'.$title.' </strong> '.$msg.'</p><button class="notice-dismiss" type="button"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
			}
		}
	}

	public function column_cb($item)
	{
		return sprintf(
			'<input type="checkbox" name="mergedproduct[]" value="%s" />', $item['wooproductid']
		);
	}

	public function search_box($text, $input_id)
	{?>
	<p class="search-box">
	  <label class="screen-reader-text" for="<?php echo $input_id ?>"><?php echo $text; ?>:</label>
	  <input type="search" id="<?php echo $input_id ?>" name="s" value="<?php _admin_search_query();?>" />
	  <?php submit_button($text, 'button', false, false, array('id' => 'search-submit'));?>
  </p>
<?php }

}
