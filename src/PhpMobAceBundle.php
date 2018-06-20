<?php

declare(strict_types=1);

namespace PhpMob\AceBundle;

use PhpMob\AceBundle\DependencyInjection\Compiler\ResourceCompilerPass;
use PhpMob\AceBundle\DependencyInjection\PhpMobAceExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class PhpMobAceBundle extends Bundle
{
    public function __construct()
    {
        $this->extension = new PhpMobAceExtension();
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ResourceCompilerPass());
    }

    /**
     * {@inheritdoc}
     */
    protected function getBundlePrefix(): string
    {
        return $this->extension->getAlias();
    }
}
