<?php

namespace Shulgin\LessProcessor\Css\PreProcessor\Adapter\Less;

use Psr\Log\LoggerInterface;
use Magento\Framework\Phrase;
use Magento\Framework\App\State;
use Magento\Framework\View\Asset\File;
use Magento\Framework\View\Asset\Source;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Css\PreProcessor\File\Temporary;
use Magento\Framework\View\Asset\ContentProcessorException;
use Magento\Framework\View\Asset\ContentProcessorInterface;

/**
 * Class Processor
 */
class Processor implements ContentProcessorInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var State
     */
    private $appState;

    /**
     * @var Source
     */
    private $assetSource;

    /**
     * @var Temporary
     */
    private $temporaryFile;

    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     * @param State $appState
     * @param Source $assetSource
     * @param Temporary $temporaryFile
     */
    public function __construct(
        LoggerInterface $logger,
        State $appState,
        Source $assetSource,
        Temporary $temporaryFile
    ) {
        $this->logger = $logger;
        $this->appState = $appState;
        $this->assetSource = $assetSource;
        $this->temporaryFile = $temporaryFile;
    }

    /**
     * @inheritdoc
     */
    public function processContent(File $asset)
    {
        $path = $asset->getPath();
        try {

            $content = $this->assetSource->getContent($asset);

            if (trim($content) === '') {
                throw new ContentProcessorException(
                    new Phrase('Compilation from source: LESS file is empty: ' . $path)
                );
            }

            $tmpFilePath = $this->temporaryFile->createFile($path, $content);
            //$tmpMapFilePath = $this->temporaryFile->createFile(str_replace('.less','.map',  $path) . ".less", '');

            $parser = new \Less_Parser(
                [
                    'sourceMap'         => true,
                    'relativeUrls'      => false,
                    //'sourceMapWriteTo'  => $tmpMapFilePath,
                    //'sourceMapURL'      => $tmpMapFilePath,
                    'compress'          => $this->appState->getMode() !== State::MODE_DEVELOPER
                ]
            );

            gc_disable();
            //$parser->SetOption('sourceMapWriteTo', $tmpFilePath);
            //$parser->SetOption('sourceMapURL', $tmpFilePath. ".map");
            $parser->parseFile($tmpFilePath, '');
            $content = $parser->getCss();
            gc_enable();

            if (trim($content) === '') {
                throw new ContentProcessorException(
                    new Phrase('Compilation from source: LESS file is empty: ' . $path)
                );
            } else {
                return $content;
            }
        } catch (\Exception $e) {
            throw new ContentProcessorException(new Phrase($e->getMessage()));
        }
    }
}
