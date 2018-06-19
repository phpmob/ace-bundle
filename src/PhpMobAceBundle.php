<?php

declare(strict_types=1);

namespace PhpMob\AceBundle;

use PhpMob\AceBundle\DependencyInjection\PhpMobAceExtension;
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
    protected function getBundlePrefix(): string
    {
        return $this->extension->getAlias();
    }
}
