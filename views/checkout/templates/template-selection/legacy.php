<?php
/**
 * Template File: Basic Pricing Table.
 *
 * To see what methods are available on the product variable, @see inc/models/class-products.php.
 *
 * This template can also be overridden using template overrides.
 * See more here: https://help.wpultimo.com/article/335-template-overrides.
 *
 * @since 2.0.0
 * @param array $products List of product objects.
 * @param string $name ID of the field.
 * @param string $label The field label.
 */

// Exit if accessed directly
defined('ABSPATH') || exit;

if (!$should_display) {

  echo "<div></div>";

  return;

} // end if;

$sites = array_map('wu_get_site', isset($sites) ? $sites : array());

$categories = isset($categories) ? $categories : array();

$customer_sites_category = __('Your Sites', 'wp_ultimo');

$customer_sites = isset($customer_sites) ? array_map('intval', $customer_sites) : array();

?>

<?php if (empty($sites)) : ?>

<div 
  class="wu-text-center wu-bg-gray-100 wu-rounded wu-uppercase wu-font-semibold wu-text-xs wu-text-gray-700 wu-p-4"
>

	<?php _e('No Site Templates Found.', 'wp-ultimo'); ?>

</div>

<?php else : ?>

<div class="themes-php wu-styling">

  <div class="wrap wu-template-selection">

    <?php

    /**
     * Allow developers to hide the title.
     */
    if (apply_filters('wu_step_template_display_header', true)) :

		?>

      <h2>

        <?php _e('Pick your Template', 'wp-ultimo'); ?>

        <span class="title-count theme-count">

      		<?php echo count($sites); ?>

        </span>

      </h2>

    <?php endif; ?>

    <div class="wp-filter">

      <div class="wp-filter-responsive">

        <h4><?php _e('Template Categories', 'wp-ultimo'); ?></h4>

        <select class="">

          <option value="">
            
	          <?php _e('All Templates', 'wp-ultimo'); ?>
          
          </option>

          <?php if (!empty($customer_sites)) : ?>

            <option value="<?php echo esc_attr($customer_sites_category); ?>">
            
		          <?php echo $customer_sites_category; ?>
            
            </option>

          <?php endif; ?>

	        <?php foreach ($categories as $category) : ?>

            <option value="<?php echo esc_attr($category); ?>">
            
		          <?php echo $category; ?>
            
            </option>

          <?php endforeach; ?>

        </select>

      </div>

      <ul class="filter-links wp-filter-template">

        <li class="selector-inactive">

          <a
            href="#" 
            data-category=""
            :class="$parent.template_category === '' ? 'current' : ''" 
            v-on:click.prevent="$parent.template_category = ''"
          >
            
            <?php _e('All Templates', 'wp-ultimo'); ?>
          
          </a>

        </li>

        <?php if (!empty($customer_sites)) : ?>

          <li class="selector-inactive">

            <a 
              href="#" 
              data-category="<?php echo esc_attr($customer_sites_category); ?>"
              :class="$parent.template_category === '<?php echo esc_attr($customer_sites_category); ?>' ? 'current' : ''" 
              v-on:click.prevent="$parent.template_category = '<?php echo esc_attr($customer_sites_category); ?>'"
            >
          
		          <?php echo $customer_sites_category; ?>
          
            </a>

          </li>

        <?php endif; ?>

	      <?php foreach ($categories as $category) : ?>

          <li class="selector-inactive">

            <a 
              href="#" 
              data-category="<?php echo esc_attr($category); ?>"
              :class="$parent.template_category === '<?php echo esc_attr($category); ?>' ? 'current' : ''" 
              v-on:click.prevent="$parent.template_category = '<?php echo esc_attr($category); ?>'"
            >
          
		          <?php echo $category; ?>
          
            </a>

          </li>

        <?php endforeach; ?>

      </ul>

    </div>

    <div class="theme-browser rendered">

      <div class="wu-grid wu-grid-cols-1 sm:wu-grid-cols-2 md:wu-grid-cols-<?php echo $cols; ?> wu-gap-4 wp-clearfix">

	      <?php $i = 0; foreach ($sites as $site) : ?>

          <?php if ($site->get_type() !== 'site_template' && !in_array($site->get_id(), $customer_sites, true)) { continue; } ?>

          <?php $is_template = $site->get_type() === 'site_template'; ?>

          <?php $categories = array_merge($site->get_categories(), !$is_template ? array($customer_sites_category) : array()) ?>

          <div 
            class="theme" 
            tabindex="<?php echo $i; ?>" 
            aria-describedby="<?php echo $site->get_id(); ?>-action <?php echo $site->get_id(); ?>-name" 
            data-slug="<?php echo $site->get_id(); ?>"
            v-show="!$parent.template_category || <?php echo esc_attr(json_encode($categories)); ?>.join(',').indexOf($parent.template_category) > -1"  
            v-cloak
          >

            <div class="theme-screenshot">

              <img 
                src="<?php echo $site->get_featured_image(); ?>" 
                alt="<?php echo $site->get_title(); ?>"
              >

            </div>

            <a
              <?php echo $is_template ? $site->get_preview_url_attrs() : sprintf('href="%s" target="_blank"', $site->get_active_site_url()); ?>
              class="more-details" 
              id="<?php echo $site->get_id(); ?>-action"
            >

		          <?php $is_template ? _e('View Template', 'wp-ultimo') : _e('View Site', 'wp-ultimo'); ?>

            </a>

            <div class="wu-flex theme-name-header wu-items-center wu-relative">

              <h2 class="theme-name wu-flex-grow wu-h-full" id="<?php echo $site->get_id(); ?>-name">

                <?php echo $site->get_title(); ?>
                
              </h2>

              <div class="theme-actions wu-flex">

                <button 
                  class="button button-primary" 
                  type="button" 
                  v-on:click.prevent="$parent.template_id = <?php echo esc_attr($site->get_id()); ?>"
                >

                  <span v-if="$parent.template_id == <?php echo esc_attr($site->get_id()); ?>"><?php _e('Selected', 'wp-ultimo'); ?></span>

                  <span v-else><?php _e('Select', 'wp-ultimo'); ?></span>

                </button>

              </div>

            </div>

          </div>

        <?php
        
        $i++;
        
        endforeach;
	
        ?>

      </div>

    </div>

    <div class="theme-overlay"></div>

    <p class="no-themes">

	    <?php _e('No Templates Found', 'wp-ultimo'); ?>
        
    </p>

  </div>

</div>

<?php endif; ?>
