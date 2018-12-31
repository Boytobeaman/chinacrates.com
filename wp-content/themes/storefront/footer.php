<?php
/**
 * The template for displaying the footer.
 *
 * Contains the closing of the #content div and all content after
 *
 * @package storefront
 */

?>

		</div><!-- .col-full -->
	</div><!-- #content -->

	<?php do_action( 'storefront_before_footer' ); ?>

	<footer id="colophon" class="site-footer" role="contentinfo">
		<div class="col-full">

			<div class="text-center" id="footerContainer">
				<div class="col-lg-3 col-md-3 col-sm-3 ">
					<div><p>JOIN US:</p></div>	
					<div>		
					<ul class="forFooter">
						<li class="forFooterli"><a href=""><img alt="Facebook" src="/img/socialIcon/grey_facebook.png" data-pin-nopin="true"></a></li>
						<li class="forFooterli"><a href=""><img alt="Twitter" src="/img/socialIcon/grey_twitter.png" data-pin-nopin="true"></a></li>
						<li class="forFooterli"><a href=""><img alt="Linkedin" src="/img/socialIcon/grey_linkedin.png" data-pin-nopin="true"></a></li>
						<li class="forFooterli"><a href=""><img alt="Youtube" src="/img/socialIcon/grey_youtube.png" data-pin-nopin="true"></a></li>
					</ul>
					</div>	
					<div class="clearfix"></div>
					<hr class="visible-xs">
				</div>							
				<div class="col-lg-9 col-md-9 col-sm-9 ">
					<p class="text-center"> DESIGNED BY ANTHONY LU</p>
					<p class="text-center">© Copyright 2019 SHANGHAI JOIN PLASTIC PRODUCTS CO.,LTD.</p>
				</div>
				
			</div>

		</div><!-- .col-full -->
	</footer><!-- #colophon -->

	<?php do_action( 'storefront_after_footer' ); ?>

</div><!-- #page -->
<link rel="stylesheet" href="/css/bootstrap.min.css">
<script src="/js/jquery.min.js"></script>
<script src="/js/bootstrap.min.js"></script>
<script src="/js/myownscript.js"></script>
<?php wp_footer(); ?>

</body>
</html>
