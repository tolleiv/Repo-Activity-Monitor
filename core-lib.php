<?php

class StatGenerator {

	protected $dataDir;
	protected $baseDir;
	protected $maxMonths;

	protected $persons;
	protected $commitCounts;

	public function __construct($dataDir, $baseDir, $since) {
		$this->dataDir = $dataDir;
		$this->baseDir = $baseDir;
		$this->maxMonths = $this->getMonthDiff($since);
	}

	protected function getMonthDiff($since) {
		$diff = date_diff(new DateTime($since), new DateTime());
		return $diff->format('%y') * 12 + $diff->format('%m');
	}

	public function getMaxMonths() {
		return $this->maxMonths;
	}

	/**
	 * Get the numbers together
	 *
	 * @param $modules
	 * @return array
	 */
	public function generateData($modules) {
		$i = 0;
		list($persons, $commitCounts) = $this->findData();
		$now = strtotime("first day of this month");
		do {
			$index = strftime('%Y.%m', strtotime('-' . $i . ' months', $now));
			if (isset($persons[$index])) { // assume that all "older" data was in the store
				break;
			}

			$persons[$index] = array();
			$count = 0;
			foreach ($modules as $module) {
				chdir($this->baseDir . $module);
				$commits = $this->getCommitsForMonth($i);
				$count += $this->buildStatForCommits($persons[$index], $commits);
				unset($persons[$index]['']);
				unset($persons[$index]['WWW']);
			}
			asort($persons[$index]);
			$persons[$index] = array_reverse($persons[$index]);
			$commitCounts[$index] = $count;
			chdir($this->baseDir);
		} while (++$i < $this->maxMonths);
		krsort($persons);
		// slice to make sure that the current month is not cached
		$this->storeData(array(array_slice($persons, 1), array_slice($commitCounts, 1)));

		return array($persons, $commitCounts);
	}

	/**
	 * Get all commits (one per line) for the given month
	 *
	 * @param $offset
	 * @return array|null
	 */
	protected function getCommitsForMonth($offset) {
		$commits = array();
		$now = strtotime("first day of this month");
		$time = strtotime($offset ? '-' . $offset . ' months' : 'now', $now);
		$month = strftime('%B %Y', $time);
		$begin = strtotime('first day of ' . $month);
		$end = strtotime('last day of ' . $month);
		$cmd = 'git log --since="' . strftime('%Y-%m-%d', $begin) . '" ' . ($end > time() ? '' : '--until="' . strftime('%Y-%m-%d', $end) . '"') . ' --oneline' . "\n";
		@exec($cmd, $commits);
		return $commits;
	}

	/**
	 * Get the log for a single commit and get the related scores in place
	 *
	 * @param $persons
	 * @param $commits
	 * @return int
	 */
	protected function buildStatForCommits(&$persons, $commits) {
		$count = 0;
		$cmd = 'git log -n 1 --pretty ';
		foreach ($commits as $commit) {
			$hash = substr($commit, 0, strpos($commit, ' '));
			$result = array();
			@exec($cmd . $hash, $result);
			$this->buildScoreForCommit($result, $persons);
			$count++;
		}
		return $count;
	}

	/**
	 * Building the score for a single commit message
	 *
	 * @param $input
	 * @param $persons
	 */
	protected function buildScoreForCommit($input, &$persons) {
		$persons[$this->getAuthor($input)] += 10;
		$this->processPersons('/Tested-by: ([^<]*)/', $input, function($name) use(&$persons) {
			$persons[$name] += 3;
		});
		$this->processPersons('/Reviewed-by: ([^<]*)/', $input, function($name) use(&$persons) {
			$persons[$name] += 1;
		});
	}


	/**
	 * Extract the name (with the given regex) from the input
	 *
	 * @param $regEx
	 * @param $input
	 * @return bool|mixed
	 */
	protected function getName($regEx, $input) {
		$match = array();
		preg_match($regEx, $input, $match);
		return isset($match[1]) ? $this->mapName(trim($match[1])) : FALSE;
	}

	/**
	 * used to clean up names to have a unique appearance
	 *
	 * @param $name
	 * @return mixed
	 */
	protected function mapName($name) {
		$names = array('cybercraft' => 'Jo Hasenau', 'etobi.de' => 'Tobias Liebig', 'xperseguers' => 'Xavier Perseguers', 'ohader' => 'Oliver Hader', 'francois' => 'Francois Suter', 'steffenk' => 'Steffen Kamper', 'psychomieze' => 'Susanne Moog', 'tolleiv' => 'Tolleiv Nietsch', 'jigal' => 'Jigal van Hemert', 'sgalinsk' => 'Stefan Galinski', 'lolli' => 'Christian Kuhn', 'sonne' => 'Sonja Scholz', 'baschny' => 'Ernesto Baschny', 'stan' => 'Stanislas Rolland', 'mbresch' => 'Marco Bresch', 'Tolleiv' => 'Tolleiv Nietsch', 'stephenking' => 'Steffen Gebert', 'Steffen Gebert and the T3UXW09 team' => 'Steffen Gebert', 'Benni Mack' => 'Benjamin Mack', 'nxpthx' => "Steffen Ritter", "Björn Pedersen" => "Bjoern Pedersen");
		$leName = isset($names[$name]) ? $names[$name] : $name;
		return preg_replace('/,.*/', '', $leName);
	}

	/**
	 * Get any lines from the input block if they match the regex
	 * @param $regEx
	 * @param $input
	 * @return array
	 */
	protected function getMatchingLines($regEx, $input) {
		$lines = array();
		foreach ($input as $line) {
			if (preg_match($regEx, $line)) {
				$lines[] = $line;
			}
		}
		return $lines;
	}

	/**
	 * Use the author or "thanks to" annotation
	 *
	 * @param $input
	 * @return bool|mixed
	 */
	protected function getAuthor($input) {
		$lines = $this->getMatchingLines('/\(Thanks to.+\)/', $input);
		if (!empty($lines)) {
			$name = $this->getName('/Thanks to ([^<\)]*)/', $lines[0]);
			$parts = explode(' and ', str_replace(array('/', '&'), array('and', 'and'), $name));
			$name = $parts[0]; // Sorry have to take one
		} else {
			$lines = $this->getMatchingLines('/^Author:.*$/', $input);
			$name = $this->getName('/Author: ([^<]*)/', $lines[0]);
		}
		return $name;
	}

	/**
	 * Find all people matching a specific contribution $regex
	 *
	 * @param $regex
	 * @param $input
	 * @param $callback
	 */
	protected function processPersons($regex, $input, $callback) {
		$lines = $this->getMatchingLines($regex, $input);
		foreach ($lines as $line) {
			$callback($this->getName($regex, $line));
		}
	}

	/**
	 * Check if we've cached data
	 *
	 * @param $dir
	 * @return array|mixed
	 */
	protected function findData() {
		if (is_file($this->dataDir . 'stat.inc')) {
			include($this->dataDir . 'stat.inc');
		}
		return isset($data) && !empty($data) ? $data : array();
	}

	/**
	 * Cache the data
	 *
	 * @param $dir
	 * @param $data
	 */
	protected function storeData($data) {
		file_put_contents($this->dataDir . 'stat.inc',
			sprintf('<?php $data = unserialize(\'%s\');', str_replace("'", "\\'", serialize($data)))
		);
	}
}
