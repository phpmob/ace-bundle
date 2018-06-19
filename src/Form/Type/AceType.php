<?php

declare(strict_types=1);

namespace PhpMob\AceBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AceType extends AbstractType
{
    /**
     * @var bool
     */
    private $enable = true;

    /**
     * @var bool
     */
    private $async = false;

    /**
     * @var bool
     */
    private $autoload = true;

    /**
     * @var string
     */
    private $basePath = 'bundles/phpmobace/';

    /**
     * @var string
     */
    private $jsPath = 'bundles/phpmobace/src-min-noconflict/ace.js';

    /**
     * @param bool|null $enable
     *
     * @return bool
     */
    public function isEnable($enable = null)
    {
        if (null !== $enable) {
            $this->enable = (bool) $enable;
        }

        return $this->enable;
    }

    /**
     * @param bool|null $async
     *
     * @return bool
     */
    public function isAsync($async = null)
    {
        if (null !== $async) {
            $this->async = (bool) $async;
        }

        return $this->async;
    }

    /**
     * @param bool $autoload
     *
     * @return bool
     */
    public function isAutoload($autoload = null)
    {
        if (null !== $autoload) {
            $this->autoload = (bool) $autoload;
        }

        return $this->autoload;
    }

    /**
     * @return string
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * @param string $basePath
     */
    public function setBasePath($basePath)
    {
        $this->basePath = $basePath;
    }

    /**
     * @return string
     */
    public function getJsPath()
    {
        return $this->jsPath;
    }

    /**
     * @param string $jsPath
     */
    public function setJsPath($jsPath)
    {
        $this->jsPath = $jsPath;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setAttribute('enable', $options['enable']);

        if (!$options['enable']) {
            return;
        }

        $builder->setAttribute('async', $options['async']);
        $builder->setAttribute('autoload', $options['autoload']);
        $builder->setAttribute('base_path', $options['base_path']);
        $builder->setAttribute('js_path', $options['js_path']);

        $config = $options['config'];

        if (null === $options['config_name']) {
            $options['config_name'] = uniqid('fos', true);
        }

        $builder->setAttribute('config', $config);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $config = $form->getConfig();
        $view->vars['enable'] = $config->getAttribute('enable');

        if (!$view->vars['enable']) {
            return;
        }

        $view->vars['async'] = $config->getAttribute('async');
        $view->vars['autoload'] = $config->getAttribute('autoload');
        $view->vars['base_path'] = $config->getAttribute('base_path');
        $view->vars['js_path'] = $config->getAttribute('js_path');
        $view->vars['config'] = $config->getAttribute('config');
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'enable' => $this->enable,
                'async' => $this->async,
                'autoload' => $this->autoload,
                'base_path' => $this->basePath,
                'js_path' => $this->jsPath,
                'config' => [],
                'plugins' => [],
                'styles' => [],
                'templates' => [],
            ])
            ->addAllowedTypes('enable', 'bool')
            ->addAllowedTypes('async', 'bool')
            ->addAllowedTypes('autoload', 'bool')
            ->addAllowedTypes('base_path', 'string')
            ->addAllowedTypes('js_path', 'string')
            ->addAllowedTypes('config', 'array')
            ->setNormalizer('base_path', function (Options $options, $value) {
                if ('/' !== substr($value, -1)) {
                    $value .= '/';
                }

                return $value;
            });
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return method_exists(AbstractType::class, 'getBlockPrefix') ? TextareaType::class : 'textarea';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'ace';
    }
}
