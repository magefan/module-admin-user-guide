<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\AdminUserGuide\Model;

use Magento\Framework\Filesystem;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Class XmlReader
 */
class XmlReader
{

    const DATA_FILE_NAME = 'magefan/aug-data/knowledge-base.xml';

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
     * @var Curl
     */
    private $curl;

    /**
     * @param CacheInterface $cache
     * @param Filesystem $filesystem
     * @param Json $json
     * @param Curl $curl
     */
    public function __construct(
        CacheInterface $cache,
        Filesystem $filesystem,
        Json $json,
        Curl $curl
    ) {
        $this->cache = $cache;
        $this->filesystem = $filesystem;
        $this->json = $json;
        $this->curl = $curl;
    }

    /**
     * @return array|bool|float|int|mixed|string|null
     * @throws \Magento\Framework\Exception\FileSystemException
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
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function readFile()
    {
        $directoryRead = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR);
        if (!$directoryRead->isExist(self::DATA_FILE_NAME)) {
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
        $this->cache->save($this->json->serialize($data),'magefan_aug_data');
    }

    /**
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function update()
    {
        $fileUrl = 'https://' . 'mage' . 'fan.com' .'/media/knowledge-base.xml';
        try {
            $this->curl->get($fileUrl);
            $contents = $this->curl->getBody();
        } catch (\Exception $e) {
            $contents = null;
        }
        if ($contents) {
            $media = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            $media->writeFile(self::DATA_FILE_NAME, $contents);
        }
    }
}
