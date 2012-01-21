<?php

error_reporting(E_ALL ^ E_NOTICE);


function getMatchingLines($regEx, $input) {
	$lines = array();
	foreach($input as $line) {
		if(preg_match($regEx, $line)) {
			$lines[] = $line;
		}
	}
	return $lines;
};
    // used to clean up names to have a unique appearance
function mapName($name) {
	$names = array('cybercraft' => 'Jo Hasenau', 'etobi.de' => 'Tobias Liebig', 'xperseguers' => 'Xavier Perseguers', 'ohader' => 'Oliver Hader', 'francois' => 'Francois Suter','steffenk' => 'Steffen Kamper', 'psychomieze' => 'Susanne Moog', 'tolleiv' => 'Tolleiv Nietsch', 'jigal' => 'Jigal van Hemert', 'sgalinsk' => 'Stefan Galinski', 'lolli' => 'Christian Kuhn', 'sonne' => 'Sonja Scholz', 'baschny' => 'Ernesto Baschny', 'stan' => 'Stanislas Rolland', 'mbresch' => 'Marco Bresch', 'Tolleiv' => 'Tolleiv Nietsch', 'stephenking' => 'Steffen Gebert', 'Steffen Gebert and the T3UXW09 team' => 'Steffen Gebert', 'Benni Mack' => 'Benjamin Mack', 'nxpthx' => "Steffen Ritter");
	$leName = isset($names[$name]) ? $names[$name] : $name;
	return preg_replace('/,.*/', '', $leName);
};

function getName($regEx, $input) {
	$match = array();
	preg_match($regEx, $input, $match);
	return isset($match[1]) ? mapName(trim($match[1])) : FALSE;
};

function getAuthor($input) {
	$lines = getMatchingLines('/\(Thanks to.+\)/',$input);
	if (!empty($lines)) {
	    $name = getName('/Thanks to ([^<\)]*)/', $lines[0]);
	    $parts = explode(' and ', str_replace(array('/', '&'),array('and', 'and'), $name));
	    $name = $parts[0]; // Sorry have to take one
	} else { 
	    $lines = getMatchingLines('/^Author:.*$/', $input); 	
	    $name = getName('/Author: ([^<]*)/', $lines[0]); 
	}
	return $name;
};

function processPersons($regex, $input, $callback) {
	$lines = getMatchingLines($regex , $input);
	foreach($lines as $line) {
		$callback(getName($regex, $line));
	}
};

function buildScoreForCommit($input, &$persons) {
	$persons[getAuthor($input)] += 10;
	processPersons('/Tested-by: ([^<]*)/', $input, function($name) use(&$persons) { $persons[$name] += 3; });
	processPersons('/Reviewed-by: ([^<]*)/', $input, function($name) use(&$persons) { $persons[$name] += 1; });
};

function buildStatForCommits(&$persons, $commits) {
	$count = 0;
	$cmd = 'git log -n 1 --pretty ';
	foreach($commits as $commit) {
		$hash = substr($commit, 0, strpos($commit, ' '));
		$result=array();
		exec ($cmd . $hash, $result);
		buildScoreForCommit($result, $persons);
		$count++; 
	}
	return $count;
};

function color($name) {
	$color = substr(md5($name), 6, 6);
	return sprintf('rgba(%s, %s, %s, 0.5)', hexdec(substr($color, 0, 2)), hexdec(substr($color, 2, 2)), hexdec(substr($color, 4, 2)));	
};

function getCommitsForMonth($offset) {
    $commits = array();
    $time = strtotime($offset ? '-'.$offset.' months' : 'now');
    $month = strftime('%B %Y', $time);
    $begin = strtotime('first day of ' . $month);
    $end = strtotime('last day of ' . $month);
    $cmd = 'git log --since="' . strftime('%Y-%m-%d', $begin) . '" ' . ($end > time() ? '' : '--until="' . strftime('%Y-%m-%d', $end) . '"' ) . ' --oneline'; 
    exec($cmd, $commits);
    return $commits;
}

function findData($dir) {
    if (is_file($dir . 'stat.inc')) {
	include($dir . 'stat.inc');
    }
    return isset($data) && !empty($data) ? $data : array();
}

function storeData($dir, $data) {
    file_put_contents($dir . 'stat.inc',
	sprintf('<?php $data = unserialize(\'%s\');', str_replace("'", "\\'", serialize($data)))
    );
}


$diff = date_diff(new DateTime('2006-03-01'), new DateTime());
$maxMonths = $diff->format('%y')*12 + $diff->format('%m');
$baseDir = isset($argv[1]) ? $argv[1] : getcwd(); $baseDir .= substr($baseDir,-1) == '/' ? '' : '/';
$dataDir = isset($argv[2]) ? $argv[2] : getcwd(); $dataDir .= substr($dataDir,-1) == '/' ? '' : '/';
$modules = array('.', 'typo3/sysext/workspaces', 'typo3/sysext/extbase', 'typo3/sysext/fluid', 'typo3/sysext/dbal', 'typo3/sysext/version', 'typo3/sysext/linkvalidator');
$i=0;
list($persons, $commitCounts) = findData($dataDir);
$stats = array();
do {
	$index = strftime('%Y.%m', strtotime($i ? '-' . $i . ' month' : 'now'));
	if (isset($persons[$index])) { // assume that all "older" data was in the store
		break;
	}
	$persons[$index] = array();
	$count = 0;
	foreach($modules as $module) {
		chdir($baseDir . $module);
		$commits = getCommitsForMonth($i);
		$count += buildStatForCommits($persons[$index], $commits);
		unset($persons[$index]['']);
		unset($persons[$index]['WWW']);
	}
	asort($persons[$index]);
	$persons[$index] = array_reverse($persons[$index]);
	$commitCounts[$index] = $count;
	chdir($baseDir);
} while(++$i < $maxMonths);
krsort($persons);
storeData($dataDir, array(array_slice($persons, 1), array_slice($commitCounts, 1)));


$chartLabels = array();
$i=0;
foreach($persons as $month => $data) {
	$chartCommits[] = array($i, $commitCounts[$month]);
	$chartContributors[] = array($i, count(array_keys($data)));
	$chartTotal[] = array($i, array_sum($data)/10);
	$stats[$i++] = array(
		'month' => $month,
		'commits' => $commitCounts[$month],
		'contributers' => count(array_keys($data)),
		'total' => array_sum($data),
		'top20' => array_sum(array_slice($data, 0, 20))	
	);
}

$authorIds = array(); $jsonAuthors=array(); $i=0;
foreach($persons as $month => $data) {
	foreach($data as $author => $commits) {
		if (!array_key_exists($author, $authorIds)) {
			$authorIds[$author] = ++$i;
		} 
		$id = $authorIds[$author];
		$jsonAuthors[$id] = array(
			'n' => $author,
			'c' => (isset($jsonAuthors[$id]['c']) ? $jsonAuthors[$id]['c'] : 0) + $commits,
		);
	}
}
$i = 0;
$max = 0; $jsonBuckets = array();
do {
	$time = strtotime($i ? '-' . $i . ' month' : 'now');
	$bucket = array('d' => $time, 'i' => array());
	$index = strftime('%Y.%m', $time);
	$tmpSum=0;
	$top20 = array_keys(array_slice($persons[$index], 0, 20));
	$contributors = $persons[$index];
	ksort($contributors);
	foreach($contributors as $author => $commits) {
		$commitCount = in_array($author, $top20) ? $commits : 1;
		$bucket['i'][] = array($authorIds[$author], $commitCount);
		$tmpSum += $commits;
	}
	$max = $tmpSum > $max ? $tmpSum : $max;
	$jsonBuckets[] = $bucket;
} while(++$i < $maxMonths);

file_put_contents($dataDir . 'json.php', json_encode(array('authors' => $jsonAuthors, 'buckets' => $jsonBuckets, 'max' => $max)));

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

$cols=6;
for($o=0;$o<$maxMonths;$o+=6) {
	echo '<table cellspacing="0" cellpadding="0">';
	echo '<tr>';
	for($i=$o;$i<$o+$cols;$i++) {
		if (!isset($stats[$i])) {
			break;
		}
		echo sprintf('<th>%s (<span title="no. of contributers">%d</span> / <span title="total score">%d</span>)</th>', $stats[$i]['month'], $stats[$i]['contributers'], $stats[$i]['total']);
	}
	echo '</tr>';

	for($j=0;$j<20;$j++) {
	echo '<tr>';
		for($i=$o;$i<$o+$cols;$i++) {
			$index = strftime('%Y.%m', strtotime($i ? '-' . $i . ' month' : 'now'));
			if (!isset($persons[$index])) {
				break;
			}
			$person = array_slice($persons[$index], $j, 1);
			if (!key($person)) {
				echo '<td style="border:none;"></td>';
			} else {
				echo sprintf('<td style="background-color:%s">%s (%s)</td>', color(key($person)), key($person), current($person));
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

file_put_contents($dataDir .'stat.html', ob_get_clean());
