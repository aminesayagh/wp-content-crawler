<?php
/**
 * Created by PhpStorm.
 * User: turgutsaricam
 * Date: 10/12/2018
 * Time: 18:37
 *
 * @since 1.8.0
 */

namespace WPCCrawler\Objects\OptionsBox\Boxes\File;


use WPCCrawler\Objects\OptionsBox\Boxes\Base\BaseOptionsBoxData;
use WPCCrawler\Objects\OptionsBox\Boxes\File\Options\FileOptionsBoxOperationOptions;
use WPCCrawler\Objects\OptionsBox\Boxes\File\Options\FileOptionsBoxTemplateOptions;
use WPCCrawler\Utils;

class FileOptionsBoxData extends BaseOptionsBoxData {

    /** @var array */
    private $findReplace = null;

    /** @var null|FileOptionsBoxOperationOptions */
    private $fileOperations = null;

    /** @var null|FileOptionsBoxTemplateOptions */
    private $templates = null;

    /**
     * @return array
     */
    public function getFindReplaceOptions() {
        if ($this->findReplace === null) {
            $this->findReplace = Utils::array_get($this->getData(), 'fileFindReplace.file_find_replace', []);
        }

        return $this->findReplace;
    }

    /**
     * @return null|FileOptionsBoxTemplateOptions
     * @since 1.8.0
     */
    public function getTemplateOptions() {
        if ($this->templates === null) {
            $templates = Utils::array_get($this->getData(), 'fileTemplates', []);
            $this->templates = !$templates ? null : new FileOptionsBoxTemplateOptions($templates);
        }

        return $this->templates;
    }

    /**
     * @return null|FileOptionsBoxOperationOptions
     * @since 1.8.0
     */
    public function getFileOperationOptions() {
        if ($this->fileOperations === null) {
            $fileOperations = Utils::array_get($this->getData(), 'fileOperations', []);
            $this->fileOperations = !$fileOperations ? null : new FileOptionsBoxOperationOptions($fileOperations);
        }

        return $this->fileOperations;
    }

}