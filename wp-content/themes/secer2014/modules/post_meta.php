<style >
/* 自定义字段 */
.btn_demo,.btn_download{ margin: 10px 0 0px 10px; float: left; font-size: 16px; font-family:"Microsoft YaHei",Verdana,Arial,Helvetica,sans-serif; }
.btn_demo a span, .btn_download a span { float: left; display: block; color: #fff; padding: 0 14px; line-height: 40px; cursor: pointer; font-size: 20px; }
.btn_demo a { -webkit-transition: 0.5s ease all; float: left; display: block; background: url(images/demo.png) #f95155 no-repeat 0px center; padding-left: 40px; border: none; border-left: 0px solid #CC4D00; height: 40px; color: #567a82; text-decoration: none; }
.btn_download a { -webkit-transition: 0.5s ease all; float: left; display: block; background: url(images/download.png) #16A085 no-repeat 0px center; padding-left: 40px; border: none; border-left: 0px solid #CC4D00; height: 40px; color: #567a82; text-decoration: none; }
.btn_download a:hover { -webkit-transform: rotate(360deg) scale(1.1,1.1); -moz-transform: rotate(360deg) scale(1.1,1.1); border-radius: 0px; -webkit-border-radius: 0px; -moz-border-radius: 0px; -khtml-border-radius: 0px; border-left: 8px solid #16A085; }
.btn_demo a:hover{ -webkit-transform: rotate(360deg) scale(1.1,1.1); -moz-transform: rotate(360deg) scale(1.1,1.1); border-radius: 0px; -webkit-border-radius: 0px; -moz-border-radius: 0px; -khtml-border-radius: 0px; border-left: 8px solid #f95155; }
/*友链*/
</style>
<?php
/*
* 在这里添加我们的自定义字段
*
* Link:http://www.cnsecer.com/2974.html
*/
 
// 设置自定义字段的留空时（没有设置时）的默认值
 
$demo_def = "";
$download_one_def = "";
$download_two_def = "";
$download_def ="";

$demo = get_post_meta($post->ID, 'demo', true);
$download_one = get_post_meta($post->ID, 'download_one', true);
$download_two = get_post_meta($post->ID, 'download_two', true);
$download  = get_post_meta($post->ID, 'download', true);
// 检查这个字段是否有值
if (empty ( $demo)) { //如果值为空，输出默认值
	$demo = $demo_def;
}

if (empty ( $download_one)) { //如果值为空，输出默认值
	$download_one = $download_one_def;
}

if (empty ( $download_two)) { //如果值为空，输出默认值
	$download_two = $download_def;
}

if (empty ( $download)) { //如果值为空，输出默认值
	$download = $download_def;
}
//如果不为空 则输出
if(!empty($download_one)){

	echo "<h3>预览和下载</h3>";

	echo '
	        <a class="dl" target="_blank" href=" ';  echo $demo; echo ' "> 
	          <i class="fa fa-external-link"></i>
	          <span>演示地址</span>
	        </a>
		 ';

	echo '
	    
	        <a class="dl" target="_blank" href=" ';  echo $download_one; echo ' "> 
	          <i class="fa fa-cloud-download"></i>
	          <span>主题下载</span>
	        </a>
		 ';

	echo '
	    
	        <a  class="dl" target="_blank" href=" ';  echo $download_two; echo ' "> 
	          <i class="fa fa-cloud-download"></i>
	          <span>备胎下载</span>
	        </a>
		 ';
	echo '<div class="clearfix"> </div>';
}


if(!empty($download)){

	echo "<h3>下载</h3>";

	echo '
	    <div class="btn_download">
	        <a target="_blank" href=" ';  echo $download; echo ' "> 
	          <span>文件下载</span>
	        </a>
		</div> ';

	echo '<div class="clearfix"> </div>';
}


?>

