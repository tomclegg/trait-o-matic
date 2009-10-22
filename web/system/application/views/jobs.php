<?php
require ('top.php');

$myhostname = trim(`hostname`);

// Return something reasonable to display in html, suppress CI 'no such key' warnings
function nice($array, $key)
{
  if (!array_key_exists($key, $array)) return "";
  $v = $array[$key];
  if (ereg("_locator$", $key))
    {
      $label = $v;
      if (ereg("warehouse://[^/]*/(.*)", $v, $regs))
	$label = $regs[1];
      return "<a href=\"$v\">".htmlspecialchars(substr($label,0,8))."...</a>";
    }
  return htmlspecialchars($v);
}

// Return a button that will submit the given genotype/phenotype data as a new job
function reprocess_button($jobdata, $username=FALSE)
{
  if (!array_key_exists('genotype_locator', $jobdata)) return "";

  $gloc = $jobdata['genotype_locator'];
  if (!ereg("^warehouse://", $gloc))
    $gloc = "warehouse:///$gloc";

  $ploc = "";
  if (array_key_exists('profile_locator', $jobdata))
    {
      $ploc = $jobdata['profile_locator'];
      if (!ereg("^warehouse://", $ploc)) $ploc = "warehouse:///$ploc";
    }

  return '<form style="display:inline" action="/query/" method="POST">'
    . '<input type="hidden" name="genotype_locator" value="'
    . $gloc
    . '"><input type="hidden" name="phenotype_locator" value="'
    . $ploc
    . '"><input type="hidden" name="username" value="'
    . htmlspecialchars($username)
    . '"><input type="submit" name="submit-from-warehouse-form" value="Reprocess">'
    . '</form>';
}

// Return a link to the results for the given username
function results_link($job_or_username)
{
  if (!sizeof($job_or_username))
    return "";
  else if (is_array($job_or_username))
    return '<a href="/results/job/'
      . (0 + $job_or_username['id'])
      . '">'
      . (array_key_exists('user', $job_or_username) ? htmlspecialchars($job_or_username['user']['username']) : "-")
      . '</a>';
  else
    return '<a href="/samples/'
      . urlencode(ereg_replace(" ", "_", $job_or_username))
      . '">View</a>';
}

function sortable_datetime($datetime)
{
  return "<span style=\"display:none\">"
    . ereg_replace(" ", "T", $datetime)
    . "</span>$datetime";
}

?>
	<div id="body"><div>
		<div id="main">
			<h3 class="description">Browse</h3>
			<div class="two-column">
				<div class="column">
				</div>
				<div class="last column">
					<p id="allele-frequency-legend" class="legend"><strong>View:</strong><br>
					<span class="rare">Public</span><br>
					<span class="minor">Shared but not public</span><br></p>
				</div>
			</div>
			<div id="results">
			<h3 class="toggle">Marked as shared or public in my database<span class="count">(<?php echo count($jobs); ?>)</span></h3>
			<div class="data">
				<table class="sortable data" width="100%">
					<col width="40%">
					<col width="5%">
					<col width="20%">
					<col width="20%">
					<thead>
					<tr>
					<th scope="col" class="text"><div>Username</div></th>
					<th scope="col" class="text"><div>Public</div></th>
					<th scope="col" class="date-iso"><div>Submitted</div></th>
					<th scope="col" class="sort-first-descending date-iso"><div>Processed</div></th>
					</tr>
					</thead>
					<tbody>
<?php
foreach ($jobs as $j):
?>
					<tr class="<?php if ($j['public']<1): ?>minor<?php else: ?>rare<?php endif; ?>">
					<td scope="col"><?=results_link($j)?></td>
					<td scope="col"><?php echo $j['public'] ? "Public" : "-"; ?></td>
					<td scope="col"><?=sortable_datetime($j['submitted'])?></td>
					<td scope="col"><?=sortable_datetime($j['processed'])?></td>
					</tr>
<?php endforeach; ?>
<?php if (!sizeof($jobs)): ?>
						<tr>
							<td colspan="3"><span><br>None available<br><br></span></td>
						</tr>
<?php endif; ?>
					</tbody>
				</table>
			</div>
<?php
if (is_array($warehouse_data))
foreach ($warehouse_data as $hostname => $host_data):
?>
			<h3 class="toggle">Shared from <?php echo htmlspecialchars($hostname); ?> <span class="count">(<?php echo count($host_data); ?>)</span></h3>
			<div class="data">
				<table class="sortable data" width="100%">
					<col width="40%">
					<col width="20%">
					<col width="20%">
					<col width="20%">
					<thead>
					<tr>
					<th scope="col" class="text"><div>Username</div></th>
					<th scope="col" class="text"><div>Genotype locator</div></th>
					<th scope="col" class="text"><div>Phenotype/profile locator</div></th>
					<th scope="col" class="no-sort"><div><?=$hostname == $myhostname ? "Results" : "Reprocess data here"?></div></th>
					</tr>
					</thead>
					<tbody>
<?php
foreach ($host_data as $username => $jobdata):
?>
					<tr class="unknown-frequency">
					<td scope="col"><?=htmlspecialchars($username)?></td>
					<td scope="col"><?=nice($jobdata,'genotype_locator')?></td>
					<td scope="col"><?=nice($jobdata,'profile_locator')?></td>
					<td scope="col"><?=$hostname == $myhostname ? results_link($username) : reprocess_button($jobdata, $username)?></td>
					</tr>
<?php endforeach; ?>
<?php if (!sizeof($host_data)): ?>
						<tr>
							<td colspan="3"><span><br>None available<br><br></span></td>
						</tr>
<?php endif; ?>
					</tbody>
				</table>
			</div>
<?php endforeach; ?>
			</div>
		</div>
	</div></div>
	<div id="foot"><div>
		<div id="copyright">
			<p>
				<span>Copyright &copy; MMIX President and Fellows of Harvard College<?php if(!isset($suppress_timing_data)): ?><br>[{elapsed_time} s]<?php endif; ?></span>
			</p>
		</div>
	</div></div>
</body>
</html>
