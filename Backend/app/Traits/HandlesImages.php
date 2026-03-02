<?php

namespace App\Traits;

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\UploadedFile;

trait HandlesImages
{
    /**
     * Subir imagen a Cloudinary
     */
    public function uploadImage(UploadedFile $file, $folder = 'uploads', $options = [])
    {
        try {
            $uploadedFile = Cloudinary::upload($file->getRealPath(), array_merge([
                'folder' => $folder,
                'transformation' => [
                    'quality' => 'auto',
                    'fetch_format' => 'auto'
                ]
            ], $options));

            return [
                'url' => $uploadedFile->getSecurePath(),
                'public_id' => $uploadedFile->getPublicId(),
                'size' => $uploadedFile->getSize(),
                'format' => $uploadedFile->getFileType(),
                'width' => $uploadedFile->getWidth(),
                'height' => $uploadedFile->getHeight(),
            ];
        } catch (\Exception $e) {
            throw new \Exception('Error uploading image: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar imagen de Cloudinary
     */
    public function deleteImage($publicId)
    {
        try {
            if ($publicId) {
                Cloudinary::destroy($publicId);
            }
            return true;
        } catch (\Exception $e) {
            throw new \Exception('Error deleting image: ' . $e->getMessage());
        }
    }

    /**
     * Actualizar imagen (eliminar anterior y subir nueva)
     */
    public function updateImage($oldPublicId, UploadedFile $newFile, $folder = 'uploads', $options = [])
    {
        if ($oldPublicId) {
            $this->deleteImage($oldPublicId);
        }
        
        return $this->uploadImage($newFile, $folder, $options);
    }
}