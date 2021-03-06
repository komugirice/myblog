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