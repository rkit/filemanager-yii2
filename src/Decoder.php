<?php

/**
 * @link https://github.com/rkit/filemanager-yii2
 * @copyright Copyright (c) 2015 Igor Romanov
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace rkit\filemanager;

use Yii;
use yii\helpers\FileHelper;
use yii\base\InvalidValueException;
use rkit\filemanager\models\File;

/**
 * File Manager
 *
 * @author Igor Romanov <rkit.ru@gmail.com>
 * @since 1.0
 */
class Decoder
{
    /**
     * Create file from uploader (UploadedFile)
     *
     * @param UploadedFile $data
     * @param int $ownerId
     * @param int $ownerType
     * @param bool $saveAfterUpload Save the file immediately after upload
     * @param bool $protected File is protected?
     * @return \rkit\filemanager\models\File|bool
     */
    public function createFromUploader(
        $data,
        $ownerId = -1,
        $ownerType = -1,
        $saveAfterUpload = false,
        $protected = false
    ) {
        $pathInfo = pathinfo($data->name);
        $file = new File([
            'tmp' => true,
            'owner_id' => $ownerId,
            'owner_type' => $ownerType,
            'size' => $data->size,
            'mime' => $data->type,
            'title' => $pathInfo['filename'],
            'name' => File::generateName($pathInfo['extension']),
            'protected' => $protected
        ]);

        return $file->saveToTmp($data->tempName, $saveAfterUpload);
    }
    /**
     * Create file from path
     *
     * @param string $path Path in filesystem or URL
     * @param int $ownerId
     * @param int $ownerType
     * @param bool $saveAfterUpload Save the file immediately after upload
     * @param bool $protected File is protected?
     * @return \rkit\filemanager\models\File|bool
     */
    public function createFromPath(
        $path,
        $ownerId = -1,
        $ownerType = -1,
        $saveAfterUpload = false,
        $protected = false
    ) {
        $tempfile = tempnam(sys_get_temp_dir(), 'FMR');
        if ($filecontent = @file_get_contents($path)) {
            file_put_contents($tempfile, $filecontent);
            $pathInfo = pathinfo($path);
            $file = new File([
                'tmp' => true,
                'owner_id' => $ownerId,
                'owner_type' => $ownerType,
                'size' => filesize($tempfile),
                'mime' => FileHelper::getMimeType($tempfile),
                'title' => $pathInfo['filename'],
                'name' => File::generateName($pathInfo['extension']),
                'protected' => $protected
            ]);

            return $file->saveToTmp($tempfile, $saveAfterUpload, false);
        } else {
            throw new InvalidValueException('Unable to create from `' . $path . '`');
        }

        return false;
    }
}
