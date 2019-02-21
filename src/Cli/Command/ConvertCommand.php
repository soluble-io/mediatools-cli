<?php

declare(strict_types=1);

/**
 * @see       https://github.com/soluble-io/soluble-mediatools-cli for the canonical repository
 * @copyright Copyright (c) 2018-2019 Sébastien Vanvelthem. (https://github.com/belgattitude)
 * @license   https://github.com/soluble-io/soluble-mediatools-cli/blob/master/LICENSE.md MIT
 */

namespace Soluble\MediaTools\Cli\Command;

use Soluble\MediaTools\Common\IO\PlatformNullFile;
use Soluble\MediaTools\Video\Filter\Hqdn3DVideoFilter;
use Soluble\MediaTools\Video\Filter\VideoFilterChain;
use Soluble\MediaTools\Video\Filter\YadifVideoFilter;
use Soluble\MediaTools\Video\VideoAnalyzerInterface;
use Soluble\MediaTools\Video\VideoConverterInterface;
use Soluble\MediaTools\Video\VideoConvertParams;
use Soluble\MediaTools\Video\VideoConvertParamsInterface;
use Soluble\MediaTools\Video\VideoInfoReaderInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class ConvertCommand extends Command
{
    /** @var VideoInfoReaderInterface */
    protected $videoInfoReader;

    /** @var VideoAnalyzerInterface */
    protected $videoAnalyzer;

    /** @var VideoConverterInterface */
    protected $videoConverter;

    /** @var string[] */
    protected $supportedVideoExtensions = [
        'mov',
        'mp4',
        'mkv',
        'flv',
        'webm',
    ];

    public function __construct(VideoInfoReaderInterface $videoInfoReader, VideoAnalyzerInterface $videoAnalyzer, VideoConverterInterface $videoConverter)
    {
        $this->videoInfoReader = $videoInfoReader;
        $this->videoAnalyzer   = $videoAnalyzer;
        $this->videoConverter  = $videoConverter;
        parent::__construct();
    }

    /**
     * Configures the command.
     */
    protected function configure(): void
    {
        $this
            ->setName('transcode:videos')
            ->setDescription('Generate mp4/vp9 videos from directory')
            ->setDefinition(
                new InputDefinition([
                    new InputOption('dir', 'd', InputOption::VALUE_REQUIRED),
                ])
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$input->hasOption('dir')) {
            throw new \Exception('Missing dir argument, use <command> <dir>');
        }
        $videoPath = $input->hasOption('dir') ? $input->getOption('dir') : '';
        if (!is_string($videoPath) || !is_dir($videoPath)) {
            throw new \Exception(sprintf(
                'Video dir %s does not exists',
                is_string($videoPath) ? $videoPath : ''
            ));
        }

        $convertVP9  = true;
        $convertH264 = true;

        $output->writeln('Getting information');

        // Get the videos in path

        $videos = $this->getVideoFiles($videoPath);

        $progressBar = new ProgressBar($output, count($videos));
        $progressBar->start();

        $outputPath = $videoPath . '/../latest_conversion';
        if (!is_dir($outputPath)) {
            throw new \Exception('Output path does not exists');
        }

        $rows = [];

        /** @var \SplFileInfo $video */
        foreach ($videos as $video) {
            $videoFile = $video->getPathname();

            $info = $this->videoInfoReader->getInfo($videoFile);

            $interlaceGuess = $this->videoAnalyzer->detectInterlacement(
                $videoFile,
                // Max frames to analyze must be big !!!
                // There's a lot of videos satrting with black
                2000
            );

            $interlaceMode = $interlaceGuess->isInterlacedBff(0.4) ? 'BFF' :
                ($interlaceGuess->isInterlacedTff(0.4) ? 'TFF' : '');

            $vStream = $info->getVideoStreams()->getFirst();

            $pixFmt = $vStream->getPixFmt();

            $rows[] = [
                $video->getBasename(),
                sprintf('%sx%s', $vStream->getWidth(), $vStream->getHeight()),
                $info->getDuration(),
                $vStream->getBitRate(),
                $vStream->getCodecName(),
                $pixFmt,
                $interlaceMode,
                filesize($videoFile),
            ];

            $extraParams = new VideoConvertParams();
            if ($pixFmt !== 'yuv420p') {
                $extraParams = $extraParams->withPixFmt('yuv420p');
            }
            if ($interlaceMode !== '') {
                $extraParams = $extraParams->withVideoFilter(
                    new VideoFilterChain([
                        new YadifVideoFilter(),
                        new Hqdn3DVideoFilter(),
                    ])
                );
            } else {
                new VideoFilterChain([
                    new Hqdn3DVideoFilter(),
                ]);
            }

            // VP9 conversion
            $vp9Output = sprintf(
                '%s/%s%s',
                $outputPath,
                basename($videoFile, pathinfo($videoFile, PATHINFO_EXTENSION)),
                'webm'
            );

            if ($convertVP9 && !file_exists($vp9Output)) {
                $this->convertVP9SinglePass(
                    $videoFile,
                    $vp9Output,
                    $extraParams
                );
                // to allow laptop to cool down
                sleep(60);
            }

            // H264 conversion
            $h264Output = sprintf(
                '%s/%s%s',
                $outputPath,
                basename($videoFile, pathinfo($videoFile, PATHINFO_EXTENSION)),
                'mp4'
            );

            if ($convertH264 && !file_exists($h264Output)) {
                $this->convertH264(
                    $videoFile,
                    $h264Output,
                    $extraParams
                );
                sleep(60);
            }

            $progressBar->advance();
        }

        $output->writeln('');

        $table = new Table($output);
        $table->setHeaders([
            'file',
            'size',
            'duration',
            'bitrate',
            'codec',
            'fmt',
            'interlace',
            'filesize',
        ]);
        $table->setRows($rows ?? []);
        $table->render();

        $output->writeln("\nFinished");

        return 1;
    }

    /**
     * @param string $videoPath
     *
     * @return array<\SplFileInfo>
     */
    public function getVideoFiles(string $videoPath): array
    {
        $files = (new Finder())->files()
            ->in($videoPath)
            ->name(sprintf(
                '/\.(%s)$/',
                implode('|', $this->supportedVideoExtensions)
            ));

        $videos = [];

        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            // original files must not be converted, an mkv have been
            // provided
            if (preg_match('/\.original\./', $file->getPathname()) !== 1) {
                continue;
            }

            $videos[] = $file;
        }

        return $videos;
    }

    public function convertH264(string $input, string $output, VideoConvertParamsInterface $extraParams): void
    {
        $params = $this->getH264PresetParams(4);
        $params = $params->withConvertParams($extraParams);

        $tmpOutput = $output . '.tmp';

        $this->videoConverter->convert(
            $input,
            $tmpOutput,
            $params
        );

        if (!file_exists($tmpOutput)) {
            throw new \Exception(sprintf(
                'Temp file %s does not exists',
                $tmpOutput
            ));
        }

        rename($tmpOutput, $output);
    }

    public function getH264PresetParams(int $threads): VideoConvertParams
    {
        return (new VideoConvertParams())
            ->withVideoCodec('h264')
            ->withAudioCodec('aac')
            ->withAudioBitrate('128k')
            ->withPreset('medium')
            ->withStreamable(true)
            ->withCrf(24)
            ->withThreads($threads)
            ->withOutputFormat('mp4');
    }

    public function convertVP9SinglePass(string $input, string $output, VideoConvertParamsInterface $extraParams): void
    {
        $params = (new VideoConvertParams())
            ->withVideoCodec('libvpx-vp9')
            ->withVideoBitrate('850k')
            ->withVideoMinBitrate('400k')
            ->withVideoMaxBitrate('1200k')
            ->withQuality('good')
            ->withCrf(32)
            ->withThreads(8)
            ->withKeyframeSpacing(240)
            ->withTileColumns(2)
            ->withFrameParallel(1)
            ->withOutputFormat('webm')
            ->withConvertParams($extraParams)
            ->withSpeed(1)
            ->withAudioCodec('libopus')
            ->withAudioBitrate('128k');

        $tmpOutput = $output . '.tmp';

        $this->videoConverter->convert(
            $input,
            $tmpOutput,
            $params
        );

        if (!file_exists($tmpOutput)) {
            throw new \Exception(sprintf(
                'Temp file %s does not exists',
                $tmpOutput
            ));
        }

        rename($tmpOutput, $output);
    }

    public function convertVP9Multipass(string $input, string $output, VideoConvertParamsInterface $extraParams): void
    {
        $logFile = tempnam(sys_get_temp_dir(), 'ffmpeg-log');

        $firstPassParams = (new VideoConvertParams())
            // VIDEO FILTERS MUST BE DONE BEFORE
            // CODEC SELECTION
            ->withConvertParams($extraParams)
            ->withVideoCodec('libvpx-vp9')
            ->withVideoBitrate('1024k')
            ->withVideoMinBitrate('512k')
            ->withVideoMaxBitrate('1485k')
            ->withQuality('good')
            ->withCrf(32)
            ->withThreads(8)
            ->withKeyframeSpacing(240)
            ->withTileColumns(2)
            ->withFrameParallel(1)
            ->withOutputFormat('webm')
            ->withSpeed(4)
            ->withPass(1)
            ->withPassLogFile(is_string($logFile) ? $logFile : '/tmp/ffmpeg-log');

        try {
            $pass1Process = $this->videoConverter->getSymfonyProcess(
                $input,
                new PlatformNullFile(),
                // We don't need audio (speedup)
                $firstPassParams->withNoAudio()
            );
            //var_dump($pass1Process->getCommandLine());
            //die();
            $pass1Process->mustRun();
        } catch (\Throwable $e) {
            if (is_string($logFile) && file_exists($logFile)) {
                unlink($logFile);
            }
            throw $e;
        }

        $secondPassParams = $firstPassParams
            ->withConvertParams($extraParams)
            ->withSpeed(2)
            ->withPass(2)
            ->withAudioCodec('libopus')
            ->withAudioBitrate('128k')
            ->withAutoAltRef(1)
            ->withLagInFrames(25);

        $tmpOutput = $output . '.tmp';

        $pass2Process = $this->videoConverter->getSymfonyProcess(
            $input,
            $tmpOutput,
            $secondPassParams
        );

        //var_dump($pass2Process->getCommandLine());
        $pass2Process->mustRun();

        if (!file_exists($tmpOutput)) {
            throw new \Exception(sprintf(
                'Temp file %s does not exists',
                $tmpOutput
            ));
        }

        rename($tmpOutput, $output);
    }
}
