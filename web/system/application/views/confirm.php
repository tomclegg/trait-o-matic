<?php
require ('top.php');
?>
	<div id="body"><div>
		<div id="main">
			<h3><?php if (isset($heading)): echo htmlspecialchars($heading); else: ?>You&rsquo;re Done!<?php endif; ?></h3>
			<p>If you provided an email address, we&rsquo;ll send you a notification email when results are ready. You&rsquo;ll need to use the user name and password you just selected to <a href="/results/job/<?=$job?>">retrieve your results</a>.</p>
		</div>
	</div></div>
	<div id="foot"><div>
		<div id="copyright">
			<p>
				<span>Copyright &copy; MMIX President and Fellows of Harvard College<br>[{elapsed_time} s]</span>
			</p>
		</div>
	</div></div>
</body>
</html>