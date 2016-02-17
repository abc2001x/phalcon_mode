<body>
	{% include "header.volt" %}

	<!-- 主菜单 -->
	<div class="main">
		{% include "nav.volt" %}

		<style type="text/css">
			.content p{text-align:center;font-size:18px;line-height:2;}
			.content img{margin-top:100px;margin-bottom:30px;}
			.content a{color:#8AABFE;}
			.content span{color:#f00;margin-left:10px;}
		</style>
		<div class="content">
			<p><img src="/admin/images/error_404.png" /></p>
			<p>抱歉，你所访问的页面不存在</p>
			<p><a href="javascript:history.go(-1);" >点击此连接返回</a><span>3</span></p>
		</div>

	</div><!-- main end -->
</body>
<script type="text/javascript">
	$(document).ready(function(){
		var time = parseInt($(".content span").text());
		var countdown = function(){if(time>0){time--;$(".content span").text(time);}else{window.location.href=$(".content a").attr("href");}};
		var timer = setInterval(countdown, 1000);
	});
	</script>