<?php

/**
 * This is the main admin class of your plugin. It extends the basic class WPDKWordPressAdmin, that gives you some facilities to handle operation in WordPress administrative
 * area.
 *
 * @class              WWSAdminMenu
 * @author             =undo= <info@wpxtre.me>
 * @copyright          Copyright (C) 2012-2014 wpXtreme Inc. All Rights Reserved.
 * @date               2014-03-07
 * @version            1.0.0
 *
 */
class WWSAdminMenu extends WPDKWordPressAdmin {

  /**
   * This is the minumun capability required to display admin menu item
   *
   * @brief Menu capability
   */
  const MENU_CAPABILITY = 'manage_options';

  /**
   * Create and return a singleton instance of WWSAdminMenu class
   *
   * @brief Init
   *
   * @return WWSAdminMenu
   */
  public static function init()
  {
    static $instance = null;
    if ( is_null( $instance ) ) {
      $instance = new self();
    }

    return $instance;
  }


  /**
   * Create an instance of WWSAdminMenu class
   *
   * @brief Construct
   *
   * @return WWSAdminMenu
   */
  public function __construct()
  {
    /**
     * @var WWSCONNECTOR $plugin
     */
    $plugin = $GLOBALS['WWSCONNECTOR'];
    parent::__construct( $plugin );

  }

  /**
   * Called by WPDKWordPressAdmin parent when the admin head is loaded
   *
   * @brief Admin head
   */
  public function admin_head()
  {
    // You can enqueue here all the scripts and css styles needed by your plugin, through wp_enqueue_script and wp_enqueue_style functions   */
  }




static function wwsconnector_setting_function()
  {
    register_setting('wwsconnector-settings-group','wwsconnector_username');
    register_setting('wwsconnector-settings-group','wwsconnector_password');
    register_setting('wwsconnector-settings-group','wwsconnector_token');
    register_setting('wwsconnector-settings-group','wwsconnector_imagefolder');
    register_setting('wwsconnector-settings-group','wwsconnector_pricebook');
    register_setting('wwsconnector-settings-group','wwsconnector_guest_account_id');
  }

  /**
   * Called when WordPress is ready to build the admin menu.
   *
   * @brief Admin menu
   */
  public function admin_menu()
  {
    
     $icon_menu = $this->plugin->imagesURL . 'logo-16x16.png';

     $menus = array(
      'wws_connector' => array(
        'menuTitle'  => __( 'WWS Connector' ),
        // WordPress capability needed to see this menu - if current WordPress user does not have this capability, the menu will be hidden
        'capability' => self::MENU_CAPABILITY,
        // Icon to show in menu - see above
        'icon'       => $icon_menu,
        // Create two submenu item to this main menu
        'subMenus'   => array(
          array(
            'menuTitle'      => __( 'Synchronize Products' ), // Menu item shown as first submenu in main navigation menu
            'pageTitle'      => __( 'Synchronize Products - Syncronize products from woocommerce to salesforce' ),  // The web page title shown when this item is clicked

            // WordPress capability needed to see this menu item - if current WordPress user does not have this capability, this menu item will be hidden
            'capability'     => self::MENU_CAPABILITY,
            'viewController' => 'WWSProductsViewController', // Function called whenever this menu item is clicked
          ),
          // Add a divider to separate the first submenu item from the second
          WPDKSubMenuDivider::DIVIDER,
          array(
            'menuTitle'      => __( 'Synchronize Category' ), // Menu item shown as first submenu in main navigation menu
            'pageTitle'      => __( 'Synchronize Category - Syncronize Category from woocommerce to salesforce' ),  // The web page title shown when this item is clicked

            // WordPress capability needed to see this menu item - if current WordPress user does not have this capability, this menu item will be hidden
            'capability'     => self::MENU_CAPABILITY,
            'viewController' => 'WWSCategoriesViewController', // Function called whenever this menu item is clicked
          ),
          // Add a divider to separate the first submenu item from the second
          WPDKSubMenuDivider::DIVIDER,
          array(
            'menuTitle'      => __( '<span style="color:orange;">Synchronize Users</span> (full version only)' ), // Menu item shown as first submenu in main navigation menu
            'pageTitle'      => __( 'Synchronize Users - Syncronize Users from woocommerce to salesforce' ),  // The web page title shown when this item is clicked

            // WordPress capability needed to see this menu item - if current WordPress user does not have this capability, this menu item will be hidden
            'capability'     => self::MENU_CAPABILITY,
            'viewController' => 'WWSUsersViewController', // Function called whenever this menu item is clicked
          ),
          // Add a divider to separate the first submenu item from the second
          WPDKSubMenuDivider::DIVIDER,
          array(
            'menuTitle'      => __( '<span style="color:orange;">Synchronize Orders</span> (full version only)' ), // Menu item shown as first submenu in main navigation menu
            'pageTitle'      => __( 'Synchronize Orders - Syncronize Orders from woocommerce to salesforce' ),  // The web page title shown when this item is clicked

            // WordPress capability needed to see this menu item - if current WordPress user does not have this capability, this menu item will be hidden
            'capability'     => self::MENU_CAPABILITY,
            'viewController' => 'WWSOrdersViewController', // Function called whenever this menu item is clicked
          ),
           // Add a divider to separate the first submenu item from the second
          WPDKSubMenuDivider::DIVIDER,
          array(
            'menuTitle'      => __( 'Settings' ), // Menu item shown as first submenu in main navigation menu
            'pageTitle'      => __( 'Settings - woocommerce salesforce connector settings' ),  // The web page title shown when this item is clicked

            // WordPress capability needed to see this menu item - if current WordPress user does not have this capability, this menu item will be hidden
            'capability'     => self::MENU_CAPABILITY,
            'viewController' => 'WWSSetingsViewController', // Function called whenever this menu item is clicked
          )
       ),
      $position=4
      )
    );
    // Physically build the menu added to main navigation menu when this plugin is activated
    WPDKMenu::renderByArray( $menus );
  }

}
