<?php

namespace Doctrine\ODM\MongoDB\Tools\Console\Command;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console;
use Doctrine\ODM\MongoDB\Tools\Console\MetadataFilter;

/**
 * Command to (re)generate the proxy classes used by doctrine.
 *
 * @since   1.0
 */
class GenerateProxiesCommand extends Console\Command\Command
{
    /**
     * @see Console\Command\Command
     */
    protected function configure()
    {
        $this
        ->setName('odm:generate:proxies')
        ->setDescription('Generates proxy classes for document classes.')
        ->setDefinition(array(
            new InputOption(
                'filter', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'A string pattern used to match documents that should be processed.'
            ),
            new InputArgument(
                'dest-path', InputArgument::OPTIONAL,
                'The path to generate your proxy classes. If none is provided, it will attempt to grab from configuration.'
            ),
        ))
        ->setHelp(<<<EOT
Generates proxy classes for document classes.
EOT
        );
    }

    /**
     * @see Console\Command\Command
     */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $dm = $this->getHelper('documentManager')->getDocumentManager();
        
        $metadatas = array_filter($dm->getMetadataFactory()->getAllMetadata(), function (ClassMetadata $classMetadata) {
            return !$classMetadata->isEmbeddedDocument && !$classMetadata->isMappedSuperclass && !$classMetadata->isQueryResultDocument;
        });
        $metadatas = MetadataFilter::filter($metadatas, $input->getOption('filter'));

        // Process destination directory
        if (($destPath = $input->getArgument('dest-path')) === null) {
            $destPath = $dm->getConfiguration()->getProxyDir();
        }

        if ( ! is_dir($destPath)) {
            mkdir($destPath, 0775, true);
        }

        $destPath = realpath($destPath);

        if ( ! file_exists($destPath)) {
            throw new \InvalidArgumentException(
                sprintf("Proxies destination directory '<info>%s</info>' does not exist.", $destPath)
            );
        } elseif ( ! is_writable($destPath)) {
            throw new \InvalidArgumentException(
                sprintf("Proxies destination directory '<info>%s</info>' does not have write permissions.", $destPath)
            );
        }

        if (count($metadatas)) {
            foreach ($metadatas as $metadata) {
                $output->write(
                    sprintf('Processing document "<info>%s</info>"', $metadata->name) . PHP_EOL
                );
            }

            // Generating Proxies
            $dm->getProxyFactory()->generateProxyClasses($metadatas, $destPath);

            // Outputting information message
            $output->write(PHP_EOL . sprintf('Proxy classes generated to "<info>%s</INFO>"', $destPath) . PHP_EOL);
        } else {
            $output->write('No Metadata Classes to process.' . PHP_EOL);
        }
    }
}
