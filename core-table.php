<?php


class TableFormatter {
	public function generate($persons, $commitCounts, $maxMonths) {

		$stats = $this->generateStats($persons, $commitCounts);

		$now = strtotime("first day of this month");
		/*****************************
		 * Generate Table Overview
		 *****************************/
		ob_start();
		echo '<?xml version="1.0" encoding="UTF-8"?>
	<html><head><title>Visualizing TYPO3 Core activity - statistics in tabled overview</title>
	<style type="text/css">
	* { margin: 0; padding: 0; font: 100.1% Helvetica, Arial, sans-serif; font-size: 11px; }
	table { margin: 20px; }
	a { color:grey; text-decoration:none; font-size: 13px; }
	a:hover { text-decoration:underline; }
	#description { font-size:13px; padding:10px 30px; }
	#footer { font-size:12px; padding:10px 30px; }
	td { width:160px; border-top:1px solid grey; border-left: 1px solid white; padding: 1px 3px;white-space:nowrap; }
	.FlattrButton { top:5px; position:relative; }
	</style>
	<script type="text/javascript">
	/* <![CDATA[ */
	    (function() {
	        var s = document.createElement(\'script\'), t = document.getElementsByTagName(\'script\')[0];
	        s.type = \'text/javascript\';
	        s.async = true;
	        s.src = \'http://api.flattr.com/js/0.6/load.js?mode=auto\';
	        t.parentNode.insertBefore(s, t);
	    })();
	/* ]]> */</script>
	<link rel="alternate" type="application/rss+xml" title="the fancy part of the web RSS Feed" href="http://blog.tolleiv.de/feed/" />
	<link rel="pingback" href="http://blog.tolleiv.de/xmlrpc.php" />

	<link rel="alternate" type="application/rss+xml" title="the fancy part of the web &raquo; Visualizing TYPO3 Core activity Comments Feed" href="http://blog.tolleiv.de/2012/01/visualizing-typo3-core-activity/feed/" />
	</head><body>
	<div id="description">Scoring is described in the related blog post <a href="http://blog.tolleiv.de/2012/01/visualizing-ty…-core-activity/">on visualization of the TYPO3 core activity.</a> The same numbers can also be found in the <a href="http://blog.tolleiv.de/impact-chart/">impact chart visualization</a></div>';

		$cols = 6;
		for ($o = 0; $o < $maxMonths; $o += 6) {
			echo '<table cellspacing="0" cellpadding="0">';
			echo '<tr>';
			for ($i = $o; $i < $o + $cols; $i++) {
				if (!isset($stats[$i])) {
					break;
				}
				echo sprintf('<th>%s (<span title="no. of contributers">%d</span> / <span title="total score">%d</span>)</th>', $stats[$i]['month'], $stats[$i]['contributers'], $stats[$i]['total']);
			}
			echo '</tr>';

			for ($j = 0; $j < 20; $j++) {
				echo '<tr>';
				for ($i = $o; $i < $o + $cols; $i++) {
					$index = strftime('%Y.%m', strtotime($i ? '-' . $i . ' month' : 'now', $now));
					if (!isset($persons[$index])) {
						break;
					}
					$person = array_slice($persons[$index], $j, 1);
					if (!key($person)) {
						echo '<td style="border:none;"></td>';
					} else {
						echo sprintf('<td style="background-color:%s">%s (%s)</td>', $this->color(key($person)), key($person), current($person));
					}
				}
				echo '</tr>';
			}
			echo '</table>';
		}
		echo '
	    <script type="text/javascript">

	  var _gaq = _gaq || [];
	  _gaq.push([\'_setAccount\', \'UA-28458717-1\']);
	  _gaq.push([\'_trackPageview\']);

	  (function() {
	    var ga = document.createElement(\'script\'); ga.type = \'text/javascript\'; ga.async = true;
	    ga.src = (\'https:\' == document.location.protocol ? \'https://ssl\' : \'http://www\') + \'.google-analytics.com/ga.js\';
	    var s = document.getElementsByTagName(\'script\')[0]; s.parentNode.insertBefore(ga, s);
	  })();

	</script>
	    <div id="footer">
		    <a href="http://blog.tolleiv.de/imprint/">Imprint</a> - <a href="http://blog.tolleiv.de/feed/" class="icon rss" title="You should read this blog in your rss reader">RSS feed</a> - <a class="icon twitter" href="http://www.twitter.com/tolleiv" title="You should follow me on twitter">Follow me on Twitter</a> - <a class="icon facebook" href="http://www.facebook.com/people/@/528343772" title="We should stay in touch on facebook">Get in touch on Facebook</a> - <a class="FlattrButton" style="display:none;" rev="flattr;button:compact;" href="http://blog.tolleiv.de/impact-chart"></a>
	<noscript><a href="http://flattr.com/thing/468094/TYPO3-Core-impact-charts" target="_blank">
	<img src="http://api.flattr.com/button/flattr-badge-large.png" alt="Flattr this" title="Flattr this" border="0" align="middle" /></a></noscript>
		</div>
	    </body>
	</html>';

		return ob_get_clean();
	}


	protected function generateStats($persons, $commitCounts) {
		$stats = array();
		$i = 0;
		foreach ($persons as $month => $data) {
			$stats[$i++] = array(
				'month' => $month,
				'commits' => $commitCounts[$month],
				'contributers' => count(array_keys($data)),
				'total' => array_sum($data),
				'top20' => array_sum(array_slice($data, 0, 20))
			);
		}
		return $stats;
	}

	/**
	 * Create a nice color for the table listings
	 *
	 * @param $name
	 * @return string
	 */
	protected function color($name) {
		$color = substr(md5($name), 6, 6);
		return sprintf('rgba(%s, %s, %s, 0.5)', hexdec(substr($color, 0, 2)), hexdec(substr($color, 2, 2)), hexdec(substr($color, 4, 2)));
	}
}