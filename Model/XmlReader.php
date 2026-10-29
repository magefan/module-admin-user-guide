<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\AdminUserGuide\Model;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\App\Filesystem\DirectoryList;

class XmlReader
{

    public const DATA_FILE_NAME = 'magefan/aug-data/knowledge-base.xml';

    /**
     * Cache key of the flag that prevents remote requests after a failed one
     */
    public const RETRY_CACHE_KEY = 'magefan_aug_data_retry';

    /**
     * Delay before the next attempt after a failed request
     */
    public const RETRY_LIFETIME = 3600;

    /**
     * Max time in seconds to establish connection
     */
    public const CONNECT_TIMEOUT = 3;

    /**
     * Max time in seconds for the whole request
     */
    public const TIMEOUT = 5;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var CurlFactory
     */
    private $curlFactory;

    /**
     * @param CacheInterface $cache
     * @param Filesystem $filesystem
     * @param Json $json
     * @param CurlFactory $curlFactory
     */
    public function __construct(
        CacheInterface $cache,
        Filesystem $filesystem,
        Json $json,
        CurlFactory $curlFactory
    ) {
        $this->cache = $cache;
        $this->filesystem = $filesystem;
        $this->json = $json;
        $this->curlFactory = $curlFactory;
    }

    /**
     * Get unserialized data
     *
     * @return array|bool|float|int|mixed|string|null
     * @throws FileSystemException
     */
    public function get()
    {
        if (!$this->cache->load('magefan_aug_data')) {
            $this->readFile();
        }

        try {
            return $this->json->unserialize($this->cache->load('magefan_aug_data'));
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Read file
     *
     * @throws FileSystemException
     */
    private function readFile()
    {
        $directoryRead = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR);
        if (!$directoryRead->isExist(self::DATA_FILE_NAME) && !$this->cache->load(self::RETRY_CACHE_KEY)) {
            $this->update();
        }
        if (!$directoryRead->isExist(self::DATA_FILE_NAME)) {
            return;
        }
        $xml = $directoryRead->readFile(self::DATA_FILE_NAME);
        try {
            $new = simplexml_load_string($xml);
        } catch (\Exception $e) {
            $new = null;
        }

        $data = [];
        if (isset($new) && isset($new->channel) && isset($new->channel->item)) {
            foreach ($new->channel->item as $item) {
                $data[] = (array)$item;
            }
        }
        $this->cache->save($this->json->serialize($data), 'magefan_aug_data');
    }

    /**
     *  Update file content
     *
     * @throws FileSystemException
     */
    public function update()
    {
        $fileUrl = 'https://' . 'mage' . 'fan.com' .'/media/knowledge-base.xml';
        try {
            $curl = $this->curlFactory->create();
            $curl->setTimeout(self::TIMEOUT);
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, self::CONNECT_TIMEOUT);
            $curl->get($fileUrl);
            $contents = (200 == $curl->getStatus()) ? $curl->getBody() : null;
        } catch (\Exception $e) {
            $contents = null;
        }
        if ($contents && $this->isValidXml($contents)) {
            $media = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            $media->writeFile(self::DATA_FILE_NAME, $contents);
        } else {
            $this->cache->save('1', self::RETRY_CACHE_KEY, [], self::RETRY_LIFETIME);
        }
    }

    /**
     * Check if content is valid xml, to not overwrite existing file with error page
     *
     * @param string $contents
     * @return bool
     */
    private function isValidXml(string $contents): bool
    {
        $useErrors = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($contents);
        libxml_clear_errors();
        libxml_use_internal_errors($useErrors);

        return false !== $xml;
    }
}
