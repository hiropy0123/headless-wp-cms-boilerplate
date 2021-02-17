<?php
// add_filter('manage_posts_columns', 'posts_columns_id', 5);
// add_action('manage_posts_custom_column', 'posts_custom_id_columns', 5, 2);
add_filter('manage_pages_columns', 'posts_columns_id', 5);
add_action('manage_pages_custom_column', 'posts_custom_id_columns', 5, 2);
// スラッグ名の表示
function posts_columns_id($defaults){
    $defaults['wps_post_id'] = __('ID');
    $defaults['slug'] = "スラッグ";

    return $defaults;
}
function posts_custom_id_columns($column_name, $id){
    if($column_name === 'wps_post_id'){
      echo $id;
    }
    if( $column_name == 'slug' ) {
        $post = get_post($id);
        $slug = $post->post_name;
        echo esc_attr($slug);
    }
}


// 管理画面のCSS編集 dashboard_browser_nag_style
function dashboard_browser_nag_style() {
  echo '<style>#dashboard_browser_nag .dismiss { display: none !important; }</style>';
}
add_action('admin_print_styles', 'dashboard_browser_nag_style');

// Pre Get Post
// カスタム投稿の並び順を変更
add_action( 'pre_get_posts', 'items_post_sort' );
function items_post_sort( $query ) {
  // メインクエリではない場合return
  if ( !is_admin() || !$query->is_main_query() ){
    return;
  }

}

// ダッシュボードの概要にカスタム投稿も表示
add_action( 'dashboard_glance_items', 'add_custom_post_dashboard_widget' );
function add_custom_post_dashboard_widget() {
  $args = array(
    'public' => true,
    '_builtin' => false
  );
  $output = 'object';
  $operator = 'and';

  $post_types = get_post_types( $args, $output, $operator );
  foreach ( $post_types as $post_type ) {
    $num_posts = wp_count_posts( $post_type->name );
    $num = number_format_i18n( $num_posts->publish );
    $text = _n( $post_type->labels->singular_name, $post_type->labels->name, intval( $num_posts->publish ) );
    if ( current_user_can( 'edit_posts' ) ) {
      $output = '<a href="edit.php?post_type=' . $post_type->name . '">' . $num . '&nbsp;' . $text . '</a>';
    }
    echo '<li class="post-count ' . $post_type->name . '-count">' . $output . '</li>';
  }
}

// 一覧画面から削除するカラム
add_filter( 'manage_pages_columns', 'delete_column');
add_filter( 'manage_posts_columns', 'delete_column');
function delete_column($columns) {
    unset($columns['comments']);
    return $columns;
}

// remove admin menus
function remove_admin_menus() {
  global $menu;
  unset($menu[25]);       // コメント
}
add_action('admin_menu', 'remove_admin_menus');

//管理バーの項目削除
function remove_bar_menus( $wp_admin_bar ) {
  $wp_admin_bar->remove_menu( 'wp-logo' );
  $wp_admin_bar->remove_menu( 'comments' );
  $wp_admin_bar->remove_menu( 'customize' );
  // $wp_admin_bar->remove_menu( 'site-name' );
  $wp_admin_bar->remove_menu( 'new-content' );
  // $wp_admin_bar->remove_menu( 'updates' );
}
add_action('admin_bar_menu', 'remove_bar_menus', 201);

// add_editor_style
add_action( 'enqueue_block_editor_assets', 'my_custom_editor_style' );
function my_custom_editor_style() {
  //現在適用しているテーマのeditor-style.cssを読み込む
  $editor_style_url = get_theme_file_uri('/editor-style.css');
  wp_enqueue_style( 'theme-editor-style', $editor_style_url );
}

// remove generator
remove_action('wp_head','wp_generator');
remove_action('wp_head', 'wp_shortlink_wp_head');
remove_action('wp_head', 'feed_links_extra', 3);
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('admin_print_scripts', 'print_emoji_detection_script');
remove_action('admin_print_styles', 'print_emoji_styles');
remove_action('wp_head', 'rel_canonical');

// remove EditURI
remove_action('wp_head','rsd_link');
// remove wlwmanifest
remove_action('wp_head', 'wlwmanifest_link');
// remove wp version param from any enqueued scripts
function vc_remove_wp_ver_css_js( $src ) {
    if ( strpos( $src, 'ver=' ) )
        $src = remove_query_arg( 'ver', $src );
    return $src;
}
add_filter( 'style_loader_src', 'vc_remove_wp_ver_css_js', 9999 );
add_filter( 'script_loader_src', 'vc_remove_wp_ver_css_js', 9999 );

// auto update
add_filter( 'allow_major_auto_core_updates', '__return_true' );
add_filter( 'auto_update_plugin', '__return_true' );