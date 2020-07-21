<?php

namespace LDL\Env\Console\Command;

use LDL\Env\Builder\EnvBuilderInterface;
use LDL\Env\Writer\Exception\FileAlreadyExistsException;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use LDL\Console\Helper\ProgressBarFactory;
use LDL\Env\Builder\EnvBuilder;
use LDL\Env\Compiler\Options\EnvCompilerOptions;
use LDL\Env\Finder\Exception\NoFilesFoundException;
use LDL\Env\Finder\Options\EnvFileFinderOptions;
use LDL\Env\Writer\Options\EnvWriterOptions;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BuildCommand extends SymfonyCommand
{
    public const COMMAND_NAME = 'env:build';

    /**
     * @var EnvBuilderInterface
     */
    private $builder;

    public function __construct(
        ?string $name = null,
        EnvBuilderInterface $builder=null
    )
    {
        parent::__construct($name);
        $this->builder = $builder ?? new EnvBuilder();
    }

    public function configure() : void
    {
        $finderDefaults = EnvFileFinderOptions::fromArray([]);
        $compilerDefaults = EnvCompilerOptions::fromArray([]);

        $this->setName(self::COMMAND_NAME)
            ->setDescription('Build compiled .env file')
            ->addArgument(
                'output-file',
                InputArgument::REQUIRED,
                'Name of the output file'
            )
            ->addOption(
                'force-overwrite',
                'f',
                InputOption::VALUE_NONE,
                'Overwrite output file'
            )
            ->addOption(
                'scan-directories',
                'd',
                InputOption::VALUE_OPTIONAL,
                sprintf(
                    'Comma separated list of directories to scan, default: %s',
                    implode(', ', $finderDefaults->getDirectories())
                ),
                implode(',', $finderDefaults->getDirectories())
            )
            ->addOption(
                'scan-files',
                'l',
                InputOption::VALUE_OPTIONAL,
                'Comma separated list of files to scan',
                implode(', ', $finderDefaults->getFiles())
            )
            ->addOption(
                'variable-overwrite',
                'o',
                InputOption::VALUE_NONE,
                'Allow variable overwrite'
            )
            ->addOption(
                'ignore-syntax-error',
                'i',
                InputOption::VALUE_NONE,
                'Ignore syntax error'
            )
            ->addOption(
                'prefix-variable-with-file',
                'p',
                InputOption::VALUE_NONE,
                'Prefix variable with directory name, example: MyProject/.env var TEST becomes MYPROJECT_TEST'
            )
            ->addOption(
                'prefix-variable-depth',
                'e',
                InputOption::VALUE_OPTIONAL,
                'Set directory depth for prefix-variable-with-file',
                $compilerDefaults->getPrefixDepth()
            )
            ->addOption(
                'convert-to-uppercase',
                'u',
                InputOption::VALUE_NONE,
                'Convert variables to uppercase'
            )
            ->addOption(
                'comments-enabled',
                'c',
                InputOption::VALUE_NONE,
                'Adds a comment indicating from which file the env variables defined came from'
            )
            ->addOption(
                'remove-comments',
                'r',
                InputOption::VALUE_NONE,
                'Remove the comments'
            );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->build($input, $output);
            return 0;
        }catch(\Exception $e){
            $output->writeln("<error>{$e->getMessage()}</error>");
            return 1;
        }
    }

    private function build(
        InputInterface $input,
        OutputInterface $output
    ) : void
    {
        $start = hrtime(true);

        try{

            $writerOptions = EnvWriterOptions::fromArray([
                'filename' => $input->getArgument('output-file'),
                'force' => (bool) $input->getOption('force-overwrite')
            ]);

            $finderOptions = EnvFileFinderOptions::fromArray([
                'directories' => explode(',', $input->getOption('scan-directories')),
                'files' => explode(',', $input->getOption('scan-files'))
            ]);

            $compilerOptions = EnvCompilerOptions::fromArray([
                'allowVariableOverwrite' => $input->getOption('variable-overwrite'),
                'ignoreSyntaxErrors' => $input->getOption('ignore-syntax-error'),
                'prefixVariableWithFileName' => $input->getOption('prefix-variable-with-file'),
                'prefixDepth' => $input->getOption('prefix-variable-depth'),
                'convertToUpperCase' => $input->getOption('convert-to-uppercase'),
                'commentsEnabled' => $input->getOption('comments-enabled'),
                'removeComments' => $input->getOption('remove-comments')
            ]);

        }catch(\Exception $e){
            $output->writeln("\n<error>Build failed!</error>\n");
            $output->writeln("\n<error>{$e->getMessage()}</error>\n");

            return;
        }

        try {

            $title = '[ Building compiled env file ]';

            $output->writeln("\n<info>$title</info>\n");

            //$progressBar = ProgressBarFactory::build($output);
            //$progressBar->start();

            $this->builder->build(
                $finderOptions,
                $compilerOptions,
                $writerOptions
            );

            $output->writeln("");

        }catch(NoFilesFoundException $e){

            $output->writeln("\n<error>Build failed!</error>\n");
            $output->writeln("\n<error>{$e->getMessage()}</error>\n");

        }catch(FileAlreadyExistsException $e){

            $output->writeln("\n<error>Build failed!</error>\n");
            $output->writeln("\n<error>{$e->getMessage()}</error>\n");

        }

        //$progressBar->finish();

        $end = hrtime(true);
        $total = round((($end - $start) / 1e+6) / 1000,2);

        $output->writeln("\n<info>Took: $total seconds</info>");
    }

}