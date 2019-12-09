<?php

/*
 * This file is part of itk-dev/datatidy-data.
 *
 * (c) 2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Data;

use DateTime;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DataService
{
    private $dataPath = __DIR__.'/../../data';

    /** @var HttpClientInterface */
    private $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function getIndex($create = false)
    {
        $filename = $this->getIndexPath();
        if (!is_file($filename)) {
            if ($create) {
                return [];
            }

            throw new RuntimeException('Cannot load index');
        }

        return $this->load($filename, true);
    }

    private function setIndex(array $index)
    {
        $filename = $this->getIndexPath();

        file_put_contents($filename, json_encode($index));
    }

    private function getIndexPath()
    {
        return $this->dataPath.'/index.json';
    }

    public function get($path)
    {
        $filename = $this->dataPath.'/'.$path;

        if (!file_exists($filename)) {
            throw new \InvalidArgumentException(sprintf('No such path: %s', $path));
        }

        $ext = pathinfo($path, PATHINFO_EXTENSION);
        $contentType = [
            'json' => 'application/json',
            'xml' => 'application/xml',
            'csv' => 'text/csv',
        ][$ext];

        return [$filename, $contentType];
    }

    public function add($url)
    {
        $response = $this->httpClient->request('GET', $url);
        $content = $response->getContent();
        $format = $this->getFormat($content);

        $filename = preg_replace('/[^\w\-\.]/', '/', preg_replace('@^\w+:\/\/@', '', $url));
        $filename .= '.'.$format;

        $path = $this->dataPath.'/'.$filename;
        $dir = \dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, $content);

        $index = $this->getIndex(true);

        $index[$url] = [
            'filename' => $filename,
            'updated_at' => (new DateTime())->format(DateTime::ATOM),
        ];

        $this->setIndex($index);

        return $filename;
    }

    private function load($filename, $asJson = false)
    {
        $content = file_get_contents($filename);

        return $asJson ? json_decode($content, true) : $content;
    }

    private function getFormat(string $content)
    {
        if (0 === strpos($content, '[') || 0 === strpos($content, '{')) {
            return 'json';
        } elseif (0 === strpos($content, '<')) {
            return 'xml';
        }

        return 'csv';
    }
}
