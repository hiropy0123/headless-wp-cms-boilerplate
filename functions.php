<?php
add_theme_support('post-thumbnails', array('post'));
add_theme_support('menus');

// custom post type
require_once(dirname(__FILE__) . '/inc/post-type.php');

// api custom settings
require_once(dirname(__FILE__) . '/inc/api.php');

// wp admin config
require_once(dirname(__FILE__) . '/inc/admin.php');

