<?php
/**
 * Sample preferences class. In this class you define the model of your tree preferences.
 *
 * @class              PreferencesModel
 * @author             =undo= <info@wpxtre.me>
 * @copyright          Copyright (C) 2012-2014 wpXtreme Inc. All Rights Reserved.
 * @date               2014-03-07
 * @version            1.0.0
*/

class PreferencesModel extends WPDKPreferences {

  /**
   * The v name used on database
   *
   * @brief Preferences name
   *
   * @var string
   */
  const PREFERENCES_NAME = 'wpdk-controls-preferences-5';

  /**
   * Your own v property
   *
   * @brief Preferences version
   *
   * @var string $version
   */
  public $version = WPXSAMPLE5_VERSION;

  /**
   * This is the pointer to your own tree Preferences
   *
   * @brief Settings
   *
   * @var MySettingBranch $settings
   */
  public $settings;

  /**
   * Return an instance of PreferencesModel class from the database or onfly.
   *
   * @brief Init
   *
   * @return PreferencesModel
   */
  public static function init()
  {
    return parent::init( self::PREFERENCES_NAME, __CLASS__, '1.0' );
  }

  /**
   * Set the default preferences
   *
   * @brief Default preferences
   */
  public function defaults()
  {
    $this->settings = new MySettingBranch();
  }

}

/**
 * Sample of preferences branch model
 *
 * @class           MySettingBranch
 * @author          =undo= <info@wpxtre.me>
 * @copyright       Copyright (C) 2012-2014 wpXtreme Inc. All Rights Reserved.
 * @date            2014-03-07
 * @version         1.0.0
 *
 */
class MySettingBranch extends WPDKPreferencesBranch {

  // You can define your comodity constants
  const MY_VALUE = 'wpdk-sample-5-value';
  // Interface pf preferences branch

  public $value_text_box;
  public $value_check_box;
  public $value_combo_box;
  public $value_swipe;

  /**
   * Set the default preferences
   *
   * @brief Default preferences
   */
  public function defaults()
  {
    // Se the default for the first time or reset preferences
    $this->value_text_box  = '123';
    $this->value_check_box = '';
    $this->value_combo_box = '';
    $this->value_swipe     = '';
  }

  /**
   * Update this branch
   *
   * @brief Update
   */
  public function update()
  {
    // Update and sanitize from post data
    $this->value_text_box  = absint( $_POST[ self::MY_VALUE ] ); // note the constant in view
    $this->value_check_box = 'value_check_box';
    $this->value_combo_box = 'value_combo_box';
    $this->value_swipe     = 'value_swipe';
  }

}
