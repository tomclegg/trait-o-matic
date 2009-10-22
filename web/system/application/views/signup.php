<?php
if (!isset($top_current_tab))
  $top_current_tab = "/query/";
require ('top.php');
?>
	<div id="body"><div>
		<div id="main">
			<div class="two-column">
				<div class="column">
					<h3>3. Sign Up</h3>
					<p>We require that users log in to retrieve results.<!-- Create an account here, or <a href="#">attach your data to an existing account</a>. --></p>
				</div>
				<div class="last column">
<?php if (validation_errors()): ?>
					<div class="error"><?php echo validation_errors('<div>', '</div>'); ?></div>
<?php endif; ?>
					<form enctype="multipart/form-data" name="signup-form" id="signup-form" method="POST" action="/query/">
						<div class="wrapper">
							<p><label class="label">Name<br>
							<input type="text" class="text" name="username" size="40" id="username" value="<?php echo form_error('username') ? '' : (isset($username) ? $username : set_value('username')); ?>"></label></p>
							<p><label class="label">Email<span class="description"> (optional)</span><br>
							<input type="text" class="text" name="email" size="40" id="email" value="<?php echo form_error('email') ? '' : set_value('email'); ?>"></label></p>
							<p><label class="label">Password<br>
							<input type="password" class="password" name="password" size="40" id="password"></label></p>
							<p><label class="label">Verify<br>
							<input type="password" class="password" name="verify-password" size="40" id="verify-password"></label></p>
						</div>
<?php if (isset($job)): ?>
						<input type="hidden" name="job" id="job" value="<?php echo $job; ?>">
<?php endif; ?>
					<p class="submit"><span class="label"></span><input type="submit" name="submit-signup-form" id="submit-signup-form" value="Submit"></p>
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