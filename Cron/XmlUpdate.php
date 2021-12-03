<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\AdminUserGuide\Cron;

use Magefan\AdminUserGuide\Model\XmlReader;

/**
 * Class XmlReader
 */
class XmlUpdate
{
    /**
     * @var XmlReader
     */
    private $xmlReader;

    /**
     * @param XmlReader $xmlReader
     */
    public function __construct(
        XmlReader $xmlReader
    ) {
        $this->xmlReader = $xmlReader;
    }

    /**
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function execute()
    {
        $this->xmlReader->update();
    }
}
