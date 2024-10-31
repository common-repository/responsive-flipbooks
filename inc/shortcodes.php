<?php
add_shortcode('flipbook', 'responsive_flipbooks_gallery_shortcode');
function responsive_flipbooks_gallery_shortcode( $atts ) {

  extract(shortcode_atts(
      array(
        'id' => ''
      ), $atts)
  );
  ob_start(); ?>
  <div class="flipbook-wrapper">
    <?php
    $templater = new \PeakResponsiveFlipbooks\ResponsiveFlipbooksFlipbookTemplater($atts['id']);
    echo $templater->getFlipbookMaterialHTML();
    ?>
  </div>
  <?php $content = ob_get_clean();

  return $content;
}

/**
 * Add Flipbook button to tinyMCE editor.
 */
add_action('admin_enqueue_scripts', 'responsive_flipbooks_register_ajax_script');
function responsive_flipbooks_register_ajax_script() {
  wp_register_script('responsive_flipbooks_tinymce_addons', plugin_dir_url(__FILE__).'classes/js/flipbook_button_plugin.js', array(), '', false);
  wp_localize_script(
    'responsive_flipbooks_tinymce_addons',
    'ajax_object',
    array(
      'ajaxurl' =>admin_url('admin-ajax.php'),
      'bookNonce' => wp_create_nonce('flip-book-nonce'),
    )
  );
  wp_enqueue_script('responsive_flipbooks_tinymce_addons');
}

add_action('wp_ajax_responsive_flipbooks_get_flipbook_ajax', 'responsive_flipbooks_get_flipbook_ajax');
function responsive_flipbooks_get_flipbook_ajax() {
  $nonceField = sanitize_text_field($_POST['bookNonce']);

  if(!wp_verify_nonce($nonceField, 'flip-book-nonce'))
    die('restricted');

  $samples = responsive_flipbooks_build_tinymce_options_from_ids();
  $response = json_encode($samples);

  header("Content-Type: application/json");
  echo $response;
  wp_die();
}

function responsive_flipbooks_build_tinymce_options_from_ids() {
  $args = array(
    'post_type' => 'flipbooks',
    'posts_per_page' => -1
  );
  $all_flipbook_samples = query_posts( $args );

  $select_options_samples = array();
  foreach ($all_flipbook_samples as $flipbook_samples) {
    $select_options_samples[] = array(
      'title' => get_the_title($flipbook_samples->ID),
      'id' => $flipbook_samples->ID,
      'content' => $flipbook_samples->post_content
    );
  }
  return $select_options_samples;
}
