<?php

declare(strict_types=1);

namespace Zp\Supple\Migration;

use Jfcherng\Diff\Differ;
use Jfcherng\Diff\Renderer\Text\AbstractText;
use Jfcherng\Diff\SequenceMatcher;

class MigrationDiffRenderer extends AbstractText
{
    public const INFO = [
        'desc' => 'Unified',
        'type' => 'Text',
    ];

    protected function renderWorker(Differ $differ): string
    {
        $ret = '';
        /** @var array<int> $hunk */
        foreach ($differ->getGroupedOpcodesGnu() as $hunk) {
            $ret .= $this->renderHunkBlocks($differ, $hunk);
        }
        return $ret;
    }

    /**
     * Render the hunk content.
     *
     * @param Differ $differ the differ
     * @param array<int> $hunk the hunk
     * @return string
     */
    protected function renderHunkBlocks(Differ $differ, array $hunk): string
    {
        $ret = '';

        $oldNoEolAtEofIdx = $differ->getOldNoEolAtEofIdx();
        $newNoEolAtEofIdx = $differ->getNewNoEolAtEofIdx();

        foreach ($hunk as [$op, $i1, $i2, $j1, $j2]) {
            // note that although we are in a OP_EQ situation,
            // the old and the new may not be exactly the same
            // because of ignoreCase, ignoreWhitespace, etc
            if ($op === SequenceMatcher::OP_EQ) {
                // we could only pick either the old or the new to show
                // note that the GNU diff will use the old one because it creates a patch
                $ret .= $this->renderContext(
                    ' ',
                    $differ->getOld($i1, $i2),
                    $i2 === $oldNoEolAtEofIdx
                );

                continue;
            }

            if ($op & (SequenceMatcher::OP_REP | SequenceMatcher::OP_DEL)) {
                $ret .= $this->renderContext(
                    '-',
                    $differ->getOld($i1, $i2),
                    $i2 === $oldNoEolAtEofIdx
                );
            }

            if ($op & (SequenceMatcher::OP_REP | SequenceMatcher::OP_INS)) {
                $ret .= $this->renderContext(
                    '+',
                    $differ->getNew($j1, $j2),
                    $j2 === $newNoEolAtEofIdx
                );
            }
        }

        return $ret;
    }

    /**
     * Render the context array with the symbol.
     *
     * @param string $symbol the symbol
     * @param array<string> $context the context
     * @param bool $noEolAtEof there is no EOL at EOF in this block
     * @return string
     */
    protected function renderContext(string $symbol, array $context, bool $noEolAtEof = false): string
    {
        if (empty($context)) {
            return '';
        }

        $ret = $symbol . \implode("\n{$symbol}", $context) . "\n";
        $ret = $this->cliColoredString($ret, $symbol);

        return $ret;
    }
}
