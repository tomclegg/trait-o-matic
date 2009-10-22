<?php
if (!isset($top_current_tab))
  $top_current_tab = "/results/";
require ('top.php');
?>
	<div id="body"><div>
		<div id="main">
			<div class="two-column">
				<div class="column">
					<h3>Log In</h3>
					<p>If you have forgotten your password, re-submit your query and create a new account.</p>
				</div>
				<div class="last column">
<?php if (isset($error)): ?>
					<div class="error"><div><?php echo $error; ?></div></div>
<?php endif; ?>
					<form name="results-form" id="results-form" method="POST" action="<?php if (isset($redirect)): echo $redirect; else: ?>/results/<?php endif; ?>">
						<div class="wrapper">
							<p><label class="label">Name<br>
							<input type="text" class="text" name="username" size="40" id="username"></label></p>
							<p><label class="label">Password<br>
							<input type="password" class="password" name="password" size="40" id="password"></label></p>
						</div>
						<p class="submit"><input type="submit" name="submit-results-form" id="submit-results-form" value="Submit"></p>
					</form>
				</div>
			</div>
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