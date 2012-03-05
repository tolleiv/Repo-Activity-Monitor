<?php

class JsonFormatter {
	/**
	 * @param $persons
	 * @param $maxMonths
	 * @param $dataDir
	 */
	function generate($persons, $commitCounts, $maxMonths) {
		$now = strtotime("first day of this month");
		$authorIds = array();
		$jsonAuthors = array();
		$i = 0;
		foreach ($persons as $_ => $data) {
			foreach ($data as $author => $commits) {
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
		$max = 0;
		$jsonBuckets = array();
		do {
			$time = strtotime($i ? '-' . $i . ' month' : 'now', $now);
			$bucket = array('d' => $time, 'i' => array());
			$index = strftime('%Y.%m', $time);
			$tmpSum = 0;
			if (!isset($persons[$index])) {
				continue;
			}
			$top20 = array_keys(array_slice($persons[$index], 0, 20));
			$contributors = $persons[$index];
			ksort($contributors);
			foreach ($contributors as $author => $commits) {
				$commitCount = in_array($author, $top20) ? $commits : 1;
				$bucket['i'][] = array($authorIds[$author], $commitCount);
				$tmpSum += $commits;
			}
			$max = $tmpSum > $max ? $tmpSum : $max;
			$jsonBuckets[] = $bucket;
		} while (++$i < $maxMonths);

		return json_encode(array('authors' => $jsonAuthors, 'buckets' => $jsonBuckets, 'max' => $max));
	}
}