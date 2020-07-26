<?php
/* This file is a modified version of the Hedii\ZoteroApi
 * The file is licensed under MIT.
 */
namespace dokuwiki\plugin\bibliography\meta\data\zotero;

use dokuwiki\HTTP\DokuHTTPClient;
use \BadMethodCallException;
use \InvalidArgumentException;

class ZoteroApi
{
    /**
     * The base URL for all API requests.
     */
    const API_BASE_URL = 'https://api.zotero.org/';

    /**
     * The api version.
     *
     * @var int
     */
    private $version = 3;

    /**
     * The user API key.
     *
     * @var string
     */
    private $apiKey;

    /**
     * The http client.
     *
     * @var dokuwiki\HTTP\DokuHTTPClient
     */
    private $client;

    /**
     * The request timeout in seconds.
     *
     * @var int
     */
    private $timeout = 5;

    /**
     * The api url path.
     *
     * @var string|null
     */
    private $path = null;

    /**
     * @var string
     */
    private $format = 'json';

    /** Response Body from @see dokuwiki\HTTP\DokuHTTPClient
     * @var string
     */
    public $response;

    /**
     * ZoteroApi constructor.
     *
     * @param string $apiKey
     */
    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
        $this->client = new DokuHTTPClient();
    }

    /**
     * Get the api version.
     *
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set the api version.
     *
     * @param int $version
     * @return $this
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get the request timeout.
     *
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * Set the request timeout.
     *
     * @param int $timeout
     * @return $this
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * Get the api request path.
     *
     * @return null|string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set the api request path.
     *
     * @param string $path
     * @return $this
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get the requested format.
     *
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Set the requested format.
     *
     * @param string $format
     * @return $this
     */
    public function setFormat($format)
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Get the instance of the http client.
     *
     * @return \GuzzleHttp\Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Gets the response status code.
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->response->getStatusCode();
    }

    /**
     * Gets the response reason phrase associated with the status code.
     *
     * @return string
     */
    public function getReasonPhrase()
    {
        return $this->response->getReasonPhrase();
    }

    /**
     * Set the path with a raw string.
     *
     * @param string $path
     * @return $this
     * @throws \BadMethodCallException
     */
    public function raw($path)
    {
        if ($this->path !== null) {
            throw new BadMethodCallException(
                'Method raw() has to be called before any other method'
            );
        }

        $this->setPath($path);

        return $this;
    }

    /**
     * Send the request.
     *
     * @return $this
     */
    public function send()
    {
        if (strpos($this->path, 'format') == false) { // Add format parameter only if not there.
          $this->addQueryString($this->path, ['format' => $this->getFormat()]);
        }

        $this->client->timeout = $this->timeout;
        $this->client->headers['Authorization'] = 'Bearer ' . $this->apiKey;
        $this->client->headers['Zotero-API-Version'] = $this->version;
        $this->response = $this->client->get(ZoteroApi::API_BASE_URL . $this->path);

        return $this;
    }

    /**
     * Get the response headers as an array.
     *
     * @return array
     * @throws \BadMethodCallException
     */
    public function getHeaders()
    {
        if (! $this->response) {
            throw new BadMethodCallException(
                'Cannot call getHeaders() on null'
            );
        }

        return $this->client->resp_headers;
    }

    /**
     * Get the response body as an array.
     *
     * @return array
     * @throws \BadMethodCallException
     */
    public function getBody()
    {
        if (!$this->response) {
            throw new BadMethodCallException(
                'Cannot call getBody() on null'
            );
        }

        return $this->toArray($this->response);
    }

    /**
     * Get the response body as a json string.
     *
     * @return string
     * @throws \BadMethodCallException
     */
    public function getJson()
    {
        if (! $this->response) {
            throw new BadMethodCallException(
                'Cannot call getJson() on null'
            );
        }

        return (string) $this->response;
    }

    /**
     * Prepare the path to call a user resource.
     *
     * @param int $userId
     * @return $this
     */
    public function user($userId)
    {
        $this->setPath('users/' . $userId);

        return $this;
    }

    /**
     * Prepare the path to call a group resource.
     *
     * @param int $groupId
     * @return $this
     */
    public function group($groupId)
    {
        $this->setPath('groups/' . $groupId);

        return $this;
    }

    /**
     * Build the path to get either the set of all items in the library, or a
     * specific item in the library if a key is passed as a parameter.
     *
     * @param string[]|string|null $key
     * @return $this
     * @throws \BadMethodCallException
     */
    public function items($key = null) {
        $this->setPath($key && !is_array($key) ? $this->path . '/items/' . $key : $this->path . '/items');

        if (is_array($key)) {
          $this->addQueryString($this->path, ["itemKey" => join(",", $key)]);
        }
        
        if (
            ! $this->contains($this->path, 'users/') &&
            ! $this->contains($this->path, 'groups/')
        ) {
            throw new BadMethodCallException(
                'Method items() has to be called after method user($userId), method group($groupId) or method collections($key)'
            );
        }

        return $this;
    }

    /**
     * Build the path to get the set of all top-level items in the library.
     *
     * @return $this
     * @throws \BadMethodCallException
     */
    public function top()
    {
        $this->setPath($this->path . '/top');

        if (
            ! $this->contains($this->path, '/items/top') &&
            ! $this->contains($this->path, '/collections/top')
        ) {
            throw new BadMethodCallException(
                'Method top() has to be called after method items() or collections()'
            );
        }

        return $this;
    }

    /**
     * Build the path to get the set of items in the trash.
     *
     * @return $this
     * @throws \BadMethodCallException
     */
    public function trash()
    {
        $this->setPath($this->path . '/trash');

        if (! $this->contains($this->path, '/items/trash')) {
            throw new BadMethodCallException(
                'Method trash() has to be called after method items() without parameters'
            );
        }

        return $this;
    }

    /**
     * Build the path to get the set of all child items under a specific item.
     *
     * @return $this
     * @throws \BadMethodCallException
     */
    public function children()
    {
        $this->setPath($this->path . '/children');

        if (
            ! $this->contains($this->path, '/items/') ||
            $this->contains($this->path, '/items/children')
        ) {
            throw new BadMethodCallException(
                'Method children() has to be called after method items($itemKey)'
            );
        }

        return $this;
    }

    /**
     * Build the path to get the set of all tags associated with
     * a specific item.
     *
     * @param string|null $tag
     * @return $this
     * @throws \BadMethodCallException
     */
    public function tags($tag = null)
    {
        if ($tag) {
            if (! is_string($tag)) {
                throw new InvalidArgumentException(
                    'Method tags($tag) parameter has to be a string'
                );
            }

            $this->setPath($this->path . '/tags/' . rawurlencode($tag));

            if (
                ! $this->contains($this->path, 'users/') &&
                ! $this->contains($this->path, 'groups/')
            ) {
                throw new BadMethodCallException(
                    'Method tags($tag) has to be called just after method user($userId) or method group($groupId)'
                );
            }

            if (
                $this->contains($this->path, '/items') ||
                $this->contains($this->path, '/collections')
            ) {
                throw new BadMethodCallException(
                    'Method tags($tag) with a tag string as a parameter cannot be called after methods items() or method collections()'
                );
            }

            return $this;
        }

        $this->setPath($this->path . '/tags');

        if (
            $this->contains($this->path, '/items/tags') ||
            $this->contains($this->path, '/collections/tags') ||
            (
                ! $this->contains($this->path, 'users/') &&
                ! $this->contains($this->path, 'groups/')
            )
        ) {
            throw new BadMethodCallException(
                'Method tags() has to be called after method user($userId), method group($groupId), method items($key) or method collections($key)'
            );
        }

        return $this;
    }

    /**
     * Build the path to get either the set of all collections in the library,
     * or a collection in the library if a key is passed as a parameter.
     *
     * @param string|null $key
     * @return $this
     * @throws \BadMethodCallException
     */
    public function collections($key = null)
    {
        $this->setPath(
            $key ? $this->path . '/collections/' . $key : $this->path . '/collections'
        );

        if (
            ! $this->contains($this->path, 'users/') &&
            ! $this->contains($this->path, 'groups/')
        ) {
            throw new BadMethodCallException(
                'Method collections() has to be called after method user($userId) or method group($groupId)'
            );
        }

        return $this;
    }

    /**
     * Build the path to get the set of sub-collections within a specific
     * collection in the library
     *
     * @return $this
     * @throws \BadMethodCallException
     */
    public function subCollections()
    {
        if (! $this->contains($this->path, '/collections/')) {
            throw new BadMethodCallException(
                'Method subCollections() has to be called after method collections($key)'
            );
        }

        $this->setPath($this->path . '/collections');

        return $this;
    }

    /**
     * Build the path to get the set of all saved searches in the library.
     *
     * @param string|null $key
     * @return $this
     * @throws \BadMethodCallException
     */
    public function searches($key = null)
    {
        $this->setPath(
            $key ? $this->path . '/searches/' . $key : $this->path . '/searches'
        );

        if (
            ! $this->contains($this->path, 'users/') &&
            ! $this->contains($this->path, 'groups/')
        ) {
            throw new BadMethodCallException(
                'Method searches() has to be called just after method user($userId) or method group($groupId)'
            );
        }

        if (
            $this->contains($this->path, '/items') ||
            $this->contains($this->path, '/collections')
        ) {
            throw new BadMethodCallException(
                'Method searches() has to be called just after method user($userId) or method group($groupId)'
            );
        }

        return $this;
    }

    /**
     * Build the path to get the user id and privileges of the given API key.
     *
     * @param string $key
     * @return $this
     */
    public function key($key)
    {
        $this->setPath('keys/' . $key);

        return $this;
    }

    /**
     * Build the path to get the set of groups the current API key has access
     * to, including public groups the key owner belongs to even if the key
     * does not have explicit permissions for them.
     *
     * @return $this
     * @throws \BadMethodCallException
     */
    public function groups()
    {
        if (
            ! $this->contains($this->path, 'users/') ||
            $this->contains($this->path, '/items') ||
            $this->contains($this->path, '/collections')
        ) {
            throw new BadMethodCallException(
                'Method groups() has to be called just after method user($userId)'
            );
        }

        $this->setPath($this->path . '/groups');

        return $this;
    }

    /**
     * The name of the field by which entries are sorted.
     *
     * @param string $value
     * @return $this
     * @throws \BadMethodCallException
     */
    public function sortBy($value)
    {
        if (
            ! in_array($value, [
                'dateAdded', 'dateModified', 'title', 'creator', 'type', 'date', 'publisher', 'publicationTitle', 'journalAbbreviation', 'language', 'accessDate', 'libraryCatalog', 'callNumber', 'rights', 'addedBy', 'numItems'
            ])
        ) {
            throw new InvalidArgumentException(
                'Method sort($value) parameter has to be one of dateAdded, dateModified, title, creator, type, date, publisher, publicationTitle, journalAbbreviation, language, accessDate, libraryCatalog, callNumber, rights, addedBy or numItems'
            );
        }

        $this->addQueryString($this->path, ['sort' => $value]);

        return $this;
    }

    /**
     * The sorting direction of the field specified in the sort parameter.
     *
     * @param string $value
     * @return $this
     * @throws \BadMethodCallException
     */
    public function direction($value)
    {
        if (! in_array($value, ['asc', 'desc'])) {
            throw new InvalidArgumentException(
                'Method sort($value) parameter has to be one of asc or desc'
            );
        }

        $this->addQueryString($this->path, ['direction' => $value]);

        return $this;
    }

    /**
     * The maximum number of results to return with a single request.
     * Required for export formats.
     *
     * @param int $value
     * @return $this
     * @throws \BadMethodCallException
     */
    public function limit($value)
    {
        if (! is_numeric($value) || ($value <= 0 || $value > 100)) {
            throw new InvalidArgumentException(
                'Method limit($value) parameter has to be an integer between 1 and 100'
            );
        }

        $this->addQueryString($this->path, ['limit' => $value]);

        return $this;
    }

    /**
     * The index of the first result. Combine with the limit parameter to
     * select a slice of the available results.
     *
     * @param int $value
     * @return $this
     * @throws \BadMethodCallException
     */
    public function start($value)
    {
        if (! is_numeric($value)) {
            throw new InvalidArgumentException(
                'Method start($value) parameter has to be an integer'
            );
        }

        $this->addQueryString($this->path, ['start' => $value]);

        return $this;
    }

    public function since($version)
    {
      $this->addQueryString($this->path, ['since' => $version]);
      return $this;
    }

    public function include($formats)
    {
      $this->addQueryString($this->path, ['include' => is_array($formats) ? join(',', $formats) : $formats]);
      //Fixme check if format is json
      return $this;
    }

    /**
     * Build the path to get an object of all resources with item or collection
     * keys as keys and their versions as values. It cannot be called on single
     * collection or single item. The number of resources returned is not
     * limited by the api: all items or collections versions are returned.
     *
     * @return $this
     * @throws \BadMethodCallException
     */
    public function versions()
    {
        $this->addQueryString($this->path, ['format' => 'versions']);

        if (
            ! $this->contains($this->path, 'items') &&
            ! $this->contains($this->path, 'collections')
        ) {
            throw new BadMethodCallException(
                'Method versions() can only be called on multi-object collection and multiple items'
            );
        }

        return $this;
    }

    /**
     * Format a json string to an array.
     *
     * @param string $body
     * @return mixed
     */
    private function toArray($body)
    {
        return json_decode($body, true);
    }

    /**
     * Determine if a given string contains a given substring.
     *
     * @param string $haystack
     * @param string|array $needles
     * @return bool
     */
    private function contains($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ($needle != '' && mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Append query strings to the path.
     *
     * @param string $path
     * @param array $queries
     */
    private function addQueryString($path, array $queries)
    {
        foreach ($queries as $key => $value) {
            $separator = (strpos($path, '?') !== false) ? '&' : '?';
            $path .= $separator . $key . '=' . $value;
        }

        $this->setPath($path);
    }
}
