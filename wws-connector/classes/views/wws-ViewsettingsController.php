<?php

/**
 * @class           WWSSetingsViewController
 * @author          webkul team
 * @copyright       Copyright (C) 2015 webkul software pvt Ltd. All Rights Reserved.
 * @date            2015-12-24
 * @version         1.0.0
 *
 */
class WWSSetingsViewController extends WPDKViewController {

  /**
   * Return a singleton instance of WWSSetingsViewController class
   *
   * @brief Singleton
   *
   * @return WWSSetingsViewController
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
   * Create an instance of WWSSetingsViewController class
   *
   * @brief Construct
   *
   * @return WWSSetingsViewController
   */
  public function __construct()
  {
    // Build the container, with default header
    parent::__construct( 'WWSSetingsViewController', '' );
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
		<h2>Woocommerce Connector Configuration settings</h2>

		<form method="post" action="options.php">
		<?php settings_fields( 'wwsconnector-settings-group' ); ?>
		<?php do_settings_sections('wwsconnector-settings-group');?>
		<?php
			$return_data_all = $this->getSalesforceData();
			$folder = array();
			$pricebook = array();
			if (isset($return_data_all['error'])) {
				$this->my_error_notice12('error','Error',$return_data_all['error']);
			}else{
				$folder = $return_data_all['folder'];
				$pricebook = $return_data_all['pricebook'];
			}
		?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php _e('Salesforce User Name');?></th>
				<td>
				<input name="wwsconnector_username" type="text" id="wwsconnector_username" value="<?php echo esc_attr(get_option('wwsconnector_username')); ?>" />
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Salesforce User Password');?></th>
				<td>
				<input name="wwsconnector_password" type="password" id="wwsconnector_password" value="<?php echo esc_attr(get_option('wwsconnector_password'));?>" />
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Salesforce User Token');?></th>
				<td >
				<input name="wwsconnector_token" type="text" id="wwsconnector_token" value="<?php echo esc_attr(get_option('wwsconnector_token'));?>" />
				</td>
			</tr>
			<tr valign="top">
				<?php
				if (!empty($folder)) {
				?>
				<th scope="row"><?php _e('Choose Document Folder');?></th>
				<td>
					<select name="wwsconnector_imagefolder" id="wwsconnector_imagefolder" value="">
						<option value="">please Select Folder name</option>
						<?php 
						foreach($folder as $doc){
						?>
						<option value="<?php echo $doc->value;?>"<?php if(get_option('wwsconnector_imagefolder')==$doc->value) echo 'selected';?>  ><?php echo $doc->text;?></option>
						<?php }	?>
					</select>
				</td>
				<?php } ?>
			</tr>
			<?php ?>
			<tr valign="top">
			<?php
				if (!empty($pricebook)) {
			?>
				<th scope="row"><?php _e('Choose Price Book');?></th>
				<td >
					<select name="wwsconnector_pricebook" id="wwsconnector_pricebook" value="">
						<option value="" <?php echo get_option('wwsconnector_pricebook');?> >please Select Price Book</option>
						<?php
						foreach($pricebook as $bk){
						?>
						<option value="<?php echo $bk->value;?>"<?php if(get_option('wwsconnector_pricebook')==$bk->value) echo 'selected';?>  ><?php echo $bk->text;?></option>
						<?php }	?>
					</select>
				</td>
				<?php }	?>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Salesforce Guest Account Id');?></th>
				<td>
				<input name="wwsconnector_guest_account_id" type="text" id="wwsconnector_guest_account_id" value="<?php echo esc_attr(get_option('wwsconnector_guest_account_id')); ?>" />
				</td>
			</tr>
			<tr>
				<td></td>
				<td></td>
			</tr>
		</table>
		<?php submit_button();?>
		</form><?php
	} 
	function my_error_notice12($type, $title, $message)
	{
	?>
		<div class="<?php echo $type; ?> notice is-dismissible">
			<p>
				<strong><?php echo $title; ?></strong> <?php echo $message; ?>.
			</p>
			<button class="notice-dismiss" type="button">
				<span class="screen-reader-text">Dismiss this notice.</span>
			</button>
		</div>
		<?php
	}
	function getSalesforceData()
	{
  		$username=esc_attr(get_option('wwsconnector_username'));
		$pass=esc_attr(get_option('wwsconnector_password'));
		$token=esc_attr(get_option('wwsconnector_token'));
		// require_once "services/soapclient/SforcePartnerClient.php";
		require_once "services/soapclient/SforceEnterpriseClient.php";
  		if(!empty($username)&&!empty($pass)&&!empty($token))
		{
			try {
				$saleforceConnection = new SforceEnterpriseClient();
				$cwd = __DIR__;
				$saleforceConnection->createConnection($cwd."/services/enterprise.wsdl.xml");
				$saleforceConnection->login($username,$pass.$token);
				$documentFolder=$saleforceConnection->query("SELECT Id,Name,Type FROM Folder where Type='Document'");
				foreach($documentFolder->records as $key=> $folder){
					$document[$key]= new stdclass();
					$document[$key]->text=$folder->Name;					
					$document[$key]->value=$folder->Id;
				}
				$salesforce_data_all['folder'] = $document;
				$pricebook = $saleforceConnection->query("SELECT Id,Name FROM Pricebook2 where IsStandard=false");
				foreach($pricebook->records as $key=> $book){
					$sfpricebook[$key]= new stdclass();
					$sfpricebook[$key]->text=$book->Name;					
					$sfpricebook[$key]->value=$book->Id;
				}
				$salesforce_data_all['pricebook'] = $sfpricebook;
				return $salesforce_data_all;
			} catch (Exception $e) {
				$e->faultstring;
				$error = $e->getMessage();
				return array('error'=>$error);
			}
		}
	}
}
