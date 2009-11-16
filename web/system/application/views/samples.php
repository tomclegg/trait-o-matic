<?php
if (!isset($top_current_tab))
  $top_current_tab = "/";
require ('top.php');
?>
	<div id="body"><div>
		<div id="main">
			<h3 class="description">Samples</h3>
			<div id="glider">
				<div class="scroller"><div>
<?php
foreach(array_chunk($samples, 5) as $samples_section):
?>
					<div class="section">
<?php
foreach($samples_section as $s):
$url = '/results/job/'.$s['job']['id'].'/'.rawurlencode(ereg_replace(" ", "_", $s['name']));
?>
						<p class="link"><a href="<?php echo htmlspecialchars($url); ?>"><img src="/media/placeholder.gif" width="100" height="100" alt="Picture"><br><?php echo $s['htmllabel']; ?></a></p>
<?php
endforeach;
?>
					</div>
<?php
endforeach;
?>
				</div></div>
<?php
if(count($samples) > 5):
?>
				<div class="prev"></div>
				<div class="nav"></div>
				<div class="next"></div>
<?php
endif;
?>
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