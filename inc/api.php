<?php
// CORS
function my_customize_rest_cors() {
    remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );

    add_filter( 'rest_pre_serve_request', function( $value ) {
        header( 'Access-Control-Allow-Origin: *' );
        header( 'Access-Control-Expose-Headers: X-WP-Total, X-WP-TotalPages', false );

        return $value;
  });
}
add_action( 'rest_api_init', 'my_customize_rest_cors', 15 );

// カテゴリ名を取得する関数を登録
add_action( 'rest_api_init', 'register_category_name' );
add_action( 'rest_api_init', 'register_category_slug' );
function register_category_name() {
// register_rest_field関数を用いget_category_name関数からカテゴリ名を取得し、追加する
    register_rest_field( 'posts', // 投稿タイプ
        'category_name',
        array(
            'get_callback'  => 'get_category_name'
        )
    );
    register_rest_field( 'news', // 投稿タイプ
        'category_name',
        array(
            'get_callback'  => 'get_category_name'
        )
    );
}
// スラッグ名の取得
function register_category_slug() {
    register_rest_field( 'posts',
        'category_slug',
        array(
            'get_callback' => 'get_slug_name'
        )
    );
    register_rest_field( 'news',
        'category_slug',
        array(
            'get_callback' => 'get_slug_name'
        )
    );
}

//$objectは現在の投稿の詳細データが入る
function get_category_name( $object ) {
    $category = get_the_category($object[ 'id' ]);
    $cat_name = $category[0]->cat_name;
    return $cat_name;
}

function get_slug_name( $object ) {
    $category = get_the_category($object[ 'id' ]);
    $cat_slug = $category[0]->category_nicename;
    return $cat_slug;
}


// This enables the orderby=menu_order for Posts
add_filter( 'rest_post_collection_params', 'filter_add_rest_orderby_params', 10, 1 );
/**
 * Add menu_order to the list of permitted orderby values
 */
function filter_add_rest_orderby_params( $params ) {
    $params['orderby']['enum'][] = 'menu_order';
  return $params;
}


// ---
// カスタムAPI の作成 エンドポイントの設定
// ------------------------------------------------------------------------
add_action('rest_api_init', 'add_my_custom_endpoint');
function add_my_custom_endpoint() {
    // ニュース取得API
    register_rest_route('api/v1', '/news', array(
        'methods' => 'GET',
        'callback' => 'news_api'
    ));
    // カテゴリー取得API
    register_rest_route('api/v2', '/categories', array(
        'methods' => 'GET',
        'callback' => 'categories_api'
    ));
}


// ----------------------------------------------------------------------
// ニュース取得API
// /wp-json/api/v1/news
function news_api(WP_REST_Request $request) {

  // 検索条件
  $args = array(
    'posts_per_page' => 10,
    'post_type' => 'news',
    'orderby' => 'date',
    'order'   => 'DESC',
    'post_status' => 'publish',
  );
  // URL params
  $p_author = $request->get_param('author');
  if ($p_author) {
    $args['author'] = $p_author;
  }
  $p_category = $request->get_param('category');
  if ($p_category) {
    $args['cat'] = $p_category;
  }
  $p_per_page = $request->get_param('per_page');
  if ($p_per_page) {
    $args['posts_per_page'] = $p_per_page;
  }
  $p_page = $request->get_param('page');
  if ($p_page) {
    $args['paged'] = $p_page;
  }
  $post_status = $request->get_param('post_status');
  if ($post_status && current_user_can('manage_options')) {
    $args['post_status'] = $post_status;
  }

  // $argsに記事の取得条件を設定する
  $the_query = new WP_Query($args);
  $data = array();
  while ($the_query->have_posts()) {
    // 次のpostに進む
    $the_query->the_post();

    // postを整形
    $post = get_post();
    $categories = get_the_category();

    // 日付のフォーマット
    $date = date("Y.m.d", strtotime($post->post_date));

    // 本文を整形 WP Blockエディッタ wp:blockタグの削除
    $body = preg_replace("/<!-- wp:.* -->/u", "", get_the_content());
    $body = preg_replace("/<!-- \/wp:.* -->/u", "", $body);
    $body = preg_replace("/\\n/u", "", $body);

    // レスポンスデータ
    $data[] = array(
      'id' => $post->ID,
      'date' => $date,
      'title' => get_the_title(),
      'category' => array(
        'name' => $categories[0]->cat_name,
        'slug' => $categories[0]->slug
      ),
      'body' => $body
    );
  }

  // レスポンス
  $response = new WP_REST_Response($data);
  $response->header('X-WP-Total', $the_query->found_posts);
  $response->header('X-WP-TotalPages', $the_query->max_num_pages);
  $response->set_status(200);
  return $response;
}


// ----------------------------------------------------------------------
// カテゴリー取得API
// /wp-json/api/v2/categories
function categories_api(WP_REST_Request $request) {
  // 検索条件
  $args = array(
    'taxonomy' => 'category',
    'orderby' => 'menu_order',
    'order'  => 'ASC'
  );

  // $argsに記事の取得条件を設定する
  $terms = get_terms('category', $args);

  $data = array();

  foreach( $terms as $term ) {
    $data[] = array(
      'name' => $term->name,
      'slug' => $term->slug
    );
  }

  // レスポンス
  $response = new WP_REST_Response($data);
  $response->header('X-WP-Total', $the_query->found_posts);
  $response->header('X-WP-TotalPages', $the_query->max_num_pages);
  $response->set_status(200);
  return $response;
}

function wp_get_previous_post() {
  $prev = get_previous_post();
  if (empty($prev)) {
    $prev = get_posts(array('order' => 'DESC', 'posts_per_page' => 1));
    if (!empty($prev[0])) {
      $prev = $prev[0];
    }
  }
  return $prev;
}

function wp_get_next_post() {
  $next = get_next_post();
  if (empty($next)) {
    $next = get_posts(array('order' => 'DESC', 'posts_per_page' => 1));
    if (!empty($next[0])) {
      $next = $next[0];
    }
  }
  return $next;
}

// format api reponse  wp-rest-apiの不要な項目を削除する
add_filter('rest_prepare_post', 'remove_unused_post_data', 10, 3);
add_filter('rest_prepare_page', 'remove_unused_post_data', 10, 3);

function remove_unused_post_data($response, $post, $request) {
  $params = $request->get_params();
  if (isset($params['id'])) {
    // 記事詳細 /wp-json/wp/v2/posts/:id
    unset($response->data['excerpt']);
    unset($response->data['guid']);
    unset($response->data['featured_media']);
    unset($response->data['comment_status']);
    unset($response->data['ping_status']);
    unset($response->data['sticky']);
    unset($response->data['template']);
    unset($response->data['format']);
    unset($response->data['slug']);
    unset($response->data['date_gmt']);
    unset($response->data['modified_gmt']);
    $response->remove_link('self');
    $response->remove_link('collection');
    $response->remove_link('about');
    $response->remove_link('author');
    $response->remove_link('replies');
    $response->remove_link('version-history');
    $response->remove_link('predecessor-version');
    $response->remove_link('https://api.w.org/attachment');
    $response->remove_link('https://api.w.org/term');
  } else {
    // 記事一覧 /wp-json/wp/v2/posts
    unset($response->data['excerpt']);
    unset($response->data['guid']);
    unset($response->data['featured_media']);
    unset($response->data['comment_status']);
    unset($response->data['ping_status']);
    unset($response->data['sticky']);
    unset($response->data['template']);
    unset($response->data['format']);
    unset($response->data['slug']);
    unset($response->data['date_gmt']);
    unset($response->data['modified_gmt']);
    $response->remove_link('self');
    $response->remove_link('collection');
    $response->remove_link('about');
    $response->remove_link('author');
    $response->remove_link('replies');
    $response->remove_link('version-history');
    $response->remove_link('predecessor-version');
    $response->remove_link('https://api.w.org/attachment');
    $response->remove_link('https://api.w.org/term');
  }
  return $response;
}