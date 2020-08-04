<?php //カテゴリ用のパンくずリスト
/**
 * Simplicity WordPress Theme
 * @author: yhira
 * @link: https://wp-simplicity.com
 * @license: http://www.gnu.org/licenses/gpl-2.0.html GPL v2 or later
 */
if ( !defined( 'ABSPATH' ) ) exit;

//パンくずリストを表示するとき ?>
<?php if ( !is_front_page() ): //個別ページでパンくずリストを表示する場合
$root_text = get_theme_text_breadcrumbs_home();
$root_text = apply_filters('breadcrumbs_page_root_text', $root_text);
?>
<div id="breadcrumb" class="breadcrumb breadcrumb-page" itemscope itemtype="https://schema.org/BreadcrumbList">
  <?php $count = 0;
  $per_ids = array_reverse(get_post_ancestors($post->ID));
    ?><div class="breadcrumb-home" itemscope itemtype="https://schema.org/ListItem" itemprop="itemListElement"><span class="fa fa-home fa-fw" aria-hidden="true"></span><a href="<?php echo esc_url(get_home_url()); ?>" itemprop="item"><span itemprop="name"><?php echo esc_html($root_text); ?></span></a><meta itemprop="position" content="1" /><?php echo (count($per_ids) == 0) ? '' : '<span class="sp"><span class="fa fa-angle-right" aria-hidden="true"></span></span>' ?></div>
  <?php foreach ( $per_ids as $par_id ){
    $count += 1;
    $page = get_post($par_id);
    $post_title = $page->post_title;
    $post_title = apply_filters('breadcrumbs_page_title', $post_title, $page);
    ?><div class="breadcrumb-item" itemscope itemtype="https://schema.org/ListItem" itemprop="itemListElement"><span class="fa fa-file-o fa-fw" aria-hidden="true"></span><a href="<?php echo esc_url(get_page_link( $par_id ));?>" itemprop="item"><span itemprop="name"><?php echo esc_html($post_title); ?></span></a><meta itemprop="position" content="<?php echo $count + 1; ?>" /><?php echo (count($per_ids) == $count) ? '' : '<span class="sp"><span class="fa fa-angle-right" aria-hidden="true"></span></span>' ?></div>
  <?php } ?>
</div><!-- /#breadcrumb -->
<?php endif; ?>
