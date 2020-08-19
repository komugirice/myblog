<?php //子テーマ用関数
if ( !defined( 'ABSPATH' ) ) exit;

//子テーマ用のビジュアルエディタースタイルを適用
add_editor_style();


//以下に子テーマ用の関数を書く

function wp_add_common_styles() {
    wp_enqueue_style( 'common-css', get_stylesheet_directory_uri() . '/common.css' );
    wp_enqueue_style( 'normalize-css', get_stylesheet_directory_uri() . '/normalize.css' );
}
add_action( 'wp_enqueue_scripts', 'wp_add_common_styles' );

function twpp_change_sort_order( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) {
        return;
    }
    if ( $query->is_category() ) {
        // $query->set( 'orderby', 'modified' );
        // $query->set( 'order', 'DESC' );
        // var_dump($query);
    }
}
// add_action( 'pre_get_posts', 'twpp_change_sort_order' ); 

function my_main_query( $query ) {
    if ( is_admin() ) {
        return;
    }
    // $query->set( 'post_type', array( 'qiitaapplication' ) );
    $query->set( 'post_type', 'any' );
}
// add_action( 'pre_get_posts', 'my_main_query' );

//投稿にサブメニューを追加
function add_post_submenu() {
    add_submenu_page( 'edit.php', 'QiitaApplication', '■QiitaApplication', 'activate_plugins', 'edit.php?post_status=publish&cat=7' );
    add_submenu_page( 'edit.php', 'メンタル', '■メンタル', 'activate_plugins', 'edit.php?post_status=publish&cat=27' );
}
add_action('admin_menu', 'add_post_submenu');