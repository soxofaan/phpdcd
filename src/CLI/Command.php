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
 * @author    Sebastian Bergmann <sebastian@phpunit.de>
 * @copyright 2009-2013 Sebastian Bergmann <sebastian@phpunit.de>
 * @license   http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @since     File available since Release 1.0.0
 */

namespace SebastianBergmann\PHPDCD\CLI;

use SebastianBergmann\PHPDCD\Detector;
use SebastianBergmann\PHPDCD\Log\Text;
use SebastianBergmann\FinderFacade\FinderFacade;
use Symfony\Component\Console\Command\Command as AbstractCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author    Sebastian Bergmann <sebastian@phpunit.de>
 * @copyright 2009-2013 Sebastian Bergmann <sebastian@phpunit.de>
 * @license   http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link      http://github.com/sebastianbergmann/phpdcd/tree
 * @since     Class available since Release 1.0.0
 */
class Command extends AbstractCommand
{
    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setName('phpdcd')
             ->setDefinition(
                 array(
                     new InputArgument(
                         'values',
                         InputArgument::IS_ARRAY
                     )
                 )
             )
             ->addOption(
                 'names',
                 null,
                 InputOption::VALUE_REQUIRED,
                 'A comma-separated list of file names to check',
                 array('*.php')
             )
             ->addOption(
                 'names-exclude',
                 null,
                 InputOption::VALUE_REQUIRED,
                 'A comma-separated list of file names to exclude',
                 array()
             )
             ->addOption(
                 'exclude',
                 null,
                 InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                 'Exclude a directory from code analysis'
             )
             ->addOption(
                 'recursive',
                 null,
                 InputOption::VALUE_NONE,
                 'Report code as dead if it is only called by dead code'
             )
             ->addOption(
                'progress',
                null,
                InputOption::VALUE_NONE,
                'Show a progress bar while analysing files'
             );
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return null|integer null or 0 if everything went fine, or an error code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $finder = new FinderFacade(
            $input->getArgument('values'),
            $input->getOption('exclude'),
            $this->handleCSVOption($input, 'names'),
            $this->handleCSVOption($input, 'names-exclude')
        );

        $files = $finder->findFiles();

        if (empty($files)) {
            $output->writeln('No files found to scan');
            exit(1);
        }

        $quiet = $output->getVerbosity() == OutputInterface::VERBOSITY_QUIET;

        if ($input->getOption('progress')) {
            $progressBar = $this->getHelperSet()->get('progress');
            $progressBar->start($output, count($files));
            $advanceCallback = function ($file) use ($progressBar) { $progressBar->advance(); };
        }
        else {
            $progressBar = null;
            $advanceCallback = null;
        }

        $detector = new Detector;

        $result = $detector->detectDeadCode(
            $files,
            $input->getOption('recursive'),
            $advanceCallback
        );

        if ($progressBar) {
            $progressBar->finish();
        }

        if (!$quiet) {
            $printer = new Text;
            $printer->printResult($output, $result);

            $output->writeln(\PHP_Timer::resourceUsage());
        }
    }

    /**
     * @param  Symfony\Component\Console\Input\InputOption $input
     * @param  string                                      $option
     * @return array
     */
    private function handleCSVOption(InputInterface $input, $option)
    {
        $result = $input->getOption($option);

        if (!is_array($result)) {
            $result = explode(',', $result);
            array_map('trim', $result);
        }

        return $result;
    }
}
