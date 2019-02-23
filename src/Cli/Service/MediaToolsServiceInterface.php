<?php

declare(strict_types=1);

/**
 * @see       https://github.com/soluble-io/soluble-mediatools-cli for the canonical repository
 *
 * @copyright Copyright (c) 2018-2019 Sébastien Vanvelthem. (https://github.com/belgattitude)
 * @license   https://github.com/soluble-io/soluble-mediatools-cli/blob/master/LICENSE.md MIT
 */

namespace Soluble\MediaTools\Cli\Service;

use Soluble\MediaTools\Video\VideoAnalyzerInterface;
use Soluble\MediaTools\Video\VideoConverterInterface;
use Soluble\MediaTools\Video\VideoInfoReaderInterface;
use Soluble\MediaTools\Video\VideoThumbGeneratorInterface;

interface MediaToolsServiceInterface
{
    public function getReader(): VideoInfoReaderInterface;

    public function getAnalyzer(): VideoAnalyzerInterface;

    public function getConverter(): VideoConverterInterface;

    public function getThumbGenerator(): VideoThumbGeneratorInterface;
}
