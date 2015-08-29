<?php
/**
 * Part of the Caffeinated PHP packages.
 *
 * MIT License and copyright information bundled with this package in the LICENSE file
 */
namespace Caffeinated\Bonsai\Filters;

use Assetic\Asset\AssetInterface;
use Assetic\Filter\FilterInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Credit for this filter goes to Jason Lewis, with his no longer maintained Basset package.
 *
 * @author    Jason Lewis
 * @link      <http://jasonlewis.me/code/basset>
 * @license   BSD-2-Clause
 * @package   Basset
 * @copyright 2012-2013 Jason Lewis
 */

/**
 * UriRewriteFilter is a rewrite and port of the popular CssUriRewrite class written by Steve Clay.
 * Original source can be found by following the links below.
 *
 * @author    Steve Clay
 * @link      <https://github.com/mrclay/minify>
 * @license   <https://github.com/mrclay/minify/blob/master/LICENSE.txt>
 * @package   Minify
 * @copyright 2008 Steve Clay / Ryan Grove
 */
class UriRewriteFilter implements FilterInterface
{
    /**
     * Applications document root. This is typically the public directory.
     *
     * @var string
     */
    protected $documentRoot;

    /**
     * Symfony request instance.
     *
     * @var string
     */
    protected $request;

    /**
     * Root directory of the asset.
     *
     * @var string
     */
    protected $assetDirectory;

    /**
     * Array of symbolic links.
     *
     * @var array
     */
    protected $symlinks;

    /**
     * Create a new UriRewriteFilter instance.
     *
     * @param  string $documentRoot
     * @param  array  $symlinks
     */
    public function __construct($documentRoot = null, $symlinks = array())
    {
        $this->documentRoot = $this->realPath($documentRoot);
        $this->symlinks     = $symlinks;
    }

    /**
     * Apply filter on file load.
     *
     * @param  \Assetic\Asset\AssetInterface $asset
     * @return void
     */
    public function filterLoad(AssetInterface $asset)
    {
    }

    /**
     * Apply a filter on file dump.
     *
     * @param  \Assetic\Asset\AssetInterface $asset
     * @return void
     */
    public function filterDump(AssetInterface $asset)
    {
        $this->assetDirectory = $this->realPath($asset->getSourceRoot());
        $content              = $asset->getContent();
        // Spin through the symlinks and normalize them. We'll first unset the original
        // symlink so that it doesn't clash with the new symlinks once they are added
        // back in.
        foreach ($this->symlinks as $link => $target) {
            unset($this->symlinks[ $link ]);
            if ($link == '//') {
                $link = $this->documentRoot;
            } else {
                $link = str_replace('//', $this->documentRoot . '/', $link);
            }
            $link                    = strtr($link, '/', DIRECTORY_SEPARATOR);
            $this->symlinks[ $link ] = $this->realPath($target);
        }
        $content = $this->trimUrls($content);
        $content = preg_replace_callback('/@import\\s+([\'"])(.*?)[\'"]/', array( $this, 'processUriCallback' ), $content);
        $content = preg_replace_callback('/url\\(\\s*([^\\)\\s]+)\\s*\\)/', array( $this, 'processUriCallback' ), $content);
        $asset->setContent($content);
    }

    /**
     * Returns or creates a new symfony request.
     *
     * @return \Symfony\Component\HttpFoundation\Request
     */
    public function getRequest()
    {
        return $this->request ?: $this->request = Request::createFromGlobals();
    }

    /**
     * Sets the request instance.
     *
     * @param  \Symfony\Component\HttpFoundation\Request
     * @return void
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }

    /**
     * Takes a path and transforms it to a real path.
     *
     * @param  string $path
     * @return string
     */
    protected function realPath($path)
    {
        if (php_sapi_name() == 'cli' && ! $path) {
            $path = $_SERVER[ 'DOCUMENT_ROOT' ];
        }
        if ($realPath = realpath($path)) {
            $path = $realPath;
        }

        return rtrim($path, '/\\');
    }

    /**
     * Trims URLs.
     *
     * @param  string $content
     * @return string
     */
    protected function trimUrls($content)
    {
        return preg_replace('/url\\(\\s*([^\\)]+?)\\s*\\)/x', 'url($1)', $content);
    }

    /**
     * Processes a regular expression callback, determines the URI and returns the rewritten URIs.
     *
     * @param  array $matches
     * @return string
     */
    protected function processUriCallback($matches)
    {
        $scriptName = basename($this->getRequest()->getScriptName());
        $isImport   = $matches[ 0 ][ 0 ] === '@';
        // Determine what the quote character and the URI is, if there is one.
        $quoteCharacter = $uri = null;
        if ($isImport) {
            $quoteCharater = $matches[ 1 ];
            $uri           = $matches[ 2 ];
        } else {
            if ($matches[ 1 ][ 0 ] === "'" or $matches[ 1 ][ 0 ] === '"') {
                $quoteCharacter = $matches[ 1 ][ 0 ];
            }
            if (! $quoteCharacter) {
                $uri = $matches[ 1 ];
            } else {
                $uri = substr($matches[ 1 ], 1, strlen($matches[ 1 ]) - 2);
            }
        }
        // Strip off the scriptname
        $uri = str_replace($scriptName . '/', '', $uri);
        // Analyze the URI
        if ($uri[ 0 ] !== '/' and strpos($uri, '//') === false and strpos($uri, 'data') !== 0) {
            $uri = $this->rewriteAbsolute($uri);
        }
        if ($isImport) {
            return "@import {$quoteCharacter}{$uri}{$quoteCharacter}";
        }

        return "url({$quoteCharacter}{$uri}{$quoteCharacter})";
    }

    /**
     * Rewrites a relative URI.
     *
     * @param  string $uri
     * @return string
     */
    protected function rewriteAbsolute($uri)
    {
        $request = $this->getRequest();
        $path    = strtr($this->assetDirectory, '/', DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . strtr($uri, '/', DIRECTORY_SEPARATOR);
        foreach ($this->symlinks as $link => $target) {
            if (strpos($path, $target) === 0) {
                $path = $link . substr($path, strlen($target));
                break;
            }
        }
        $base = isset($_SERVER[ 'REQUEST_URI' ]) ? $_SERVER[ 'REQUEST_URI' ] : null;
        if ($request->getHost()) {
            $base = $request->getSchemeAndHttpHost() . $request->getBaseUrl();
        }
        // Prepend the base url to compile the correct paths
        // for subdirectories and symlinked directories
        $path       = $base . substr($path, strlen($this->documentRoot));
        $scriptName = basename($request->getScriptName());
        // Strip off the scriptname (index.php) if present
        $path = str_replace($scriptName . '/', '', $path);
        $uri  = strtr($path, '/\\', '//');
        $uri  = $this->removeDots($uri);

        return $uri;
    }

    /**
     * Removes dots from a URI.
     *
     * @param  string $uri
     * @return string
     */
    protected function removeDots($uri)
    {
        $uri = str_replace('/./', '/', $uri);
        do {
            $uri = preg_replace('@/[^/]+/\\.\\./@', '/', $uri, 1, $changed);
        } while ($changed);

        return $uri;
    }
}
