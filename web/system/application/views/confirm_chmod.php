<?php
require ('top.php');
?>
	<div id="body"><div>
		<div id="main">
			<h3><?php if (isset($heading)): echo htmlspecialchars($heading); else: ?>Settings Updated<?php endif; ?></h3>
			<p><a href="/results/">Return to your results &rarr;</a></p>
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