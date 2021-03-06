<?php
function open_framework_preprocess_html(&$vars) {
  // theme option variables
  $vars['front_heading_classes'] = theme_get_setting('front_heading_classes'); 
  $vars['breadcrumb_classes'] = theme_get_setting('breadcrumb_classes'); 
  $vars['border_classes'] = theme_get_setting('border_classes'); 
  $vars['corner_classes'] = theme_get_setting('corner_classes'); 
  $vars['body_bg_type'] = theme_get_setting('body_bg_type'); 
  $vars['body_bg_classes'] = theme_get_setting('body_bg_classes'); 
  $vars['body_bg_path'] = theme_get_setting('body_bg_path'); 
}

function open_framework_preprocess_page(&$vars) {
  // Add page template suggestions based on the aliased path. For instance, if the current page has an alias of about/history/early, we'll have templates of:
  // page-about-history-early.tpl.php, page-about-history.tpl.php, page-about.tpl.php
  // Whichever is found first is the one that will be used.
  if (module_exists('path')) {
    $alias = drupal_get_path_alias(str_replace('/edit','',$_GET['q']));
    if ($alias != $_GET['q']) {
      $template_filename = 'page';
      foreach (explode('/', $alias) as $path_part) {
        $template_filename = $template_filename . '-' . $path_part;
        $vars['template_files'][] = $template_filename;
      }
    }
  }
  // Get the entire main menu tree
  $main_menu_tree = menu_tree_all_data('main-menu');

  // Add the rendered output to the $main_menu_expanded variables
  $vars['main_menu_expanded'] = menu_tree_output($main_menu_tree);
  
    // Primary nav
  $vars['primary_nav'] = FALSE;
  if ($vars['main_menu']) {
    // Build links
    $vars['primary_nav'] = menu_tree(variable_get('menu_main_links_source', 'main-menu'));
    // Provide default theme wrapper function
    $vars['primary_nav']['#theme_wrappers'] = array('menu_tree__primary');
  }

  // Secondary nav
  $vars['secondary_nav'] = FALSE;
  if ($vars['secondary_menu']) {
    // Build links
    $vars['secondary_nav'] = menu_tree(variable_get('menu_secondary_links_source', 'user-menu'));
    // Provide default theme wrapper function
    $vars['secondary_nav']['#theme_wrappers'] = array('menu_tree__secondary');
  }

  // Replace tabs with drop down version
  $vars['tabs']['#primary'] = _bootstrap_local_tasks($vars['tabs']['#primary']);
  
}

function open_framework_preprocess_block(&$vars) {
  // Count number of blocks in a given theme region
$vars['block_count'] = count(block_list($vars['block']->region));
}

/**
* Determines if the region has at least one block for this user
*
* @param $region
* A string containing the region name
*
* @return
* TRUE if the region has at least one block. FALSE if it doesn't.
*/

function open_framework_region_has_block($region) {
  $number_of_blocks = count(block_list($region));
  if ($number_of_blocks > 0) {
    return TRUE;
  }
  else {
    return FALSE;
  }
}

/**
* Determine the span for a blocka
*
* @param $block_count
* The number of blocks in the region
*
* @param $block_id
* The position of the block (starts at 1)
*
* @param $count_sidebars
* A boolean indicating whether sidebars should be counted
*
* @return
* The span value for the block at this location and region
*/

function open_framework_get_span($block_count, $block_id, $count_sidebars) {
  // @petechen (6.27.12) This method of applying a value to span assumes that there
  // is at least 1 block. If there are no blocks, you end up with a calculation
  // dividing by 0 generating a php error. Suggest the following change:
  
  // default span if calculations fail
  // Use this default value instead as an "else" condition below:
  // $span = 12;

  // there are 12 columsn in bootstrap
  $available_width = 12;

  if ($count_sidebars) {
    // we assume that the left and right regions have a span of 3
    // if present, we remove that much from the available width
    if (open_framework_region_has_block('sidebar_first')) {
      $available_width = $available_width - 0;
    }

    if (open_framework_region_has_block('sidebar_second')) {
      $available_width = $available_width - 0;
    }
  }

  // @petechen - surroung this condition with another if else to account for $block_count = 0
  
  if ($block_count != 0) {

  // if the number of blocks divides evenly into the available width, that's our span width
  if (($available_width % $block_count) == 0) {
    $span = $available_width / $block_count;
  }
  // if the number of blocks does not divide evenly, we look up the span widths in an array
  // where then indexes are available width, number of blocks, and block position
  // e.g. [9][2][1] is the span of the first block, out of two when the available width is 9.
  else {
    $exceptions[6][4][1] = 2;
    $exceptions[6][4][2] = 2;
    $exceptions[6][4][3] = 1;
    $exceptions[6][4][4] = 1;

    $exceptions[6][5][1] = 1;
    $exceptions[6][5][2] = 1;
    $exceptions[6][5][3] = 1;
    $exceptions[6][5][4] = 1;
    $exceptions[6][5][5] = 1;

    $exceptions[9][2][1] = 3;
    $exceptions[9][2][2] = 6;

    $exceptions[9][4][1] = 3;
    $exceptions[9][4][2] = 2;
    $exceptions[9][4][3] = 2;
    $exceptions[9][4][4] = 2;

    $exceptions[9][5][1] = 3;
    $exceptions[9][5][2] = 1;
    $exceptions[9][5][3] = 1;
    $exceptions[9][5][4] = 1;
    $exceptions[9][5][5] = 3;

    $exceptions[9][6][1] = 2;
    $exceptions[9][6][2] = 2;
    $exceptions[9][6][3] = 2;
    $exceptions[9][6][4] = 1;
    $exceptions[9][6][5] = 1;
    $exceptions[9][6][6] = 1;

    $exceptions[12][5][1] = 3;
    $exceptions[12][5][2] = 2;
    $exceptions[12][5][3] = 2;
    $exceptions[12][5][4] = 2;
    $exceptions[12][5][5] = 3;

    $span = $exceptions[$available_width][$block_count][$block_id];
  }
  return $span;
}
// @petechen: so if $block_count = 0, use this as the default
  else $span = 12;
}

/**
 * Returns HTML for status and/or error messages, grouped by type.
 */
function open_framework_status_messages($variables) {
  $display = $variables['display'];
  $output = '';

  $status_heading = array(
    'status' => t('Status message'),
    'error' => t('Error message'),
    'warning' => t('Warning message'),
  );

  // Map Drupal message types to their corresponding Bootstrap classes.
  // @see http://twitter.github.com/bootstrap/components.html#alerts
  $status_class = array(
    'status' => 'success',
    'error' => 'error',
    'warning' => 'info',
  );

  foreach (drupal_get_messages($display) as $type => $messages) {
    $class = (isset($status_class[$type])) ? ' alert-' . $status_class[$type] : '';
    $output .= "<div class=\"alert alert-block$class\">\n";
    $output .= "  <a class=\"close\" data-dismiss=\"alert\" href=\"#\">x</a>\n";

    if (!empty($status_heading[$type])) {
      $output .= '<h2 class="element-invisible">' . $status_heading[$type] . "</h2>\n";
    }

    if (count($messages) > 1) {
      $output .= " <ul>\n";
      foreach ($messages as $message) {
        $output .= '  <li>' . $message . "</li>\n";
      }
      $output .= " </ul>\n";
    }
    else {
      $output .= $messages[0];
    }

    $output .= "</div>\n";
  }
  return $output;
}

/* Search Form Block */
function open_framework_form_alter(&$form, &$form_state, $form_id) {
  if ($form_id == 'search_block_form') {
    $form['search_block_form']['#title_display'] = 'invisible';
    $form['search_block_form']['#attributes']['class'][] = 'input-medium search-query';
	$form['search_block_form']['#attributes']['placeholder'] = t('Search this site...');
    $form['actions']['submit']['#attributes']['class'][] = 'btn btn-search';
  }
}

/* Returns HTML for primary and secondary local tasks. */
function open_framework_menu_local_tasks(&$vars) {
  $output = '';

  if ( !empty($vars['primary']) ) {
    $vars['primary']['#prefix'] = '<h2 class="element-invisible">' . t('Primary tabs') . '</h2>';
    $vars['primary']['#prefix'] = '<ul class="nav nav-tabs">';
    $vars['primary']['#suffix'] = '</ul>';
    $output .= drupal_render($vars['primary']);
  }

  if ( !empty($vars['secondary']) ) {
    $vars['primary']['#prefix'] = '<h2 class="element-invisible">' . t('Primary tabs') . '</h2>';
    $vars['secondary']['#prefix'] = '<ul class="nav nav-pills">';
    $vars['secondary']['#suffix'] = '</ul>';
    $output .= drupal_render($vars['secondary']);
  }

  return $output;
}

/* Returns HTML for primary and secondary local task. */
function open_framework_menu_local_task($vars) {
  $link = $vars['element']['#link'];
  $link_text = $link['title'];
  $classes = array();

  if (!empty($vars['element']['#active'])) {
    // Add text to indicate active tab for non-visual users.
    $active = '<span class="element-invisible">' . t('(active tab)') . '</span>';

    // If the link does not contain HTML already, check_plain() it now.
    // After we set 'html'=TRUE the link will not be sanitized by l().
    if (empty($link['localized_options']['html'])) {
      $link['title'] = check_plain($link['title']);
    }
    $link['localized_options']['html'] = TRUE;
    $link_text = t('!local-task-title!active', array('!local-task-title' => $link['title'], '!active' => $active));

    $classes[] = 'active';
  }

  return '<li class="' . implode(' ', $classes) . '">' . l($link_text, $link['href'], $link['localized_options']) . "</li>\n";
}

function open_framework_menu_tree(&$vars) {
  return '<ul class="menu nav">' . $vars['tree'] . '</ul>';
}

function open_framework_menu_link(array $vars) {
  $element = $vars['element'];
  $sub_menu = '';
  
  if ($element['#below']) {
    // Ad our own wrapper
    unset($element['#below']['#theme_wrappers']);
    $sub_menu = '<ul class="dropdown-menu">' . drupal_render($element['#below']) . '</ul>';
    $element['#localized_options']['attributes']['class'][] = 'dropdown-toggle';
    $element['#localized_options']['attributes']['data-toggle'] = 'dropdown';

    // Check if this element is nested within another
    if ((!empty($element['#original_link']['depth'])) && ($element['#original_link']['depth'] > 1)) {
      // Generate as dropdown submenu
      $element['#attributes']['class'][] = 'dropdown-submenu';
    }
    else {
      // Generate as standard dropdown
      $element['#attributes']['class'][] = 'dropdown';
      $element['#localized_options']['html'] = TRUE;
      $element['#title'] .= ' <span class="caret"></span>';
    }

    // Set dropdown trigger element to # to prevent inadvertant page loading with submenu click
    $element['#localized_options']['attributes']['data-target'] = '#';
  }
  
  $output = l($element['#title'], $element['#href'], $element['#localized_options']);
  return '<li' . drupal_attributes($element['#attributes']) . '>' . $output . $sub_menu . "</li>\n";
}

/**
* Get all primary tasks including subsets
*/
function _bootstrap_local_tasks($tabs = FALSE) {
  if ($tabs == '') {
    return $tabs;
  }
  
  if (!$tabs) {
    $tabs = menu_primary_local_tasks();
  }
  
  foreach ($tabs as $key => $element) {
    $result = db_select('menu_router', NULL, array('fetch' => PDO::FETCH_ASSOC))
      ->fields('menu_router')
      ->condition('tab_parent', $element['#link']['path'])
      ->condition('context', MENU_CONTEXT_INLINE, '<>')
      ->condition('type', array(MENU_DEFAULT_LOCAL_TASK, MENU_LOCAL_TASK), 'IN')
      ->orderBy('weight')
      ->orderBy('title')
      ->execute();
  
    $router_item = menu_get_item($element['#link']['href']);
    $map = $router_item['original_map'];
  
    $i = 0;
    foreach ($result as $item) {
      _menu_translate($item, $map, TRUE);
  
      //only add items that we have access to
      if ($item['tab_parent'] && $item['access']) {
        //set path to that of parent for the first item
        if ($i === 0) {
          $item['href'] = $element['#link']['href'];
        }
  
        if (current_path() == $item['href']) {
          $tabs[$key][] = array(
          '#theme' => 'menu_local_task',
          '#link' => $item,
          '#active' => TRUE,
          );
        }
        else {
          $tabs[$key][] = array(
          '#theme' => 'menu_local_task',
          '#link' => $item,
          );
        }
  
        //only count items we have access to.
        $i++;
      }
    }
  }
  
  return $tabs;
}

function open_framework_item_list($variables) {
  $items = $variables['items'];
  $title = $variables['title'];
  $type = $variables['type'];
  $attributes = $variables['attributes'];
  $output = '';

  if (isset($title)) {
    $output .= '<h3>' . $title . '</h3>';
  }

  if (!empty($items)) {
    $output .= "<$type" . drupal_attributes($attributes) . '>';
    $num_items = count($items);
    foreach ($items as $i => $item) {
      $attributes = array();
      $children = array();
      $data = '';
      if (is_array($item)) {
        foreach ($item as $key => $value) {
          if ($key == 'data') {
            $data = $value;
          }
          elseif ($key == 'children') {
            $children = $value;
          }
          else {
            $attributes[$key] = $value;
          }
        }
      }
      else {
        $data = $item;
      }
      if (count($children) > 0) {
        // Render nested list.
        $data .= theme_item_list(array('items' => $children, 'title' => NULL, 'type' => $type, 'attributes' => $attributes));
      }
      if ($i == 0) {
        $attributes['class'][] = 'first';
      }
      if ($i == $num_items - 1) {
        $attributes['class'][] = 'last';
      }
      $output .= '<li' . drupal_attributes($attributes) . '>' . $data . "</li>\n";
    }
    $output .= "</$type>";
  }
 
  return $output;
}
