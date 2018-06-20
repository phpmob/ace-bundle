<?php

/*
 * This file is part of the PhpMob package.
 *
 * (c) Ishmael Doss <nukboon@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PhpMob\AceBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ResourceCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $parameter = 'twig.form.resources';

        if ($container->hasParameter($parameter)) {
            $container->setParameter(
                $parameter,
                array_merge(
                    ['@PhpMobAce/ace_widget.html.twig'],
                    $container->getParameter($parameter)
                )
            );
        }
    }
}
