<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\AdminUserGuide\Block\Adminhtml;

use Magento\Framework\View\Element\Template;
use Magefan\AdminUserGuide\Model\XmlReader;
use Magefan\AdminUserGuide\Model\Config;
use Magento\Framework\AuthorizationInterface;

class Help extends \Magento\Framework\View\Element\Template
{
    /**
     * @var XmlReader
     */
    private $xmlReader;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var AuthorizationInterface
     */
    private $authorization;

    /**
     * Help constructor.
     * @param Template\Context $context
     * @param XmlReader $xmlReader
     * @param Config $config
     * @param AuthorizationInterface $authorization
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        XmlReader $xmlReader,
        Config $config,
        AuthorizationInterface $authorization,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->xmlReader = $xmlReader;
        $this->config = $config;
        $this->authorization = $authorization;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getPageHelp()
    {
        $data = [];
        if (!$this->authorization->isAllowed('Magefan_AdminUserGuide::help')) {
            return $data;
        }

        if ($this->config->isEnabled()) {
            $guideData = $this->xmlReader->get();

            $fullActionName = str_replace('_', '-', $this->getRequest()->getFullActionName());
            $secondActionName = $fullActionName;

            if ('adminhtml-system-config-edit' == $secondActionName) {
                $secondActionName .= '-section-' . $this->getRequest()->getParam('section');
            }

            $data = [];
            if ($guideData) {
                foreach ($guideData as $item) {
                    if (empty($item['class']) || empty($item['title'])) {
                        continue;
                    }

                    $classes = explode(' ', $item['class']);
                    if (in_array($fullActionName, $classes)
                        || in_array($secondActionName, $classes)
                    ) {
                        $links = [];
                        for ($i = 0; $i <= count($item) - 2; $i++) {
                            if ($i == 0) {
                                if (isset($item['link'])) {
                                    $links[] = $item['link'];
                                }
                                $i++;
                            } else {
                                if (isset($item['link' . $i])) {
                                    $links[] = $item['link' . $i];
                                }
                            }
                        }

                        if (!count($links)) {
                            continue;
                        }
                        $data[] = [
                            'title' => $item['title'],
                            'links' => $links
                        ];
                    }
                }
            }

        }
        return $data;
    }
}
