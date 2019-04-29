<?php

namespace App\Command;

use GuzzleHttp\ClientInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZipArchive;

class NewCommand extends Command
{
    /**
     * @var ClientInterface
     */
    private $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('new')
            ->setDescription('Install new laravel application')
            ->addArgument('name', InputArgument::REQUIRED)
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $directory = getcwd() . '/' . $input->getArgument('name');

        $this->assertApplicationDoesNotExist($directory, $output);

        $output->writeln('<info>Crafting application...</info>');

        $this->download($zipFile = $this->makeFileName())
            ->extract($zipFile, $directory)
            ->cleanUp($zipFile)
        ;

        $output->writeln('<comment>Application ready!</comment>');
    }

    private function assertApplicationDoesNotExist($directory, OutputInterface $output)
    {
        if(is_dir($directory)) {
            $output->writeln('<error>Application already exists</error>');
            exit(1);
        }
    }

    private function download($zipFile)
    {
        $response = $this->client->get('http://cabinet.laravel.com/latest.zip')
            ->getBody();

        file_put_contents($zipFile, $response);

        return $this;
    }

    private function extract($zipFile, $directory)
    {
        $archive = new ZipArchive;

        $archive->open($zipFile);
        $archive->extractTo($directory);
        $archive->close();

        return $this;
    }

    private function makeFileName()
    {
        return getcwd() . '/laravel_' . md5(time() . uniqid()) . '.zip';
    }

    private function cleanUp($zipFile)
    {
        @chmod($zipFile, 0777);
        @unlink($zipFile);

        return $this;
    }
}