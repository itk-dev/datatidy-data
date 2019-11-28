<?php

class Data {
	private $indexPath = __DIR__.'/data/index.json';

	public function __construct() {
		if (php_sapi_name() === 'cli') {
			global $argv;

			$urls = array_slice($argv, 1);

			if (empty($urls)) {
				throw new RuntimeException('No urls specified');
			}

			foreach ($urls as $url) {
				$this->addDataSource($url);
			}
		} else {
			$index = $this->getIndex();

			$baseUrl =
				('on' === ($_SERVER['HTTPS'] ?? '') ? 'https' : 'http')
				.'://'
				.$_SERVER['HTTP_HOST']
				.$_SERVER['REQUEST_URI'];

			$index = array_map(function ($item) use ($baseUrl) {
				$item['url'] = $baseUrl.$item['filename'];
				unset($item['filename']);
				return $item;
			}, $index);

			header('content-type: application/json');
			echo json_encode($index);
		}
	}

	private function addDataSource(string $url) {
		$content = $this->getContent($url);

		if (!$content) {
			throw new RuntimeException(sprintf('Cannot get content from %s', $url));
		}

		$format = $this->getFormat($content);

		$filename = 'data/'.preg_replace('/[^\w\-\.]/', '/', preg_replace('@^\w+:\/\/@', '', $url));
		$filename .= '.'.$format;

		$path = __DIR__.'/'.$filename;
		$dir = dirname($path);
		if (!is_dir($dir)) {
			mkdir($dir, 0755, true);
		}

		file_put_contents($path, $content);

		echo sprintf('Content written to %s', $path), PHP_EOL;

		$index = $this->getIndex(true);

		$index[$url] = [
			'filename' => $filename,
			'updated_at' => (new DateTime())->format(DateTime::ATOM),
		];

		$this->setIndex($index);

		echo sprintf('Index updated'), PHP_EOL;
		echo json_encode($this->getIndex(), JSON_PRETTY_PRINT);
	}

	private function getContent(string $url) {
		$content = @file_get_contents($url);

		if (empty($content)) {
			throw new RuntimeException(sprintf('Cannot load content from %s', $url));
		}

		return $content;
	}

	private function getFormat(string $content) {
		if (0 === strpos($content, '[') || 0 === strpos($content, '{')) {
			return 'json';
		}
		elseif (0 === strpos($content, '<')) {
			return 'xml';
		}

		return 'csv';
	}

	private function getIndex(bool $create = false) {
		if (!is_file($this->indexPath)) {
			if ($create) {
				return [];
			}

			throw new RuntimeException('Cannot load index');
		}

		return json_decode(file_get_contents($this->indexPath), true);
	}

	private function setIndex(array $index) {
		file_put_contents($this->indexPath, json_encode($index));
	}

}

new Data();
