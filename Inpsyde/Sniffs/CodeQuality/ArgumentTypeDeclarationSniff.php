<?php

/*
 * This file is part of the php-coding-standards package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Inpsyde\Sniffs\CodeQuality;

use Inpsyde\PhpcsHelpers;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

class ArgumentTypeDeclarationSniff implements Sniff
{
    const TYPE_CODES = [
        T_STRING,
        T_ARRAY_HINT,
        T_CALLABLE,
        T_SELF,
    ];

    const METHODS_WHITELIST = [
        'unserialize',
        'seek',
    ];

    /**
     * @return array<int|string>
     *
     * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration
     */
    public function register()
    {
        // phpcs:enable Inpsyde.CodeQuality.ReturnTypeDeclaration

        return [T_FUNCTION, T_CLOSURE];
    }

    /**
     * @param File $file
     * @param int $position
     * @return void
     *
     * phpcs:disable Inpsyde.CodeQuality
     */
    public function process(File $file, $position)
    {
        // phpcs:enable Inpsyde.CodeQuality

        if (
            PhpcsHelpers::functionIsArrayAccess($file, $position)
            || PhpcsHelpers::isHookClosure($file, $position)
            || PhpcsHelpers::isHookFunction($file, $position)
            || (
                PhpcsHelpers::functionIsMethod($file, $position)
                && in_array($file->getDeclarationName($position), self::METHODS_WHITELIST, true)
            )
        ) {
            return;
        }

        /** @var array<int, array<string, mixed>> $tokens */
        $tokens = $file->getTokens();
        $paramsStart = (int)($tokens[$position]['parenthesis_opener'] ?? 0);
        $paramsEnd = (int)($tokens[$position]['parenthesis_closer'] ?? 0);

        if (!$paramsStart || !$paramsEnd || $paramsStart >= ($paramsEnd - 1)) {
            return;
        }

        $variables = PhpcsHelpers::filterTokensByType($paramsStart, $paramsEnd, $file, T_VARIABLE);

        foreach (array_keys($variables) as $varPosition) {
            $typePosition = $file->findPrevious(
                [T_WHITESPACE, T_ELLIPSIS, T_BITWISE_AND],
                $varPosition - 1,
                $paramsStart + 1,
                true
            );

            $type = $tokens[$typePosition] ?? null;
            /** @psalm-suppress MixedArgument */
            if ($type && !in_array($type['code'], self::TYPE_CODES, true)) {
                $file->addWarning('Argument type is missing', $position, 'NoArgumentType');
            }
        }
    }
}
