<?php
/**
 * Created by PhpStorm.
 * User: turgutsaricam
 * Date: 23/12/2018
 * Time: 10:12
 *
 * @since 1.8.0
 */

namespace WPCCrawler\Objects\File;


use Exception;
use WP_Error;
use WPCCrawler\Environment;
use WPCCrawler\Factory;
use WPCCrawler\Objects\Enums\InformationMessage;
use WPCCrawler\Objects\Enums\InformationType;
use WPCCrawler\Objects\Informing\Information;
use WPCCrawler\Objects\Informing\Informer;
use WPCCrawler\WPCCrawler;

class MediaService {

    /** @var MediaService */
    private static $instance = null;

    /** @var string[] Stores the paths of files that are created when conducting a test. */
    private $testFilePaths = [];

    /**
     * @var string Base name of the file that will store the test file paths. We store it in a PHP file because we do
     *             not want the full paths to be accessible publicly for security reasons.
     */
    private $fileBaseName = 'temp-media-file-paths.php';

    /**
     * Get the instance
     *
     * @return MediaService
     * @since 1.8.0
     */
    public static function getInstance() {
        if (static::$instance === null) static::$instance = new MediaService();
        return static::$instance;
    }

    /** This is a singleton */
    private function __construct() { }

    /**
     * Inserts the file as media for a given post
     *
     * @param int       $postId    The post ID to which the media will be attached
     * @param MediaFile $mediaFile Media file to be inserted. The media file must have a local path.
     * @return int ID of the inserted media (attachment)
     * @throws Exception If the media file does not have a local path ({@link MediaFile::getLocalPath()})
     * @since 1.8.0 Uses a MediaFile instance as a parameter instead of $filePath, $title, and $alt parameters.
     */
    public function insertMedia($postId, MediaFile $mediaFile) {
        // Built on the example at: https://codex.wordpress.org/Function_Reference/wp_insert_attachment

        if (!$mediaFile->getLocalPath()) throw new Exception('Media file must have a valid local path');

        $filePath = $mediaFile->getLocalPath();

        // Check the type of file. We'll use this as the 'post_mime_type'.
        $fileType = wp_check_filetype(basename($filePath), null);

        // Prepare an array of post data for the attachment.
        $attachment = [
            'guid'              => $mediaFile->getLocalUrl(),
            'post_mime_type'    => $fileType['type'],
            'post_title'        => $mediaFile->getMediaTitle(),
            'post_content'      => $mediaFile->getMediaDescription(),
            'post_excerpt'      => $mediaFile->getMediaCaption(),
            'post_status'       => 'inherit'
        ];

        // Insert the attachment.
        $attachmentId = wp_insert_attachment($attachment, $filePath, $postId);

        // Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
        require_once(trailingslashit(ABSPATH) . Environment::adminDirName() . '/includes/image.php');

        // Generate the metadata for the attachment, and update the database record.
        $attachmentData = wp_generate_attachment_metadata($attachmentId, $filePath);

        if($mediaFile->getMediaAlt()) update_post_meta($attachmentId, '_wp_attachment_image_alt', $mediaFile->getMediaAlt());

        wp_update_attachment_metadata($attachmentId, $attachmentData);
        return $attachmentId;
    }

    /**
     * Saves the given URL in uploads folder and returns full URL of the uploaded file.
     *
     * @param string $fileUrl               Full URL of the file to be downloaded
     * @param null|string $userAgentString  The user agent to be used when downloading the file. If null, WP's default
     *                                      user agent will be used.
     * @param int $timeoutSeconds           Timeout, in seconds.
     * @return array|null An array with keys <b>'url'</b> (full URL for the file), <b>'file'</b> (absolute path of the
     *          file) and <b>'type'</b> (type of the file), or null
     */
    public function saveMedia($fileUrl, $userAgentString = null, $timeoutSeconds = 10) {
        // Built on the example at: https://codex.wordpress.org/Function_Reference/wp_handle_sideload
        // Gives us access to the download_url() and wp_handle_sideload() functions
        require_once(trailingslashit(ABSPATH) . Environment::adminDirName() . '/includes/file.php');

        // Changes WP's default user agent.
        $filterPriority = 87; // This is just a number. Its value does not matter.
        $filterTag = 'http_headers_useragent';
        $filterCallable = function() use ($userAgentString) {
            return $userAgentString;
        };

        // First, try to remove the filter, if it was previously added. By this way, if the userAgentString is null,
        // the default user agent of WP will be used, since below we only add the filter when there is a userAgentString.
        remove_filter($filterTag, $filterCallable, $filterPriority);

        // Now, if there is a user agent string other than null, add it.
        if ($userAgentString !== null) {
            add_filter($filterTag, $filterCallable, $filterPriority);
        }

        // Download file to temp dir
        $tempFile = download_url($fileUrl, $timeoutSeconds);

        if (!is_wp_error($tempFile)) {
            $ext = strtolower(pathinfo($fileUrl, PATHINFO_EXTENSION));

            // Strip parameters and get the basename
            $fileName = basename(preg_replace('/\?.*/', '', $fileUrl));

            // Remove the extension
            $fileName = explode(".", $fileName)[0];

            // If there is no extension, get the mime-type and extract the extension from it.
            if(!$ext || mb_strpos($ext, "?") !== false) {
                $ch = curl_init($fileUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_exec($ch);

                $type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

                if(mb_strpos($type, "/") !== false) {
                    $ext = explode('/', $type)[1];

                    // Match the extension until ; or \s.
                    preg_match('/([^;\s]+)/', $ext, $matches);

                    // Set the new extension
                    if($matches && isset($matches[1])) {
                        $ext = $matches[1];
                    }
                }
            }

            // Get only the valid part of the extension
            if($ext) {
                // Get all chars, digits and dot characters until there is a character that is not one of these.
                preg_match('/^([.a-zA-Z0-9]+)/', $ext, $matches);
                if($matches && isset($matches[1])) $ext = $matches[1];
            }

            // TODO: Add an option to replace an extension with another. The user must be able to define what extension
            //  should be replaced with what extension. For example, he/she should be able to define a find-replace rule
            //  to replace "aspx" with "jpeg". This replacement can be defined in options box for files. In other words,
            //  we can put this option into the file options box.

            // Array based on $_FILE as seen in PHP file uploads
            $file = [
                'name'      => "{$fileName}.{$ext}", // ex: wp-header-logo.png
                'ext'       => $ext,
                'tmp_name'  => $tempFile,
                'error'     => 0,
                'size'      => @filesize($tempFile),
            ];

            $overrides = [
                // Tells WordPress to not look for the POST form fields that would normally be present, default is true,
                // we downloaded the file from a remote server, so there will be no form fields
                'test_form'     => false,

                // Setting this to false lets WordPress allow empty files, not recommended
                'test_size'     => true,

                // A properly uploaded file will pass this test. There should be no reason to override this one.
                'test_upload'   => true,

                'test_type'     => false,
            ];

            // Move the temporary file into the uploads directory
            $results = wp_handle_sideload($file, $overrides);

            if (empty($results['error'])) {
//                $localUrl   = $results['url'];  // URL to the file in the uploads dir
//                $filePath   = $results['file']; // Full path to the file
//                $type       = $results['type']; // MIME type of the file

                // If this has run when conducting a test, store the file path as the test file path.
                if(WPCCrawler::isDoingTest()) $this->addTestFilePath($results['file']);

                return $results;

            } else {
                // Inform the user.
                Informer::add(Information::fromInformationMessage(
                    InformationMessage::FILE_COULD_NOT_BE_SAVED_ERROR,
                    sprintf(_wpcc('%1$s, File URL: %2$s'), $results['error'], $fileUrl),
                    InformationType::INFO
                )->addAsLog());
            }

        } else {
            /** @var WP_Error $tempFile */
            // Inform the user.
            Informer::add(Information::fromInformationMessage(
                InformationMessage::FILE_COULD_NOT_BE_SAVED_ERROR,
                sprintf(_wpcc('%1$s, File URL: %2$s'), implode(' | ', $tempFile->get_error_messages()), $fileUrl),
                InformationType::INFO
            )->addAsLog());
        }

        return null;
    }

    /**
     * Saves the paths of the test files to a file. This will override the data existing in the current file storing the
     * test file paths.
     *
     * @since 1.8.0
     */
    public function saveTestFilePaths() {
        // Write the test file paths into the file
        $paths = implode("\n", $this->getTestFilePaths());
        $file = sprintf('<?php return "%1$s";', addslashes($paths));
        FileService::getInstance()->getFileSystem()->put($this->getFilePath(), $file);
    }

    /**
     * Deletes previously-saved test files by retrieving their paths from the file saved by {@link saveTestFilePaths()}.
     *
     * @since 1.8.0
     */
    public function deletePreviouslySavedTestFiles() {
        $testFilePaths = $this->getPreviousTestFilePaths();
        FileService::getInstance()->getFileSystem()->delete($testFilePaths);
    }

    /*
     * GETTERS AND SETTERS
     */

    /**
     * @return string[]
     */
    public function getTestFilePaths() {
        return $this->testFilePaths;
    }

    /**
     * Add a test file path that should be deleted later.
     *
     * @param string $path Path of the test file that will be deleted later.
     * @since 1.8.0
     */
    public function addTestFilePath($path) {
        /*
         * Do not add the path if
         *  . This is not a test.
         *  . The path is not a valid path.
         *  . The path already exists as a test path.
         *  . The path is not a file.
         */
        if (!WPCCrawler::isDoingTest() ||
            !$path ||
            in_array($path, $this->testFilePaths) ||
            !Factory::fileSystem()->isFile($path)
        ) {
            return;
        }

        $this->testFilePaths[] = $path;
    }

    /**
     * Remove a test file path.
     *
     * @param string $path
     * @since 1.8.0
     */
    public function removeTestFilePath($path) {
        /*
         * Do not add the path if
         *  . This is not a test.
         *  . The path is not a valid path.
         */
        if (!WPCCrawler::isDoingTest() || !$path) return;

        // Find the key of the test file path
        $key = array_search($path, $this->testFilePaths);

        // If it exists, remove the path.
        if ($key !== false) {
            unset($this->testFilePaths[$key]);
        }
    }

    /*
     * PRIVATE HELPERS
     */

    /**
     * Get the test file paths that were saved by {@link saveTestFilePaths()}.
     *
     * @return array
     * @since 1.8.0
     */
    private function getPreviousTestFilePaths() {
        // If the file does not exist, return an empty array.
        if(!FileService::getInstance()->getFileSystem()->isFile($this->getFilePath())) return [];

        // Get the new-line separated file paths
        $newLineSeparatedSlashedString = include($this->getFilePath());
        if (!$newLineSeparatedSlashedString) return [];

        // Unslash the string
        $unslashed = stripslashes($newLineSeparatedSlashedString);
        if (!$unslashed) return [];

        // Explode from the new lines to get the file paths as an array
        return explode("\n", $unslashed);
    }

    /**
     * Get the path of the file that stores the test file paths
     *
     * @return string
     * @since 1.8.0
     */
    private function getFilePath() {
        return FileService::getInstance()->getTempDirPath() . DIRECTORY_SEPARATOR . $this->fileBaseName;
    }

}