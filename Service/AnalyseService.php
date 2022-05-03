<?php

namespace Webidea24\DeleteUnusedCatalogImages\Service;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;

class AnalyseService
{

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Config
     */
    private $eavConfig;

    /**
     * @var ResourceConnection
     */
    private $connection;

    /**
     * @var Filesystem\Driver\File
     */
    private $fileDriver;

    public function __construct(
        Filesystem $filesystem,
        Config $eavConfig,
        ResourceConnection $connection,
        Filesystem\Driver\File $fileDriver
    )
    {
        $this->filesystem = $filesystem;
        $this->eavConfig = $eavConfig;
        $this->connection = $connection;
        $this->fileDriver = $fileDriver;
    }


    private function collectImageFiles(string $relativeDir): array
    {
        $files = [];
        $mediaRead = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
        $dirContent = $mediaRead->read($relativeDir);
        foreach ($dirContent as $item) {
            if (strpos($item, 'cache') !== false || strpos($item, 'placeholder') !== false || strpos($item, 'resized') !== false) {
                continue;
            }

            if ($mediaRead->isDirectory($item)) {
                $files = array_merge($files, $this->collectImageFiles($item));
            } else {
                $files[] = $item;
            }
        }

        return $files;
    }

    public function createFile(): string
    {
        $filePath = $this->createEmptyFile();

        $mediaRead = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);

        $files = $this->collectImageFiles($mediaRead->getRelativePath('catalog/product'));

        $attributeIds = implode(',', $this->getAttributesId());
        $sqlVarchar = "SELECT 1 FROM catalog_product_entity_varchar WHERE attribute_id IN (" . $attributeIds . ") AND `value` = ? LIMIT 1";
        $sqlGallery = "SELECT 1 FROM catalog_product_entity_media_gallery WHERE attribute_id IN (" . $attributeIds . ") AND `value` = ? LIMIT 1";


        $openFile = $this->fileDriver->fileOpen($filePath, 'w');
        $cacheSubDirs = array_merge(['catalog/product/cache'], $mediaRead->read('catalog/product/cache/'));
        try {
            $connection = $this->connection->getConnection();
            foreach ($files as $imagePath) {

                if (0 === count($connection->fetchCol($sqlVarchar, [$imagePath])) &&
                    0 === count($connection->fetchCol($sqlGallery, [$imagePath]))
                ) {
                    fwrite($openFile, $imagePath . "\n");

                    foreach ($cacheSubDirs as $cacheSubDir) {
                        if (!$mediaRead->isDirectory($cacheSubDir)) {
                            continue;
                        }
                        $cachedFile = $cacheSubDir . '/' . str_replace('catalog/product/', '', $imagePath);
                        if ($mediaRead->isExist($cachedFile)) {
                            fwrite($openFile, $cachedFile . "\n");
                        }
                    }
                }
            }
        } finally {
            $this->fileDriver->fileClose($openFile);
        }

        return $filePath;
    }

    private function getAttributesId(): array
    {
        $attributes = [
            'alt_image',
            'image',
            'small_image',
            'swatch_image',
            'thumbnail',
            'media_gallery'
        ]; // TODO load attributes from config

        $attributeIds = [];
        foreach ($attributes as $attributeCode) {
            $attribute = $this->eavConfig->getAttribute(Product::ENTITY, $attributeCode);
            if ($attribute) {
                $attributeIds[] = $attribute->getId();
            }
        }

        return $attributeIds;
    }

    private function createEmptyFile(): string
    {
        $write = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $path = 'delete-unused-images/catalog-' . time() . '.txt';
        $absolutePath = $write->getAbsolutePath($path);

        if ($write->touch($path)) {
            return $absolutePath;
        }

        throw new LocalizedException(__(sprintf('File can not be created: %s', $absolutePath)));
    }

    public function getLastFile(): string
    {
        $read = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR);

        $items = $read->search('catalog-*', 'delete-unused-images');

        if (count($items) === 0) {
            throw new LocalizedException(__('There are no generated files, yet. Please run: bin/magento wi24:unusedCatalogImages:analyse'));
        }

        return $read->getAbsolutePath($items[array_key_last($items)]);
    }
}
