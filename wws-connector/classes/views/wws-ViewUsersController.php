<?php

/**
 * @class           WWSUsersViewController
 * @author          webkul team
 * @copyright       Copyright (C) 2015 webkul software pvt Ltd. All Rights Reserved.
 * @date            2015-12-24
 * @version         1.0.0
 *
 */
class WWSUsersViewController extends WPDKViewController
{

    /**
     * Return a singleton instance of WWSUsersViewController class
     *
     * @brief Singleton
     *
     * @return WWSUsersViewController
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
     * Create an instance of WWSUsersViewController class
     *
     * @brief Construct
     *
     * @return WWSUsersViewController
     */
    public function __construct()
    {
        // Build the container, with default header
        parent::__construct('WWSUsersViewController', '');
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
        ?>
        <div style="background: #ffb8b8; border: 1px solid #ff8383;">
        <p>
        <a style="font-weight: bold;" target="_blank" href="https://store.webkul.com/Wordpress-WooCommerce-Salesforce-Connector.html">
            Upgrade to the Full edition to use full version
        </a>
        </p>
        <small>
        Do you have full version?<br>
        <ol>
            <li>Please uninstall this version.</li>
            <li>Remove files for this version</li>
            <li>Now install new version</li>
        </ol>
        </small>
        </div>
        <?php
    }

}