</section>
<footer class="footer">
    <div class="footer-inner">
        <div class="copyright pull-left">
        <span style="color: #339966;"><a style="color: #339966;" href="http://yunpan.cn/Q778SaELp7IXs" target="_blank">全站资源</a></span> 提取码 <span style="color: #ff0000;">a696</span> <span style="color: #339966;"><a style="color: #339966;" href="http://pan.baidu.com/s/1qW2UxmW" target="_blank">备份地址</a></span> 全部收集自互联共享                                                                                                                                                                                                                        <span style="color: #339966;"><a style="color: #339966;" href="http://ling_leixing.jd-app.com/wp-login.php?action=register" target="_blank">注册</a></span>
        </div>
        <div class="trackcode pull-right">
            <?php if( dopt('d_track_b') ) echo dopt('d_track'); ?>
        </div>
    </div>
</footer>

<?php 
wp_footer(); 
global $dHasShare; 
if($dHasShare == true){ 
	echo'<script>with(document)0[(getElementsByTagName("head")[0]||body).appendChild(createElement("script")).src="http://bdimg.share.baidu.com/static/api/js/share.js?v=89860593.js?cdnversion="+~(-new Date()/36e5)];</script>';
}  
if( dopt('d_footcode_b') ) echo dopt('d_footcode'); 
?>
</body>
</html>