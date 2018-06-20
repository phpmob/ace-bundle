<?php

declare(strict_types=1);

namespace PhpMob\AceBundle\Installer;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AceInstaller
{
    const VERSION_LATEST = '1.3.3';

    const CLEAR_DROP = 'drop';

    const CLEAR_KEEP = 'keep';

    const CLEAR_SKIP = 'skip';

    const NOTIFY_CLEAR = 'clear';

    const NOTIFY_CLEAR_ARCHIVE = 'clear-archive';

    const NOTIFY_CLEAR_COMPLETE = 'clear-complete';

    const NOTIFY_CLEAR_PROGRESS = 'clear-progress';

    const NOTIFY_CLEAR_QUESTION = 'clear-question';

    const NOTIFY_CLEAR_SIZE = 'clear-size';

    const NOTIFY_DOWNLOAD = 'download';

    const NOTIFY_DOWNLOAD_COMPLETE = 'download-complete';

    const NOTIFY_DOWNLOAD_PROGRESS = 'download-progress';

    const NOTIFY_DOWNLOAD_SIZE = 'download-size';

    const NOTIFY_EXTRACT = 'extract';

    const NOTIFY_EXTRACT_COMPLETE = 'extract-complete';

    const NOTIFY_EXTRACT_PROGRESS = 'extract-progress';

    const NOTIFY_EXTRACT_SIZE = 'extract-size';

    /**
     * @var string
     */
    private static $archive = 'https://github.com/ajaxorg/ace-builds/archive/v%s.zip';

    /**
     * @var OptionsResolver
     */
    private $resolver;

    /**
     * @param mixed[] $options
     */
    public function __construct(array $options = [])
    {
        $this->resolver = (new OptionsResolver())
            ->setDefaults(array_merge([
                'clear' => null,
                'excludes' => [],
                'notifier' => null,
                'path' => dirname(__DIR__) . '/Resources/public',
                'version' => self::VERSION_LATEST,
            ], $options))
            ->setAllowedTypes('excludes', 'array')
            ->setAllowedTypes('notifier', ['null', 'callable'])
            ->setAllowedTypes('path', 'string')
            ->setAllowedTypes('version', 'string')
            ->setAllowedValues('clear', [self::CLEAR_DROP, self::CLEAR_KEEP, self::CLEAR_SKIP, null])
            ->setNormalizer('path', function (Options $options, $path) {
                return rtrim($path, '/');
            });
    }

    /**
     * @param mixed[] $options
     *
     * @return bool
     */
    public function install(array $options = [])
    {
        $options = $this->resolver->resolve($options);

        if (self::CLEAR_SKIP === $this->clear($options)) {
            return false;
        }

        $this->extract($this->download($options), $options);

        return true;
    }

    /**
     * @param mixed[] $options
     *
     * @return int
     */
    private function clear(array $options)
    {
        if (!file_exists($options['path'] . '/acemin/ace.js')) {
            return self::CLEAR_DROP;
        }

        if (null === $options['clear'] && null !== $options['notifier']) {
            $options['clear'] = $this->notify($options['notifier'], self::NOTIFY_CLEAR, $options['path']);
        }

        if (null === $options['clear']) {
            $options['clear'] = self::CLEAR_SKIP;
        }

        if (self::CLEAR_DROP === $options['clear']) {
            $this->removeDir($options['path']);
            $this->notify($options['notifier'], self::NOTIFY_CLEAR_COMPLETE);
        }

        return $options['clear'];
    }

    /**
     * @param mixed[] $options
     *
     * @return string
     */
    private function download(array $options)
    {
        $url = sprintf(self::$archive, $options['version']);
        $this->notify($options['notifier'], self::NOTIFY_DOWNLOAD, $url);

        $zip = @file_get_contents($url, false, $this->createStreamContext($options['notifier']));

        if (false === $zip) {
            throw $this->createException(sprintf('Unable to download ACE ZIP archive from "%s".', $url));
        }

        $path = tempnam(sys_get_temp_dir(), 'ace-' . $options['version'] . '.zip');

        if (!@file_put_contents($path, $zip)) {
            throw $this->createException(sprintf('Unable to write ACE ZIP archive to "%s".', $path));
        }

        $this->notify($options['notifier'], self::NOTIFY_DOWNLOAD_COMPLETE, $path);

        return $path;
    }

    /**
     * @param callable|null $notifier
     *
     * @return resource
     */
    private function createStreamContext(callable $notifier = null)
    {
        $context = [];
        $proxy = getenv('https_proxy') ?: getenv('http_proxy');

        if ($proxy) {
            $context['proxy'] = $proxy;
            $context['request_fulluri'] = (bool)getenv('https_proxy_request_fulluri') ?:
                getenv('http_proxy_request_fulluri');
        }

        return stream_context_create($context, [
            'notification' => function (
                $code,
                $severity,
                $message,
                $messageCode,
                $transferred,
                $size
            ) use ($notifier) {
                if (null === $notifier) {
                    return;
                }

                switch ($code) {
                    case STREAM_NOTIFY_FILE_SIZE_IS:
                        $this->notify($notifier, self::NOTIFY_DOWNLOAD_SIZE, $size);

                        break;

                    case STREAM_NOTIFY_PROGRESS:
                        $this->notify($notifier, self::NOTIFY_DOWNLOAD_PROGRESS, $transferred);

                        break;
                }
            },
        ]);
    }

    /**
     * @param string $path
     * @param mixed[] $options
     */
    private function extract($path, array $options)
    {
        $this->notify($options['notifier'], self::NOTIFY_EXTRACT, $options['path']);

        $zip = new \ZipArchive();
        $zip->open($path);

        $this->notify($options['notifier'], self::NOTIFY_EXTRACT_SIZE, $zip->numFiles);

        $zip->extractTo($options['path']);

        $src = sprintf('%s/ace-builds-%s/src-min-noconflict', $options['path'], $options['version']);
        $this->copyDir($src, $options['path'] . '/acemin');
        $this->removeDir(sprintf('%s/ace-builds-%s', $options['path'], $options['version']));
        $zip->close();

        $this->notify($options['notifier'], self::NOTIFY_EXTRACT_COMPLETE);
        $this->notify($options['notifier'], self::NOTIFY_CLEAR_ARCHIVE, $path);

        if (!@unlink($path)) {
            throw $this->createException(sprintf('Unable to remove the ACE ZIP archive "%s".', $path));
        }
    }

    /**
     * @param string $src
     * @param string $dst
     */
    private function copyDir(string $src, string $dst)
    {
        if (is_dir($src)) {
            if (!@mkdir($dst)) {
                throw $this->createException(sprintf('Unable to make dir for "%s".', $dst));
            }

            $files = scandir($src);

            foreach ($files as $file) {
                if ($file != "." && $file != "..") {
                    $this->copyDir("$src/$file", "$dst/$file");
                }
            }
        } else if (file_exists($src)) {
            if (!@copy($src, $dst)) {
                throw $this->createException(sprintf('Unable to copy "%s" to "%s".', $src, $dst));
            }
        }
    }

    /**
     * @param string $path
     */
    private function removeDir(string $path)
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            $filePath = $file->getRealPath();

            if ($dir = $file->isDir()) {
                $success = @rmdir($filePath);
            } else {
                $success = @unlink($filePath);
            }

            if (!$success) {
                throw $this->createException(sprintf(
                    'Unable to remove the %s "%s".',
                    $dir ? 'directory' : 'file',
                    $filePath
                ));
            }
        }
    }

    /**
     * @param callable|null $notifier
     * @param string $type
     * @param mixed $data
     *
     * @return mixed
     */
    private function notify(callable $notifier = null, $type, $data = null)
    {
        if (null !== $notifier) {
            return $notifier($type, $data);
        }
    }

    /**
     * @param string $message
     *
     * @return \RuntimeException
     */
    private function createException($message)
    {
        $error = error_get_last();

        if (isset($error['message'])) {
            $message .= sprintf(' (%s)', $error['message']);
        }

        return new \RuntimeException($message);
    }
}
