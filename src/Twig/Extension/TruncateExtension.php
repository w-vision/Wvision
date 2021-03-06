<?php
/**
 * w-vision
 *
 * LICENSE
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that is distributed with this source code.
 *
 * @copyright  Copyright (c) 2018 w-vision AG (https://www.w-vision.ch)
 */

namespace WvisionBundle\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TruncateExtension extends AbstractExtension
{
    /**
     * Returns a list of filters to add to the existing list.
     *
     * @return array An array of filters
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter(
                'truncate', [$this, 'truncate'], [
                    'is_safe' => ['html']
                ]
            )
        ];
    }

    /**
     * Cut string to specific length.
     *
     * @param string $str The string to cut.
     * @param int $limit The maximum number of characters that must be returned.
     * @param string $brChar The character to use for breaking the string.
     * @param string $pad The string to use at the end of the cut string.
     *
     * @return string The cut string
     */
    public function truncate($str, $limit = 100, $brChar = '', $pad = ' ...'): string
    {
        if (strlen($str) <= $limit) {
            return $str;
        }

        if ($brChar === '') {
            return substr($str, 0, $limit) . $pad;
        }

        return substr($str, 0, strpos(substr($str, 0, $limit), $brChar)) . $pad;
    }
}
