<?php

namespace Bytefusion\DeploymentBundle\Command;

use Bytefusion\DeploymentBundle\Service\CloudflareService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CloudflareCacheCommand
 * @package Bytefusion\DeploymentBundle\Command
 */
class CloudflareClearCacheCommand extends Command {

    /**
     * @var CloudflareService
     */
    private $cloudflareService;

    public function __construct(CloudflareService $cloudflareService)
    {
        $this->cloudflareService = $cloudflareService;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('deploy:cloudflare:cache')
            ->setDescription('Clear cloudflare cache')
            ->addArgument('domain', InputArgument::REQUIRED, 'Domain to clear cache for')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $result = $this->cloudflareService->clearCache($input->getArgument('domain'));
        if($result->result == 'success') {
            $output->writeln('<info>Successfully cleared cache for domain: '.$input->getArgument('domain').'</info>');
        } else {
            $output->writeln('<error>'.$result->msg.'</error>');
        }
    }

}