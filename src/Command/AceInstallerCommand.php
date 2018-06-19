<?php

declare(strict_types=1);

namespace PhpMob\AceBundle\Command;

use PhpMob\AceBundle\Installer\AceInstaller;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class AceInstallerCommand extends Command
{
    /**
     * @var AceInstaller
     */
    private $installer;

    /**
     * @param AceInstaller|null $installer
     */
    public function __construct(AceInstaller $installer = null)
    {
        parent::__construct();

        $this->installer = $installer ?: new AceInstaller();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('ace:install')
            ->setDescription('Install ACE')
            ->addArgument('path', InputArgument::OPTIONAL, 'Where to install ACE')
            ->addOption(
                'release',
                null,
                InputOption::VALUE_OPTIONAL,
                'ACE release (basic, standard or full)'
            )
            ->addOption('tag', null, InputOption::VALUE_OPTIONAL, 'ACE tag (x.y.z or latest)')
            ->addOption(
                'clear',
                null,
                InputOption::VALUE_OPTIONAL,
                'How to clear previous ACE installation (drop, keep or skip)'
            )
            ->addOption(
                'exclude',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                'Path to exclude when extracting ACE'
            )
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</info> command install ACE in your application:

  <info>php %command.full_name%</info>
  
You can install it at a specific path (absolute):

  <info>php %command.full_name% path</info>
  
You can install a specific release (basic, standard or full):

  <info>php %command.full_name% --release=full</info>
  
You can install a specific version:

  <info>php %command.full_name% --tag=4.7.0</info>

If there is a previous ACE installation detected, 
you can control how it should be handled in non-interactive mode:

  <info>php %command.full_name% --clear=drop</info>
  <info>php %command.full_name% --clear=keep</info>
  <info>php %command.full_name% --clear=skip</info>
  
You can exclude path(s) when extracting ACE:

  <info>php %command.full_name% --exclude=samples --exclude=adapters</info>
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->title($output);

        $success = $this->installer->install($this->createOptions($input, $output));

        if ($success) {
            $this->success('ACE has been successfully installed...', $output);
        } else {
            $this->info('ACE installation has been skipped...', $output);
        }
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return mixed[]
     */
    private function createOptions(InputInterface $input, OutputInterface $output)
    {
        $options = ['notifier' => $this->createNotifier($input, $output)];

        if ($input->hasArgument('path')) {
            $options['path'] = $input->getArgument('path');
        }

        if ($input->hasOption('release')) {
            $options['release'] = $input->getOption('release');
        }

        if ($input->hasOption('tag')) {
            $options['version'] = $input->getOption('tag');
        }

        if ($input->hasOption('exclude')) {
            $options['excludes'] = $input->getOption('exclude');
        }

        if ($input->hasOption('clear')) {
            $options['clear'] = $input->getOption('clear');
        }

        return array_filter($options);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return \Closure
     */
    private function createNotifier(InputInterface $input, OutputInterface $output)
    {
        $clear = new ProgressBar($output);
        $download = new ProgressBar($output);
        $extract = new ProgressBar($output);

        return function ($type, $data) use ($input, $output, $clear, $download, $extract) {
            switch ($type) {
                case AceInstaller::NOTIFY_CLEAR:
                    $result = $this->choice(
                        [
                            sprintf('ACE is already installed in "%s"...', $data),
                            '',
                            'What do you want to do?',
                        ],
                        $choices = [
                            AceInstaller::CLEAR_DROP => 'Drop the directory & reinstall ACE',
                            AceInstaller::CLEAR_KEEP => 'Keep the directory & reinstall ACE by overriding files',
                            AceInstaller::CLEAR_SKIP => 'Skip installation',
                        ],
                        AceInstaller::CLEAR_DROP,
                        $input,
                        $output
                    );

                    if (false !== ($key = array_search($result, $choices, true))) {
                        $result = $key;
                    }

                    if (AceInstaller::CLEAR_DROP === $result) {
                        $this->comment(sprintf('Dropping ACE from "%s"', $data), $output);
                    }

                    return $result;

                case AceInstaller::NOTIFY_CLEAR_ARCHIVE:
                    $this->comment(sprintf('Dropping ACE ZIP archive "%s"', $data), $output);

                    break;

                case AceInstaller::NOTIFY_CLEAR_COMPLETE:
                    $this->finishProgressBar($clear, $output);

                    break;

                case AceInstaller::NOTIFY_CLEAR_PROGRESS:
                    $clear->advance();

                    break;

                case AceInstaller::NOTIFY_CLEAR_SIZE:
                    $clear->start($data);

                    break;

                case AceInstaller::NOTIFY_DOWNLOAD:
                    $this->comment(sprintf('Downloading ACE ZIP archive from "%s"', $data), $output);

                    break;

                case AceInstaller::NOTIFY_DOWNLOAD_COMPLETE:
                    $this->finishProgressBar($download, $output);

                    break;

                case AceInstaller::NOTIFY_DOWNLOAD_PROGRESS:
                    $download->advance($data);

                    break;

                case AceInstaller::NOTIFY_DOWNLOAD_SIZE:
                    $download->start($data);

                    break;

                case AceInstaller::NOTIFY_EXTRACT:
                    $this->comment(sprintf('Extracting ACE ZIP archive to "%s"', $data), $output);

                    break;

                case AceInstaller::NOTIFY_EXTRACT_COMPLETE:
                    $this->finishProgressBar($extract, $output);

                    break;

                case AceInstaller::NOTIFY_EXTRACT_PROGRESS:
                    $extract->advance();

                    break;

                case AceInstaller::NOTIFY_EXTRACT_SIZE:
                    $extract->start($data);

                    break;
            }
        };
    }

    /**
     * @param OutputInterface $output
     */
    private function title(OutputInterface $output)
    {
        $output->writeln(
            [
                '----------------------',
                '| ACE Installer |',
                '----------------------',
                '',
            ]
        );
    }

    /**
     * @param string|string[] $message
     * @param OutputInterface $output
     */
    private function comment($message, OutputInterface $output)
    {
        $output->writeln(' // '.$message);
        $output->writeln('');
    }

    /**
     * @param string          $message
     * @param OutputInterface $output
     */
    private function success($message, OutputInterface $output)
    {
        $this->block('[OK] - '.$message, $output, 'green', 'black');
    }

    /**
     * @param string          $message
     * @param OutputInterface $output
     */
    private function info($message, OutputInterface $output)
    {
        $this->block('[INFO] - '.$message, $output, 'yellow', 'black');
    }

    /**
     * @param string          $message
     * @param OutputInterface $output
     * @param string          $background
     * @param string          $font
     */
    private function block($message, OutputInterface $output, $background = null, $font = null)
    {
        $options = [];

        if (null !== $background) {
            $options[] = 'bg='.$background;
        }

        if (null !== $font) {
            $options[] = 'fg='.$font;
        }

        $pattern = ' %s ';

        if (!empty($options)) {
            $pattern = '<'.implode(';', $options).'>'.$pattern.'</>';
        }

        $output->writeln($block = sprintf($pattern, str_repeat(' ', strlen($message))));
        $output->writeln(sprintf($pattern, $message));
        $output->writeln($block);
    }

    /**
     * @param string|string[] $question
     * @param string[]        $choices
     * @param string          $default
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return string|null
     */
    private function choice($question, array $choices, $default, InputInterface $input, OutputInterface $output)
    {
        $helper = new QuestionHelper();

        if (is_array($question)) {
            $question = implode("\n", $question);
        }

        $result = $helper->ask(
            $input,
            $output,
            new ChoiceQuestion($question, $choices, $default)
        );

        $output->writeln('');

        return $result;
    }

    /**
     * @param ProgressBar     $progress
     * @param OutputInterface $output
     */
    private function finishProgressBar($progress, OutputInterface $output)
    {
        $progress->finish();
        $output->writeln(['', '']);
    }
}
