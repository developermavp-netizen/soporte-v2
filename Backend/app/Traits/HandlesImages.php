<?php

namespace App\Traits;

use Cloudinary\Cloudinary as CloudinaryClient;
use Illuminate\Http\UploadedFile;

trait HandlesImages
{
    public function uploadImage(UploadedFile $file, $folder = 'uploads', $options = [])
    {
        try {
            $cloudinary = app(CloudinaryClient::class);

            $result = $cloudinary->uploadApi()->upload($file->getRealPath(), array_merge([
                'folder' => $folder,
                'transformation' => [
                    'quality' => 'auto',
                    'fetch_format' => 'auto'
                ]
            ], $options));

            return [
                'url'       => $result['secure_url'],
                'public_id' => $result['public_id'],
                'size'      => $result['bytes'] ?? null,
                'format'    => $result['format'] ?? null,
                'width'     => $result['width'] ?? null,
                'height'    => $result['height'] ?? null,
            ];

        } catch (\Exception $e) {
            throw new \Exception('Error uploading image: ' . $e->getMessage());
        }
    }

    public function deleteImage($publicId)
    {
        try {
            if ($publicId) {
                $cloudinary = app(CloudinaryClient::class);
                $cloudinary->uploadApi()->destroy($publicId);
            }
            return true;
        } catch (\Exception $e) {
            throw new \Exception('Error deleting image: ' . $e->getMessage());
        }
    }

    public function updateImage($oldPublicId, UploadedFile $newFile, $folder = 'uploads', $options = [])
    {
        if ($oldPublicId) {
            $this->deleteImage($oldPublicId);
        }
        return $this->uploadImage($newFile, $folder, $options);
    }
}