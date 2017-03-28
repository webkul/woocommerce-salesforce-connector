<?php

/**
 * @class           WWSCategoriesViewController
 * @author          webkul team
 * @copyright       Copyright (C) 2015 webkul software pvt Ltd. All Rights Reserved.
 * @date            2015-12-24
 * @version         1.0.0
 *
 */
class WWSCategoriesViewController extends WPDKViewController
{

	/**
	 * Return a singleton instance of WWSCategoriesViewController class
	 *
	 * @brief Singleton
	 *
	 * @return WWSCategoriesViewController
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
	 * Create an instance of WWSCategoriesViewController class
	 *
	 * @brief Construct
	 *
	 * @return WWSCategoriesViewController
	 */
	public function __construct()
	{
		// Build the container, with default header
		parent::__construct('WWSCategoriesViewController', '');

		// echo get_woocommerce_term_meta( 9, 'thumbnail_id', $single=true );
		// exit;

		$this->enqueue_scripts_styles_init();
	}

	/**
	 * Display
	 *
	 * @brief Display
	 */
	public function display()
	{

		// call parent display to build default page structure
		parent::display();
		/* custom  table code ------------------------------------ */
		$myListTable = new wwsCategoryListTable();
		echo '<div class="wrap" id="my_wrap">';
		echo '<div class="wpdk-view wrap clearfix" id="first-view-controller-view-root" data-type="wpdk-view">
		  <div class="wpdk-view clearfix wpdk-header-view clearfix" id="first-view-controller-header-view" data-type="wpdk-view">
			<div class="wpdk-vc-header-icon" id="first-view-controller-header-view" data-type="wpdk-header-view"></div>
			<h1>Synchronize Categories</h1>
			<div class="wpdk-vc-header-after-title"></div>
			<br>
			<a href="javascript:void(0);" id="syncronizeCategory_button" class="page-title-action" style="margin-right: 25px;">Synchronize Categories

			  <span class="spinner " id="syncronizeCategory_id" style="position: absolute; right: -31px; top: 0px;"></span>
			</a>
			<a href="javascript:void(0);" id="wwsexportCategory_button" class="page-title-action" style="margin-right: 25px;">Export Categories

			  <span class="spinner " id="wwsexportCategory_id" style="position: absolute; right: -31px; top: 0px;"></span>
			</a>
			<a href="javascript:void(0);" id="wwsimportCategory_button" class="page-title-action" style="margin-right: 25px;">Import  Categories

			  <span class="spinner " id="wwsimportCategory_id" style="position: absolute; right: -31px; top: 0px;"></span>
			</a>
			<br>
		  </div>
	  </div>';
		echo '<form method="post">';
		echo '<input type="hidden" name="page" value="wws_connector-submenu-2" />';
		$myListTable->search_box('Search', 'categorysearch');
		$myListTable->prepare_items();
		$myListTable->display();
		echo '</form>';
		echo '</div>';
		/* custom  table code ------------------------------------ */
	}
	public function enqueue_scripts_styles_init()
	{
		wp_enqueue_script('ajax-script', plugins_url() . '/wws-connector/assets/js/js-script.js', array('jquery'), 1.0); // jQuery will be included automatically
		// get_template_directory_uri() . '/js/script.js'; // Inside a parent theme
		// get_stylesheet_directory_uri() . '/js/script.js'; // Inside a child theme
		// plugins_url( '/js/script.js', __FILE__ ); // Inside a plugin
		wp_localize_script('ajax-script', 'ajax_object', array('ajaxurl' => admin_url('admin-ajax.php'))); // setting ajaxurl
	}

	public function exportCategories()
	{
		$args = array(
			'type'     => 'product',
			'taxonomy' => 'product_cat',
		);

		$categories = get_categories($args);
		// print_r($categories);
		foreach ($categories as $woocat) {
			// print_r($woocat);
			$CategoryRecords = new stdclass();
			if (isset($woocat->cat_name) && !empty($woocat->cat_name)) {
				$CategoryRecords->Name = $woocat->cat_name;
			}
			if (isset($woocat->cat_ID) && !empty($woocat->cat_ID)) {
				$CategoryRecords->webkul_ess__woo_category_id__c = $woocat->cat_ID;
			}
			if (isset($woocat->category_parent) && !empty($woocat->category_parent)) {
				$sfCatId = WWSCONNECTOR::getFieldOfWwsTable('wws_categories', $fieldName = 'sf_category_id', $conditionfield = 'woo_category_id', $conditionvalue = $woocat->category_parent);
				if (!empty($sfCatId)) {
					$CategoryRecords->webkul_ess__Parent_category__c = $sfCatId;
				}
			}
			if (isset($woocat->description) && !empty($woocat->description)) {
				$CategoryRecords->webkul_ess__Description__c = $woocat->description;
			}
			if (isset($woocat->slug) && !empty($woocat->slug)) {
				$CategoryRecords->webkul_ess__Slug__c = $woocat->slug;
			}
			try {
				$response = $saleforceConnection->upsert('Name', array($CategoryRecords), 'webkul_ess__woo_commerce_categories__c');

				$cat_id = WWSCONNECTOR::getFieldOfWwsTable('wws_categories', $fieldName = 'id', $conditionfield = 'woo_category_id', $conditionvalue = $woocat->cat_ID);
				if (isset($cat_id)) {
					WWSCONNECTOR::updateWwsTable('wws_categories', array('sf_category_id' => $response[0]->id, 'sync_time' => time()), array('woo_category_id' => $woocat->cat_ID));
				} else {
					$wwsObject                  = new stdclass();
					$wwsObject->id              = 0;
					$wwsObject->sf_category_id  = $response[0]->id;
					$wwsObject->woo_category_id = $CategoryRecords->webkul_ess__woo_category_id__c;
					$wwsObject->status          = '1';
					$wwsObject->sync_time       = '';
					WWSCONNECTOR::insertWwsTable('wws_categories', (array) $wwsObject);
				}
				print_r($response);
			} catch (Exception $e) {
				$error_message = $e->faultstring;
				print_r($error_message);
				exit;
			}
		}

		exit;
	}

}

/* wp list table in wordpress */

if (!class_exists('WP_List_Table')) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class wwsCategoryListTable extends WP_List_Table
{

	private function table_data()
	{
		global $wpdb;
		$table_name1 = $wpdb->prefix . 'wws_categories';
		$table_name2 = $wpdb->prefix . 'terms';
		$table_name3 = $wpdb->prefix . 'term_taxonomy';

		$data   = array();
		$search = '';
		if (isset($_REQUEST['s'])) {
			$search = " AND trms.name LIKE '%" . trim($_REQUEST['s']) . "%'";
		}
		$cat_data = WWSCONNECTOR::get_all_categories($search);
		$wooCategory = array();
		foreach ($cat_data as $ke_y => $val_ue) {
			$wooCategory_single                        = $wpdb->get_results("SELECT $table_name1.* FROM $table_name1 WHERE $table_name1.woo_category_id = " . $val_ue->term_id);
			$woo_category_temp                  = new stdclass();
			$woo_category_temp->id              = '';
			$woo_category_temp->sf_category_id  = '<span id="add_sf_cat_id_' . $val_ue->term_id . '">--</span>';
			$woo_category_temp->woo_category_id = $val_ue->term_id;
			$woo_category_temp->status          = '';
			$woo_category_temp->sync_time       = '';
			$woo_category_temp->name            = $val_ue->name;
			$woo_category_temp->term_id         = $val_ue->term_id;
			if (!empty($wooCategory_single)) {
				$woo_category_temp->id              = $wooCategory_single[0]->id;
				$woo_category_temp->sf_category_id  = $wooCategory_single[0]->sf_category_id;
				$woo_category_temp->status          = $wooCategory_single[0]->status;
				$woo_category_temp->sync_time       = $wooCategory_single[0]->sync_time;
			}
			$wooCategory[] = $woo_category_temp;
		}
		$sf_id           = array();
		$categoryimage   = array();
		$categoryname    = array();
		$woocategoryid   = array();
		$salescategoryid = array();
		$woosfmergeid    = array();
		$woosync_time    = array();

		$date_format = get_option( 'date_format' );
		$time_format = get_option( 'time_format' );

		$i = 0;
		foreach ($wooCategory as $wooCat) {
			array_push($sf_id, $wooCat->id);
			$img_src = wp_get_attachment_url(get_woocommerce_term_meta($wooCat->term_id, 'thumbnail_id', $single = true));
			if (!$img_src) {
				$img_src = wc_placeholder_img_src();
			}
			array_push($categoryimage, '<img src="' . $img_src . '" width="50" height="50" />');
			array_push($categoryname, $wooCat->name);
			array_push($woocategoryid, $wooCat->woo_category_id);
			array_push($salescategoryid, $wooCat->sf_category_id);
			array_push($woosfmergeid, $wooCat->id);
			if (!empty($wooCat->sync_time)) {
				$time = date($date_format.' '.$time_format,$wooCat->sync_time);
				$time = '<span id="add_sf_sync_time_'.$wooCat->woo_category_id.'">'.$time.'</span>';
			}else{
				$time = '<span id="add_sf_sync_time_'.$wooCat->woo_category_id.'">--</span>';
			}
			array_push($woosync_time, $time);
			$data[] = array(
				'id'              => $sf_id[$i],
				'categoryimage'   => $categoryimage[$i],
				'categoryname'    => $categoryname[$i],
				'woocategoryid'   => $woocategoryid[$i],
				'salescategoryid' => $salescategoryid[$i],
				'woosfmergeid'    => $woosfmergeid[$i],
				'woosync_time'    => $woosync_time[$i],
			);
			$i++;
		}
		return $data;
	}

	public function get_columns()
	{
		$columns = array(
			'cb'              => '<input type="checkbox" />',
			'categoryimage'   => 'Image',
			'categoryname'    => 'Category Name',
			'woocategoryid'   => 'woocommerce Category Id',
			'salescategoryid' => 'Salesforce Category Id',
			'woosync_time'    => 'Sync Time',
		);
		return $columns;
	}

	public function prepare_items()
	{
		$products_data         = $this->table_data();
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);
		$this->process_bulk_action();
		usort($products_data, array(&$this, 'usort_reorder'));
		$this->items     = $products_data;
		$user            = get_current_user_id();
		$screen          = get_current_screen();
		$option          = $screen->get_option('per_page', 'option');
		$perpage         = get_user_meta($user, $option, true);
		$per_page        = $this->get_items_per_page('per_page', get_option('posts_per_page'));
		$current_page    = $this->get_pagenum();
		$total_items     = count($products_data);
		$pagination_data = array_slice($products_data, (($current_page - 1) * $per_page), $per_page);
		$this->set_pagination_args(array(
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page'    => $per_page, //WE have to determine how many items to show on a page
		));
		$this->items = $pagination_data;
	}

	public function get_bulk_actions()
	{
		$actions = array(
			'exportCategory'    => 'Export Selected Category',
			'delete' => 'Delete',
			// 'edit'    => 'Edit'
		);
		return $actions;
	}

	public function process_bulk_action()
	{
		global $wpdb;
		$current_user = wp_get_current_user();
		$action       = $this->current_action();

		if ('delete' === $action) {
			if (isset($_REQUEST['mergecategory'])) {
				$delList = $_REQUEST['mergecategory'];
			} else {
				$delList = !empty($_REQUEST['cid'])? array($_REQUEST['cid']):array();
			}

			$prefix = $wpdb->prefix;
			if (!empty($delList)) {
				foreach ($delList as $id) {
					$wpdb->query($wpdb->prepare("DELETE FROM " . $prefix . "wws_categories WHERE woo_category_id = %d", $id));
				}
				$type = 'Deleted';
				$title = 'Deleted items id(s):';
				$de_blank = sprintf('<a href="?page=%s&action=%s&cid=%s"> Refresh Page</a>', $_REQUEST['page'], 'delete', '');
				$msg = implode(',', $delList).' <br>'.$de_blank;
				echo '<div class="'.$type.' notice is-dismissible" id="error_response"> <p> <strong>'.$title.' </strong> '.$msg.'</p><button class="notice-dismiss" type="button"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
			}
		}
	}

	public function column_default($item, $column_name)
	{
		switch ($column_name) {
			case 'categoryimage':
			case 'categoryname':
			case 'woocategoryid':
			case 'salescategoryid':
			case 'woosync_time':
				return $item[$column_name];
			default:
				return print_r($item, true); //Show the whole array for troubleshooting purposes
		}
	}
	public function get_sortable_columns()
	{
		$sortable_columns = array(
			'categoryname'    => array('categoryname', false),
			'woocategoryid'   => array('woocategoryid', false),
			'salescategoryid' => array('salescategoryid', false),
			'woosync_time'    => array('woosync_time', false),

		);
		return $sortable_columns;
	}

	public function usort_reorder($a, $b)
	{
		// If no sort, default to title
		$orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'categoryname';
		// If no order, default to asc
		$order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc';
		// Determine sort order
		$result = strcmp($a[$orderby], $b[$orderby]);
		// Send final sort direction to usort
		return ($order === 'asc') ? $result : -$result;
	}

	public function column_categoryname($item)
	{
		$actions = array(
			// 'edit'      => sprintf('<a href="?page=%s&action=%s&cid=%s">Edit</a>',$_REQUEST['page'],'edit',$item['ID']),
			'delete' => sprintf('<a href="?page=%s&action=%s&cid=%s">Delete</a>', $_REQUEST['page'], 'delete', $item['woocategoryid']),
		);

		return sprintf('%1$s %2$s', $item['categoryname'], $this->row_actions($actions));
	}

	public function column_cb($item)
	{
		return sprintf(
			'<input type="checkbox" name="mergecategory[]" value="%s" />', $item['woocategoryid']
		);
	}
	public function search_box($text, $input_id)
	{?>
	<p class="search-box">
	  <label class="screen-reader-text" for="<?php echo $input_id ?>"><?php echo $text; ?>:</label>
	  <input type="search" id="<?php echo $input_id ?>" name="s" value="<?php echo isset($_REQUEST['s']) ? $_REQUEST['s'] : ''; ?>" />
	  <?php submit_button($text, 'button', false, false, array('id' => 'search-submit'));?>
  </p>
<?php }

}
