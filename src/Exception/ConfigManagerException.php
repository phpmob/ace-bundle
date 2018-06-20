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

namespace PhpMob\AceBundle\Exception;

class ConfigManagerException extends \Exception
{
    /**
     * @param string $name
     *
     * @return ConfigManagerException
     */
    public static function configDoesNotExist($name)
    {
        return new static(sprintf('The CKEditor config "%s" does not exist.', $name));
    }
}
