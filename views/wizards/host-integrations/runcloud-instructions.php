<?php
/**
 * Runcloud instructions view.
 *
 * @since 2.0.0
 */
?>
<h1>
<?php _e('Instructions', 'wp-ultimo'); ?></h1>

<p class="wu-text-lg wu-text-gray-600 wu-my-4 wu-mb-6">

  <?php _e('You’ll need to get your', 'wp-ultimo'); ?> <strong><?php _e('API Key', 'wp-ultimo'); ?></strong> <?php _e('and', 'wp-ultimo'); ?> <strong><?php _e('API Secret', 'wp-ultimo'); ?></strong>, <?php _e('as well as find the', 'wp-ultimo'); ?> <strong><?php _e('Server ID', 'wp-ultimo'); ?></strong> <?php _e('and', 'wp-ultimo'); ?> <strong><?php _e('APP ID', 'wp-ultimo'); ?></strong> <?php _e('for your WordPress application', 'wp-ultimo'); ?>.

</p>

<h3 class="wu-m-0 wu-py-4 wu-text-lg" id="step-1-getting-the-api-key-and-secret">
  <?php _e('Getting the API Key and API Secret', 'wp-ultimo'); ?>
</h3>

<p class="wu-text-sm">
  <?php _e('On your RunCloud admin panel, click the cog icon (settings) to go to the settings page', 'wp-ultimo'); ?>.
</p>

<div class="">
  <img class="wu-w-full" src="<?php echo wu_get_asset('runcloud-1.png', 'img/hosts'); ?>">
</div>

<p class="wu-text-center"><i><?php _e('Settings Page Link', 'wp-ultimo'); ?></i></p>

<p class="wu-text-sm"><?php _e('On the new page, click in the', 'wp-ultimo'); ?><b> <?php _e('API Key', 'wp-ultimo'); ?> </b> <?php _e('menu item on the left', 'wp-ultimo'); ?>.</p>

<div class="">
  <img class="wu-w-full" src="<?php echo wu_get_asset('runcloud-2.png', 'img/hosts'); ?>">
</div>
<p class="wu-text-center"><i><?php _e('API Key page link', 'wp-ultimo'); ?></i></p>
<p class="wu-text-sm"> <?php _e('Copy the', 'wp-ultimo'); ?> <b> <?php _e('API Key and Secret values', 'wp-ultimo'); ?> </b>, <?php _e('we will need them in the next steps', 'wp-ultimo'); ?>. <b> <?php _e('Make sure the RunCloud API toggle is turned ON', 'wp-ultimo'); ?>, </b> <?php _e('otherwise RunCloud won’t accept WP Ultimo API calls', 'wp-ultimo'); ?>.</p>
<div class="">
  <img class="wu-w-full" src="<?php echo wu_get_asset('runcloud-3.png', 'img/hosts'); ?>">
</div>
<p class="wu-text-center"><i><?php _e('Copy the API Key and API Secret values', 'wp-ultimo'); ?></i></p>

  <h3 class="wu-m-0 wu-py-4 wu-text-lg" id="step-1-getting-the-api-key-and-secret">
  <?php _e('Getting the Server and App IDs', 'wp-ultimo'); ?>
</h3>
<p class="wu-text-sm"><?php _e('To find what are the server and app ids for your application, navigate to your web application manage page inside the RunCloud panel. Once you are there, you’ll be able to extract the values from the URL', 'wp-ultimo'); ?>.</p>
<div class="">
  <img class="wu-w-full" src="<?php echo wu_get_asset('runcloud-4.png', 'img/hosts'); ?>">
</div>
<p class="wu-text-center"><i><?php _e('Server ID is the first one, the second one is App ID.', 'wp-ultimo'); ?></i></p>
<p class="wu-text-sm"><?php _e('Save the Server and APP id values as they will be necessary in the next step', 'wp-ultimo'); ?>.</p>
