<?php
// カスタム投稿タイプの作成
// news
add_action('init', 'create_news_post_type');
function create_news_post_type() {
  register_post_type( 'news', [
    'labels' => [
      'name' => 'お知らせ',
      'singular_name' => 'news'
    ],
    'public' => true,
    'has_archive' => true,
    'menu_position' => 10,
    'show_in_rest' => true,
    'show_in_graphql' => true,
    'menu_icon' => 'dashicons-megaphone',
    'graphql_single_name' => 'news',
    'graphql_plural_name' => 'news',
    'supports' => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'revisions' ),
    'taxonomies' => array('category')
  ]);
}


// rewrite permalinks
add_action('init', 'custom_posttype_rewrite');
function custom_posttype_rewrite() {
  global $wp_rewrite;

  $wp_rewrite->add_rewrite_tag('%news%', '(news)','post_type=');
  $wp_rewrite->add_permastruct('news', '/%news%/%post_id%/', false);
}

add_filter('post_type_link', 'custom_posttype_permalink', 1, 3);
function custom_posttype_permalink($post_link, $id = 0, $leavename) {
  global $wp_rewrite;
  $post = &get_post($id);
  if (is_wp_error( $post )) {
    return $post;
  }
  if ('news' === $post->post_type){
    $newlink = $wp_rewrite->get_extra_permastruct($post->post_type);

    $newlink = str_replace('%news%', $post->post_type, $newlink);

    $newlink = str_replace('%post_id%', $post->ID, $newlink);

    $newlink = home_url(user_trailingslashit($newlink));

    return $newlink;
}

    return $post_link;
}