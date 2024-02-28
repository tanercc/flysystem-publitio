<?php

namespace Publitio\FlysystemPublitio;

use Illuminate\Support\Facades\Log;
use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Adapter\Polyfill\NotSupportingVisibilityTrait;
use League\Flysystem\Config;
use Publitio\BadJSONResponse;

class PublitioAdapter extends AbstractAdapter
{
    use NotSupportingVisibilityTrait;

    /**
     * @var \Publitio\API
     */
    protected $client;

    protected $config;

    public function __construct($client, $config)
    {
        $this->client = $client;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function write($path, $contents, Config $config)
    {
        return $this->upload($path, $contents);
    }

    /**
     * {@inheritdoc}
     */
    public function writeStream($path, $resource, Config $config)
    {
        return $this->upload($path, $resource);
    }

    /**
     * {@inheritdoc}
     */
    public function update($path, $contents, Config $config)
    {
        throw new Exception('Not implemented.');

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function updateStream($path, $resource, Config $config)
    {
        throw new Exception('Not implemented.');

        return false;
    }

    /**
     * @param string $path
     * @param resource|string $contents
     * @param string $mode
     *
     * @return array|false file metadata
     */
    protected function upload(string $path, $contents)
    {
        try {
            $response = $this->client->uploadFile($contents, 'file', [
                'public_id' => pathinfo($path)['filename'],
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return false;
        }

        if ($response->success) {
            return $this->normalizeResponse($response);
        } else {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rename($path, $newPath): bool
    {
        throw new Exception('Not implemented.');

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function copy($path, $newpath = ''): bool
    {
        try {
            $response = $this->client->uploadRemoteFile($path);
        } catch (BadJSONResponse $e) {
            Log::error($e->getMessage());

            return false;
        }

        return $response->url_preview;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($path): bool
    {
        // This API call permanently removes the file and all its versions!
        $filename = pathinfo($path)['filename'];
        try {
            $response = $this->client->call("/files/delete/$filename", 'DELETE');
        } catch (BadJSONResponse $e) {
            Log::error($e->getMessage());

            return false;
        }

        return $response->success;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDir($dirname): bool
    {
        // This call completely removes files and file versions together with the folder!
        try {
            $response = $this->client->call("/folders/delete/$dirname", 'DELETE');
        } catch (BadJSONResponse $e) {
            Log::error($e->getMessage());

            return false;
        }

        return $response->success;
    }

    /**
     * {@inheritdoc}
     */
    public function createDir($dirname, Config $config)
    {
        try {
            $response = $this->client->call('/folders/create', 'POST', [
                'parent_id' => '/',
                'name' => $dirname,
            ]);
        } catch (BadJSONResponse $e) {
            Log::error($e->getMessage());

            return false;
        }

        return $response->success;
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl($path): string
    {
        // logger($path);
        // File size same with hero image
        return $this->config['domain'].'/file/c_fill,w_700,q_100/'.pathinfo($path)['basename'];
        /*
        $id = 'publitioId';
        try {
            $response = $this->client->call("/files/show/$id", 'GET');
        } catch (BadJSONResponse $e) {
            Log::error($e->getMessage());

            return false;
        }

        return $response->url_preview;
        */
    }

    /**
     * {@inheritdoc}
     */
    public function has($path)
    {
        $filename = pathinfo($path)['filename'];
        try {
            $response = $this->client->call("/files/show/$filename", 'GET');
        } catch (BadJSONResponse $e) {
            Log::error($e->getMessage());

            return false;
        }

        return $response->success;
    }

    /**
     * {@inheritdoc}
     */
    public function read($path)
    {
        $filename = pathinfo($path)['filename'];
        try {
            $response = $this->client->call("/files/show/$filename", 'GET');
        } catch (BadJSONResponse $e) {
            Log::error($e->getMessage());

            return false;
        }

        return $response->url_preview;
    }

    /**
     * {@inheritdoc}
     */
    public function readStream($path)
    {
        $filename = pathinfo($path)['filename'];
        try {
            $response = $this->client->call("/files/show/$filename", 'GET');
        } catch (BadJSONResponse $e) {
            Log::error($e->getMessage());

            return false;
        }

        $stream = fopen($response->url_download, 'r');

        return compact('stream');
    }

    /**
     * {@inheritdoc}
     */
    public function listContents($directory = '', $recursive = false): array
    {
        $url = $directory ? "/files/list?folder=$directory" : '/files/list';

        try {
            $response = $this->client->call($url, 'GET');
        } catch (BadJSONResponse $e) {
            return [];
        }

        $entries = [];

        foreach ($response->files as $file) {
            $entries[] = $file->url_preview;
        }

        return $entries;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($path)
    {
        $filename = pathinfo($path)['filename'];
        try {
            $response = $this->client->call("/files/show/$filename", 'GET');
        } catch (BadJSONResponse $e) {
            return false;
        }

        return $this->normalizeResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function getSize($path)
    {
        $filename = pathinfo($path)['filename'];
        try {
            $response = $this->client->call("/files/show/$filename", 'GET');
        } catch (BadJSONResponse $e) {
            return false;
        }

        return "$response->size";
    }

    /**
     * {@inheritdoc}
     */
    public function getMimetype($path)
    {
        $filename = pathinfo($path)['filename'];
        try {
            $response = $this->client->call("/files/show/$filename", 'GET');
        } catch (BadJSONResponse $e) {
            return false;
        }

        return "$response->type/$response->extension";
    }

    /**
     * {@inheritdoc}
     */
    public function getTimestamp($path)
    {
        $filename = pathinfo($path)['filename'];
        try {
            $response = $this->client->call("/files/show/$filename", 'GET');
        } catch (BadJSONResponse $e) {
            return false;
        }

        return $response->created_at;
    }

    public function getClient()
    {
        return $this->client;
    }

    protected function normalizeResponse($response): array
    {
        $normalizedResponse = ['path' => $response->url_preview];
        $normalizedResponse['timestamp'] = strtotime($response->created_at);
        $normalizedResponse['size'] = $response->size;
        $normalizedResponse['bytes'] = $response->size;
        $normalizedResponse['type'] = 'file';

        return $normalizedResponse;
    }
}
