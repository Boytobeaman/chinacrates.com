<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
wp_enqueue_style( 'siteorigin-preview-style', plugin_dir_url( __FILE__ ) . '../css/live-editor-preview.css', array(), SITEORIGIN_PANELS_VERSION );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="http://gmpg.org/xfn/11">
	<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">
	<?php wp_head(); ?>
<!-- q-shine.net.cn Baidu tongji analytics -->
<script>
var _hmt = _hmt || [];
(function() {
var hm = document.createElement("script");
hm.src = "https://hm.baidu.com/hm.js?fbb248e05f28d765b52b43006420f164";
var s = document.getElementsByTagName("script")[0];
s.parentNode.insertBefore(hm, s);
})();
</script>
</head>

<body <?php body_class(); ?>>
	<div id="content" class="site-content">
		<div class="entry-content">
			<?php
			if( !empty( $_POST['live_editor_panels_data'] ) ) {
				$data = json_decode( wp_unslash( $_POST['live_editor_panels_data'] ), true );
				if(
					!empty( $data['widgets'] ) && (
						!class_exists( 'SiteOrigin_Widget_Field_Class_Loader' ) ||
						method_exists( 'SiteOrigin_Widget_Field_Class_Loader', 'extend' )
					)
				) {
					$data['widgets'] = siteorigin_panels_process_raw_widgets( $data['widgets'] );
				}
				echo siteorigin_panels_render( 'l' . md5( serialize( $data ) ), true, $data);
			}
			?>
		</div><!-- .entry-content -->
	</div>
	<?php wp_footer(); ?>
</body>
</html>
