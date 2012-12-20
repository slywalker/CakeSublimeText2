<?php
App::uses('AppShell', 'Console/Command');

class CompletionsShell extends AppShell {

	const CLASS_REGEX = '/^class\s([^\s]+)/m';

	const CONST_REGEX = '/define\(\'([^\']+)\'/m';

	const PROPERTY_REGEX = '/public\s\$([^\s;]+)/m';

	const FUNCTION_REGEX = '/public\sfunction\s([^_\(]+)\((.*)\)/m';

	const STATIC_REGEX = '/public\sstatic\sfunction\s([^_\(]+)\((.*)\)/m';

	const IGNORE_REGEX = '/Test/';

	protected $_constCompletions = array();
	protected $_classCompletions = array();
	protected $_propertyCompletions = array();
	protected $_functionCompletions = array();
	protected $_staticCompletions = array();

	public function startup() {}

	public function main() {
		$this->pluginPath = $dist = dirname(dirname(dirname(__FILE__))) . DS;

		$pattern = CAKE_CORE_INCLUDE_PATH . DS . 'Cake' . DS . '*.php';
		$files = $this->_globRecursive($pattern);

		$completions = array();
		foreach ($files as $file) {
			$this->_findCompletion($file);
		}

		$this->_makeCompletionJson($completions);
	}

	protected function _makeCompletionJson($completions) {
		foreach (array('const', 'class') as $key) {
			$json = array(
				'scope' => 'source.php - variable.other.php',
				'completions' => array(),
			);

			$name = '_' . $key . 'Completions';
			$this->{$name} = array_unique($this->{$name});
			array_walk_recursive($this->{$name}, function(&$val, $index) {
				$val = str_replace('$', '\$', $val);
			});

			foreach ($this->{$name} as $completion) {
				$json['completions'][] = array(
					'trigger' => $completion . "\t[CakePHP]",
					'contents' => $completion,
				);
			}

			$file = $this->pluginPath . 'Cake' . ucwords($key) . 'Completions.sublime-completions';
			file_put_contents($file, json_encode($json));
			$this->out('dist: ' . $file);
		}

		array_walk_recursive($this->_propertyCompletions, function(&$val, $index) {
			$val = str_replace('$', '\$', $val);
		});
		$json = array(
			'scope' => 'source.php - variable.other.php',
			'completions' => array(),
		);
		foreach ($this->_propertyCompletions as $file => $completion) {
			foreach ($completion[0] as $key => $match) {
				$json['completions'][] = array(
					'trigger' => $completion[1][$key] . "\t[" . str_replace('.php', '', $file) . ']',
					'contents' => $completion[1][$key],
				);
			}
		}
		$file = $this->pluginPath . 'CakePropertyCompletions.sublime-completions';
		file_put_contents($file, json_encode($json));
		$this->out('dist: ' . $file);

		array_walk_recursive($this->_functionCompletions, function(&$val, $index) {
			$val = str_replace('$', '\$', $val);
		});
		$json = array(
			'scope' => 'source.php - variable.other.php',
			'completions' => array(),
		);
		foreach ($this->_functionCompletions as $file => $completion) {
			foreach ($completion[0] as $key => $match) {
				$json['completions'][] = array(
					'trigger' => $completion[1][$key] . "\t[" . str_replace('.php', '', $file) . ']',
					'contents' => $completion[1][$key] . '(' . $completion[2][$key] . ')',
				);
			}
		}
		$file = $this->pluginPath . 'CakeMethodCompletions.sublime-completions';
		file_put_contents($file, json_encode($json));
		$this->out('dist: ' . $file);

		array_walk_recursive($this->_staticCompletions, function(&$val, $index) {
			$val = str_replace('$', '\$', $val);
		});
		$json = array(
			'scope' => 'source.php - variable.other.php',
			'completions' => array(),
		);
		foreach ($this->_staticCompletions as $file => $completion) {
			foreach ($completion[0] as $key => $match) {
				$json['completions'][] = array(
					'trigger' => str_replace('.php', '', $file) . '::' . $completion[1][$key],
					'contents' => str_replace('.php', '', $file) . '::' . $completion[1][$key] . '(' . $completion[2][$key] . ')',
				);
			}
		}
		$file = $this->pluginPath . 'CakeStaticMethodCompletions.sublime-completions';
		file_put_contents($file, json_encode($json));
		$this->out('dist: ' . $file);
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
		$this->_classCompletions += $matches[1];

		preg_match_all(self::CONST_REGEX, $text, $matches);
		$this->_constCompletions += $matches[1];

		preg_match_all(self::PROPERTY_REGEX, $text, $matches);
		$this->_propertyCompletions[basename($file)] = $matches;

		preg_match_all(self::FUNCTION_REGEX, $text, $matches);
		$this->_functionCompletions[basename($file)] = $matches;

		preg_match_all(self::STATIC_REGEX, $text, $matches);
		$this->_staticCompletions[basename($file)] = $matches;

		return $completions;
	}
}