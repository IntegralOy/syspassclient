<?php

declare(strict_types=1);

/**
 * This file is part of the sysPassClient package.
 *
 * (c) Integral Oy <integral@integral.fi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Integral\SysPass;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Process\Process;

/**
 * Class SysPassClientCommand
 * @package Integral\SysPass
 */
class SysPassClientCommand extends Command
{
    /**
     * @var SysPassClient
     */
    protected $sysPass;

    /**
     * @var Clipboard
     */
    protected $clipboard;

    /**
     * @var ConfigReader
     */
    protected $configReader;

    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->clipboard = new Clipboard();
        $this->configReader = new ConfigReader();
    }

    public function configure()
    {
        $this->addArgument('search', InputArgument::REQUIRED, 'Account search string');

        $this->addOption('no-shell', 's', InputOption::VALUE_NONE, 'Do not open a shell');
        $this->addOption('show-password', 'p', InputOption::VALUE_NONE, 'Show passwords as plain text');
        $this->addOption('config', 'c', InputOption::VALUE_OPTIONAL, 'Config file name in current working directory');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $config = $this->configReader->read($input->getOption('config'));

            $this->sysPass = new SysPassClient(
                $config['token'],
                $config['pass'],
                $config['host'],
                new \GuzzleHttp\Client()
            );

            $search = $input->getArgument('search');
            $accountId = $this->searchAccount($input, $output, $search);
            $passwordData = $this->sysPass->getPassword($accountId);

            $copiedToClipboard = false;
            if (!$input->getOption('show-password')) {
                try {
                    if ($this->clipboard->isSupported()) {
                        $copiedToClipboard = $this->clipboard->copy($passwordData['password']);
                    }
                } catch (\RuntimeException $e) {
                    $copiedToClipboard = false;
                }
            }

            $this->showResults($output, $passwordData, $copiedToClipboard);

            if (!$input->getOption('no-shell')) {
                $this->openShellIfSSH($passwordData);
            }
        } catch (\Exception $e) {
            $output->writeln('');
            $output->writeln('<error>'.$e->getMessage(). '</error>');
            $output->writeln('');

            return 1;
        }

        return 0;
    }

    /**
     * @param array $passwordData
     */
    protected function openShellIfSSH(array $passwordData)
    {
        if (isset($passwordData['url']) && Process::isTtySupported() && strpos($passwordData['url'], 'ssh ') === 0) {
            $p = new Process($passwordData['url']);
            $p->setTty(true);
            $p->start();
            $p->wait();
        }
    }

    /**
     * @param OutputInterface $output
     * @param array $passwordData
     * @param $passwordOnClipboard
     */
    protected function showResults(OutputInterface $output, array $passwordData, $passwordOnClipboard)
    {
        if ($passwordOnClipboard && isset($passwordData['password'])) {
            $passwordData['password'] = '<comment>Copied to your clipboard</comment>';
        }

        $table = new Table($output);
        $table->setHeaders([
            'Name',
            'Username',
            'Password',
            'Address',
            'Tags'
        ])->setRows([$passwordData]);

        $table->render();
    }

    /**
     * @param string $search
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return string $accountId matching the search string
     * @throws \RuntimeException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function searchAccount(InputInterface $input, OutputInterface $output, $search) : string
    {
        $data = $this->sysPass->search($search);

        if (!isset($data->result) || \count($data->result) === 0) {
            throw new \RuntimeException('No results found');
        }
        $results = \count($data->result);

        if ($results > 1) {
            $list = [];
            foreach ($data->result as $account) {
                $url = $account->account_login. ' @ ' .$account->account_url;
                if (strpos($account->account_url, 'ssh://') !== false) {
                    $url = 'ssh ' .$account->account_login.'@'.preg_replace('/^ssh:\/\//', '', $account->account_url);
                }

                $list[$account->account_id] = $account->account_name. ' | ' .$url;
            }

            $helper = $this->getHelper('question');
            $question = new ChoiceQuestion(
                'Choose the correct account',
                $list,
                null
            );
            $question->setErrorMessage('Account %s is invalid.');

            $accountId = (string) array_search(trim($helper->ask($input, $output, $question)), $list, true);
        } elseif ($results === 1) {
            $accountId = (string) $data->result[0]->account_id;
        } else {
            throw new \RuntimeException('Unknown result');
        }

        return $accountId;
    }
}
