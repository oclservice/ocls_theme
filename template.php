<?php
/**
 * @file
 * Contains the theme's functions to manipulate Drupal's default markup.
 *
 * Complete documentation for this file is available online.
 * @see https://drupal.org/node/1728096
 */

/**
 * Gets a string describing which multi-site the $base_url is referring to.
 *
 * @return string
 *   A string describing which multi-site the $base_url is referring to,
 *   if $base_url does not contain one of the
 *   supported site names, then FALSE is returned.
 */
function ocls_theme_get_multi_site() {
  global $base_url;
  $multi_sites = array(
    'centennial',
    'conestoga',
    'fleming',
    'georgian',
    'loyalist',
    'seneca',
    'niagara',
  );
  foreach ($multi_sites as $site) {
    if (strpos($base_url, $site) !== FALSE) {
      return $site;
    }
  }
  // To prevent potential problems in supporting a theme without a name
  // the default is assumed to always be CORE OCLS (College Object Repository).
  return 'ocls';
}

/**
 * Implements hook_preprocess().
 */
function ocls_theme_preprocess_islandora_compound_prev_next(&$variables) {
  // Prevent the parent thumbnail from displaying in the compound object
  // Navigation block.
  if (isset($variables['parent_tn'])) {
    $variables['parent_tn'] = NULL;
  }
}

/**
 * Implements hook_form_alter().
 */
function ocls_theme_form_user_login_alter(&$form, &$form_id) {
  // Add a horizontal rule beneath the login button.
  $form['actions']['submit']['#suffix'] = "<hr>";
}

/**
 * Implements hook_preprocess_template().
 */
function ocls_theme_preprocess_islandora_solr(&$variables) {
  //Setup message when search brings no results    
  $variables['custom_no_search_results_msg'] = theme_get_setting('ocls_custom_no_search_results_msg');
  if (empty($variables['custom_no_search_results_msg'])) {
    $variables['custom_no_search_results_msg'] = t('Sorry, but your search returned no results.');
  }
}

/**
 * Implements hook_preprocess().
 */
function ocls_theme_preprocess_islandora_solr_metadata_display(array &$variables) {
  // Retrieve an objects search result, hiding it on collection pages.
  if (theme_get_setting('ocls_show_basic_record') > 0 &&
    !in_array('islandora:collectionCModel', $variables['islandora_object']->models)) {
    $pid = $variables['islandora_object']->{'id'};
    $q = "islandora/search/$pid";
    $params = array(
      'type' => 'dismax',
      'q' => $q,
    );
    $search_result_view = islandora_solr($variables['islandora_object']->{'id'}, $params);
    $variables['search_result'] = $search_result_view;
  }
}

/**
 * Implements hook_preprocess().
 */
function ocls_theme_preprocess_islandora_solr_metadata_description(array &$variables) {
  $variables['combine'] = FALSE;
}

/**
 * Implements hook_preprocess().
 */
function ocls_theme_preprocess_islandora_solr_wrapper(&$variables) {
  // Add the result count, display switch and solr sort blocks where we need them.
  $variables['islandora_solr_result_count'] = "<div class='solr-result-label-count'><h1>Search Results</h1>"
    . "<div>" . t("Displaying ") . $variables['islandora_solr_result_count'] . "</div></div>";
  module_load_include('inc', 'islandora_solr', 'includes/blocks');
  $variables['solr_display_switch'] = ocls_theme_block_render('islandora_solr', 'display_switch');
  $variables['solr_sort'] = ocls_theme_block_render('islandora_solr', 'sort');
}

/**
 * Implements hook_preprocess_page().
 */
function ocls_theme_preprocess_page(&$variables) {
  $custom_logo_url = theme_get_setting('ocls_custom_logo_url');
    
  if (!empty($custom_logo_url)) {
    $variables['front_page'] = $custom_logo_url;
  }

  if ($variables['is_front'] == FALSE) {
    // Add the search for the header.
    if (module_exists('islandora_solr')) {
      module_load_include('inc', 'islandora_solr', 'includes/blocks');
      $variables['islandora_header_search'] = ocls_theme_block_render('islandora_solr', 'simple');
    }
  }
}

/**
 * Implements hook_preprocess_html().
 * @param unknown $variables
 */
function ocls_theme_preprocess_html(&$variables) {
  if ($variables['is_front'] == TRUE) {
    $slideshow_pids = theme_get_setting('ocls_slideshow_pids');
    $pid_datastream = theme_get_setting('ocls_background_dsid');
    $pids = explode(',', $slideshow_pids);
    if (count($pids) > 0) {
      $pid = $pids[array_rand($pids)];
      $obj = islandora_object_load($pid);
      $variables['slideshow_data'] = array(
        'background_image' => "islandora/object/$pid/datastream/$pid_datastream/view",
        'background_link' => l($obj->label, "islandora/object/$pid")
      );
    }
    module_load_include('inc', 'islandora_solr', 'includes/blocks');
    $block = islandora_solr_block_view('simple');
    $variables['islandora_front_page_search'] = render($block['content']);
  }

  // Add a CSS class to the body element, so we can tell what site we are on.
  $site = ocls_theme_get_multi_site();
  $variables['classes_array'][] = $site;
}

/**
 * Helper function to render block dynamically.
 *
 * @param unknown $module
 * @param unknown $delta
 * @param string $as_renderable
 * @return unknown
 */
function ocls_theme_block_render($module, $delta, $as_renderable = FALSE) {
  $block = block_load($module, $delta);
  $block_content = _block_render_blocks(array($block));
  $build = _block_get_renderable_array($block_content);
  if ($as_renderable) {
    return $build;
  }
  $block_rendered = drupal_render($build);
  return $block_rendered;
}

/**
 * Implements hook_form_alter().
 */
function ocls_theme_form_islandora_solr_simple_search_form_alter(&$form, &$form_state, $form_id) {
  $link = array(
    '#markup' => "<div class='adv-search-lnk'>" . l(t("Advanced Search"), "advanced-search", array('attributes' => array('class' => array('adv_search')))) . "</div>",
  );
  $form['simple']['advanced_link'] = $link;
  $form['simple']['islandora_simple_search_query']['#attributes']['placeholder'] = t("Search All Collections");
  $form['simple']['islandora_simple_search_query']['#title_display'] = 'invisible';

  if (drupal_is_front_page()) {
    $form['simple']['header_text'] = array(
      '#weight' => -2,
      '#markup' => "<h1>" . theme_get_setting('ocls_theme_search_heading') . "</h1>",
    );
  if (theme_get_setting('ocls_theme_search_text')) {
    $form['simple']['hag_theme_text_search_text'] = array(
      '#weight' => -1,
      '#markup' => "<div class='front-description-wrapper'><p class='simple-search-text'>" . theme_get_setting('ocls_theme_search_text') . "</p></div>",
    );
  }
  $menu_name = variable_get('menu_header_menu_links_source', 'menu-header-menu');
  $tree = menu_tree($menu_name);
  $form['simple']['hag_theme_search_main_menu'] = array(
    '#prefix' => '<div class="front-menu-wrapper">',
    '#weight' => 8,
    '#markup' => drupal_render($tree),
  );
  $menu_name = variable_get('menu_front-collection-links_links_source', 'menu-front-collection-links');
  $tree = menu_tree($menu_name);
  $form['simple']['hag_theme_collection_main_menu'] = array(
    '#suffix' => '</div>',
    '#weight' => 9,
    '#markup' => drupal_render($tree),
  );
  }
}

/**
 * Implements hook_form_alter().
 */
function ocls_theme_form_alter(&$form, &$form_state, $form_id){
  if($form_id == "views_exposed_form"){
    if (isset($form['dc_title'])) {
      $form['dc_title']['#attributes']['placeholder'] = t("Search Within Collection...");
    }
  }
}

/* Convert hexdec color string to rgb(a) string */

function ocls_theme_hex2rgba($color, $opacity = false) {
  $default = 'rgb(0,0,0)';
  //Return default if no color provided
  if(empty($color))
    return $default;
  //Sanitize $color if "#" is provided
  if ($color[0] == '#' ) {
    $color = substr( $color, 1 );
  }

  //Check if color has 6 or 3 characters and get values
  if (strlen($color) == 6) {
    $hex = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] );
  } elseif ( strlen( $color ) == 3 ) {
    $hex = array( $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] );
  } else {
    return $default;
  }
  //Convert hexadec to rgb
  $rgb =  array_map('hexdec', $hex);
  //Check if opacity is set(rgba or rgb)
  if($opacity){
          if(abs($opacity) > 1)
            $opacity = 1.0;
   $output = 'rgba('.implode(",",$rgb).','.$opacity.')';
  } else {
    $output = 'rgb('.implode(",",$rgb).')';
  }

  //Return rgb(a) color string
  return $output;
}
