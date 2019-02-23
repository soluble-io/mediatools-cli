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

class MediaToolsService implements MediaToolsServiceInterface
{
    /** @var VideoInfoReaderInterface */
    private $reader;

    /** @var VideoConverterInterface */
    private $converter;

    /** @var VideoAnalyzerInterface */
    private $analyzer;

    /** @var VideoThumbGeneratorInterface */
    private $thumbGenerator;

    public function __construct(
        VideoInfoReaderInterface $reader,
        VideoConverterInterface $converter,
        VideoThumbGeneratorInterface $thumbGenerator,
        VideoAnalyzerInterface $analyzer
    ) {
        $this->converter      = $converter;
        $this->reader         = $reader;
        $this->analyzer       = $analyzer;
        $this->thumbGenerator = $thumbGenerator;
    }

    public function getReader(): VideoInfoReaderInterface
    {
        return $this->reader;
    }

    public function getAnalyzer(): VideoAnalyzerInterface
    {
        return $this->analyzer;
    }

    public function getConverter(): VideoConverterInterface
    {
        return $this->converter;
    }

    public function getThumbGenerator(): VideoThumbGeneratorInterface
    {
        return $this->thumbGenerator;
    }
}
