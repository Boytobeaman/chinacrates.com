<html>
<head>
	<title><?php _e('Widget Preview', 'siteorigin-widgets') ?></title>
	<meta id="Viewport" name="viewport" width="width=960, initial-scale=0.25">
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
<body>
	<?php
	the_widget( $class, $instance, array(
		'before_widget' => '<div class="widget-preview-wrapper">',
		'after_widget' => '</div>',
	) );
	siteorigin_widget_print_styles();
	?>
</body>
</html>