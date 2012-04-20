<?php
App::uses('AppShell', 'Console/Command');

class CompletionsShell extends AppShell {

	const CLASS_REGEX = '/^class\s([^\s]+)/m';

	const CONST_REGEX = '/define\(\'([^\']+)\'/m';

	const FUNCTION_REGEX = '/public\sfunction\s([^_\(]+)\(/m';

	const IGNORE_REGEX = '/Test/';

	public function startup() {}

	public function main() {
		$pattern = CAKE_CORE_INCLUDE_PATH . DS . 'Cake' . DS . '*.php';
		$files = $this->_globRecursive($pattern);

		$completions = array();
		foreach ($files as $file) {
			$_completions = $this->_findCompletion($file);
			$this->out($file . ' ' . count($_completions) . ' keywords');
			$completions = array_merge($completions, $_completions);
		}
		$completions = array_unique($completions);

		$json = $this->_makeCompletionJson($completions);
		$dist = dirname(dirname(dirname(__FILE__))) . DS . 'CakePHP' .DS . 'CakePHP.sublime-completions';
		file_put_contents($dist, $json);
		$this->out('dist: ' . $dist);
	}

	protected function _makeCompletionJson($completions) {
		$json = array(
			'scope' => 'source.php - variable.other.php',
			'completions' => array('php'),
		);
		foreach ($completions as $completion) {
			$json['completions'][] = array('trigger' => $completion, 'contents' => $completion);
		}
		return json_encode($json);
	}

	protected function _globRecursive($pattern, $flags = 0) {
		$files = glob($pattern, $flags);
		foreach (glob(dirname($pattern) . DS . '*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
			if (!preg_match(self::IGNORE_REGEX, $dir)) {
				$files = array_merge($files, $this->_globRecursive($dir . DS . basename($pattern), $flags));
			}
		}
		return $files;
	}

	protected function _findCompletion($file) {
		$completions = array();
		$text = file_get_contents($file);

		preg_match_all(self::CLASS_REGEX, $text, $matches);
		$completions = array_merge($completions, $matches[1]);

		preg_match_all(self::CONST_REGEX, $text, $matches);
		$completions = array_merge($completions, $matches[1]);

		preg_match_all(self::FUNCTION_REGEX, $text, $matches);
		$completions = array_merge($completions, $matches[1]);

		return $completions;
	}
}