<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoDevBox\Command;

require_once __DIR__ . '/../AbstractCommand.php';

use MagentoDevBox\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for Redis setup
 */
class MagentoSetupRedis extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('magento:setup:redis')
            ->setDescription('Setup Redis for Magento')
            ->setHelp('This command allows you to setup Redis for Magento.');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $host = $input->getOption('redis-host');
        $configPath = sprintf('%s/app/etc/env.php', $this->requestOption('magento-path', $input, $output));
        $config = include $configPath;

        if ($input->getOption('redis-session-setup')) {
            $config['session'] = [
                'save' => 'redis',
                'redis' => [
                    'host' => $host,
                    'port' => '6379',
                    'password' => '',
                    'timeout' => '2.5',
                    'persistent_identifier' => '',
                    'database' => '0',
                    'compression_threshold' => '2048',
                    'compression_library' => 'gzip',
                    'log_level' => '1',
                    'max_concurrency' => '6',
                    'break_after_frontend' => '5',
                    'break_after_adminhtml' => '30',
                    'first_lifetime' => '600',
                    'bot_first_lifetime' => '60',
                    'bot_lifetime' => '7200',
                    'disable_locking' => '0',
                    'min_lifetime' => '60',
                    'max_lifetime' => '2592000'
                ]
            ];
        } else {
            $config['session'] = ['save' => 'files'];
        }

        if ($input->getOption('redis-fpc-setup')) {
            $config['cache']['frontend']['page_cache'] = [
                'backend' => 'Cm_Cache_Backend_Redis',
                'backend_options' => [
                    'server' => $host,
                    'port' => '6379',
                    'database' => '1',
                    'compress_data' => '0'
                ]
            ];
        } else {
            unset($config['cache']['frontend']['page_cache']);
        }

        if ($input->getOption('redis-cache-setup')) {
            $config['cache']['frontend']['default'] = [
                'backend' => 'Cm_Cache_Backend_Redis',
                'backend_options' => [
                    'server' => $host,
                    'port' => '6379'
                ]
            ];
        } else {
            unset($config['cache']['frontend']['default']);
        }

        file_put_contents($configPath, sprintf("<?php\n return %s;", var_export($config, true)));
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsConfig()
    {
        return [
            'magento-path' => [
                'initial' => true,
                'default' => '/var/www/magento2',
                'description' => 'Path to source folder for Magento',
                'question' => 'Please enter path to source folder for Magento %default%'
            ],
            'redis-fpc-setup' => [
                'initial' => true,
                'boolean' => true,
                'default' => false,
                'description' => 'Whether to use Redis as Magento full page cache.',
                'question' => 'Do you want to use Redis as Magento full page cache? %default%'
            ],
            'redis-cache-setup' => [
                'initial' => true,
                'boolean' => true,
                'default' => true,
                'description' => 'Whether to use Redis as Magento default cache.',
                'question' => 'Do you want to use Redis as Magento default cache? %default%'
            ],
            'redis-session-setup' => [
                'initial' => true,
                'boolean' => true,
                'default' => false,
                'description' => 'Whether to use Redis for storing sessions.',
                'question' => 'Do you want to use Redis for storing sessions? %default%'
            ],
            'redis-host' => [
                'initial' => true,
                'default' => 'redis',
                'requireValue' => false,
                'description' => 'Redis host.',
                'question' => 'Please enter Redis host %default%'
            ]
        ];
    }
}
