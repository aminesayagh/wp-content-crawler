<?php
/**
 * Created by PhpStorm.
 * User: turgutsaricam
 * Date: 24/12/2018
 * Time: 12:33
 *
 * @since 1.8.0
 */

namespace WPCCrawler\Objects;


use GuzzleHttp\Psr7\Uri;
use WPCCrawler\Utils;

class DomainValidator {

    /** @var DomainValidator */
    private static $instance = null;

    private $optionCache = [];

    /**
     * Get the instance
     *
     * @return DomainValidator
     * @since 1.8.0
     */
    public static function getInstance() {
        if (static::$instance === null) static::$instance = new DomainValidator();
        return static::$instance;
    }

    /** This is a singleton. */
    private function __construct() { }

    /**
     * Checks if a domain is valid using the domains provided by the user.
     *
     * @param string $domainListOptionName Name of the option that stores the domain list.
     *                                     E.g. '_wpcc_allowed_iframe_short_code_domains'
     * @param string $sourceUrl            Source URL to be validated against. E.g. "https://wordpress.org/resource/path/"
     * @return bool If the source URL is from a domain supplied in the options
     * @since 1.8.0
     */
    public function validate($domainListOptionName, $sourceUrl) {
        if (!$sourceUrl) return false;

        // Get the regular expressions that match valid domains
        $domainRegexes = $this->getDomainRegexes($domainListOptionName);
        if (!$domainRegexes) return false;

        // Get the domain of the source URL
        $uri = new Uri($sourceUrl);
        $host = $uri->getHost();

        // Check if the host (domain) is one of the provided domains.
        foreach($domainRegexes as $regex) {
            if ($this->isDomainValid($regex, $host)) {
                return true;
            }
        }

        // The domain is not valid.
        return false;
    }

    /*
     *
     */

    /**
     * @param string $regex        A regex that matches a valid domain.
     * @param string $testedDomain The domain to be checked if it is valid.
     * @return bool True if the domain is valid.
     * @since 1.8.0
     */
    private function isDomainValid($regex, $testedDomain) {
        // Try to match
        return !!preg_match($regex, $testedDomain);
    }

    /**
     * @param string $domainListOptionName The option name from which the domains will be retrieved. See
     *                                     {@link getOptionValue()}
     * @return string[] An array of regular expressions that match valid domains
     * @since 1.8.0
     */
    private function getDomainRegexes($domainListOptionName) {
        $domainData = $this->getOptionValue($domainListOptionName);
        if (!$domainData) return [];

        $regexes = [];
        foreach($domainData as $data) {
            $domain = Utils::array_get($data, 'domain');
            if (!$domain) continue;

            // Prepare the regular expression
            $domain = trim($domain, '/');                               // Trim the forward slashes
            $domain = preg_quote($domain, '/');                        // Quote the regex
            $domain = str_replace(['\*\.', '\*'], '.*?', $domain);      // Replace wildcards with corresponding regex.
            $regex = "/^{$domain}$/i";                                          // Create the final regex that matches non case-sensitive

            $regexes[] = $regex;
        }

        return $regexes;
    }

    /**
     * Get an option's value. This method caches the results.
     *
     * @param string $optionName Name of the option
     * @return mixed Value of the option
     * @since 1.8.0
     */
    private function getOptionValue($optionName) {
        if (!isset($this->optionCache[$optionName])) {
            $this->optionCache[$optionName] = get_option($optionName);
        }

        return $this->optionCache[$optionName];
    }
}