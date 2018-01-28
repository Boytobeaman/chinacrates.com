
<?php get_header(); ?>
	    <!-- 右侧产品内容开始 -->
	    <div class="col-sm-9 col-xs-8 col-md-10 col-lg-10" id="newscontentright">
	    	 <ol class="breadcrumb">
	    	   <li><a href="/index.html">首页</a></li>
	    	   <li class=""><a href="/plastic-box-news/">塑料周转箱新闻</a></li>
	    	   <li class="active"><?php the_title() ?></li>
	    	 </ol>
	    	 <h3><?php single_cat_title() ?></h3>
	    	 	<div id="home-loop" >
	    	 	       <?php
	    	 	       	the_post();
	    	 	       ?>
	                <div class="">
	                	<div class="thumbnail post-item">
	                        <div class="single-post-title"><h3><?php the_title() ?></h3></div>
	                        <div class="single-post-content"><?php the_content() ?></div>
	                        <div class="post-nav">
	                        	<?php previous_post_link('上一篇：%link'); ?><br />
	                        	<?php next_post_link('下一篇：%link'); ?>
	                        </div>
	                    </div>
	                </div>
	    	 	                    
	    	 	</div>
	    	 	
	    </div>
	 </div>  <!--  row end -->
	  <!-- 右侧内容结束 -->

	  </div>
	





	<? wp_footer(); ?>
</body>
</html>











