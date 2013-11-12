<?php
/**
 * phpdcd
 *
 * Copyright (c) 2009-2013, Sebastian Bergmann <sebastian@phpunit.de>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Sebastian Bergmann nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package   phpdcd
 * @author    Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright 2009-2013 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license   http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @since     File available since Release 1.0.0
 */

namespace SebastianBergmann\PHPDCD\Log;

/**
 * @author    Stefaan Lippens <soxofaan@gmail.com>
 * @copyright 2009-2013 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license   http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @version   Release: @package_version@
 * @link      http://github.com/sebastianbergmann/phpdcd/tree
 * @since     Class available since Release 1.0.0
 */
class HtmlReport
{
    /**
     * Root path of HTML resources (templates, css, js, ...)
     * @var string
     */
    protected $resourcePath;

    public function __construct($phpdcdVersion=null)
    {
        $this->phpdcdVersion = $phpdcdVersion;
        $this->resourcePath = $this->pathJoin(dirname(__FILE__), 'HtmlReport');
    }

    public function exportResult($exportPath, array $result)
    {
        // Set up Twig for template rendering.
        $loader = new \Twig_Loader_Filesystem($this->pathJoin($this->resourcePath, 'templates'));
        $twig = new \Twig_Environment($loader, array('strict_variables' => true));
        // Add some extra filters
        $twig->addFilter(new \Twig_SimpleFilter('basename', 'basename'));

        // Render report template.
        $html = $twig->render(
            'report.twig',
            array(
                'result' => $result,
                'version' => $this->phpdcdVersion,
                'php_version' => PHP_VERSION,
                'date' => date('Y-m-d H:i:s'),
            )
        );

        $exportPath = $this->ensureDirectory($exportPath);

        // Store report.
        $htmlFilename = $this->pathJoin($exportPath, 'index.html');
        $f = fopen($htmlFilename, 'w');
        fwrite($f, $html);
        fclose($f);
        $this->copyStaticFiles($exportPath);
    }

    protected function copyStaticFiles($exportPath)
    {
        // (Sub)tree to copy from resource directory to export path.
        $toCopy = array(
            'css' => array(
                'style.css',
                'lib' => array(
                    'bootstrap.min.css',
                )
            ),
            'js' => array(
                'lib' => array(
                    'jquery-2.0.3.min.js',
                    'bootstrap.min.js',
                    'Stupid-Table-Plugin' => array(
                        'stupidtable.min.js',
                        'LICENSE',
                    ),
                ),
            ),
        );

        // Do copying of the tree recursively with a closure.
        $resourceCopier = function ($from, $to, $toCopy) use (&$resourceCopier) {
            foreach ($toCopy as $name => $item) {
                if (is_array($item)) {
                    $resourceCopier(
                        $this->pathJoin($from, $name),
                        $this->ensureDirectory($this->pathJoin($to, $name)),
                        $item
                    );
                } else {
                    $r = @copy($this->pathJoin($from, $item), $this->pathJoin($to, $item));
                    if (!$r) {
                        throw new \RuntimeException(
                            sprintf(
                                'Failed to mare copy resource directory "%s" from "%s" to "%s".',
                                $item,
                                $from,
                                $to
                            )
                        );

                    }
                }
            }
        };

        $resourceCopier($this->resourcePath, $exportPath, $toCopy);
    }

    /**
     * Helper function to join path components together to a full path.
     * @return string
     */
    public function pathJoin()
    {
        // Get given path components.
        $components = func_get_args();
        // Handle single array argument use case.
        if (count($components) == 1 && is_array($components[0])) {
            $components = $components[0];
        }
        // Build path.
        $path = array_shift($components);
        foreach ($components as $component) {
            if (substr($component, 0, 1) === DIRECTORY_SEPARATOR) {
                $component = substr($component, 1);
            }
            $path .= (substr($path, -1) !== DIRECTORY_SEPARATOR ? DIRECTORY_SEPARATOR : '') . $component;
        }
        return $path;
    }


    /**
     * Make sure a directory exists
     * @param $path string directory path
     * @throws \RuntimeException
     * @return string given path
     */
    protected function ensureDirectory($path)
    {
        if (is_dir($path)) {
            return $path;
        }

        if (@mkdir($path, 0777, true)) {
            return $path;
        }

        throw new \RuntimeException(
            sprintf(
                'Failed to make sure directory "%s" exists.',
                $path
            )
        );
    }
}
