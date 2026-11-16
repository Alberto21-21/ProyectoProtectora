<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;

class CloudinaryService
{
    protected Cloudinary $cloudinary;

    public function __construct()
    {
        $this->cloudinary = new Cloudinary(
            config('services.cloudinary.url')
        );
    }

    // SUBIR UNA IMAGEN A CLOUDINARY
    public function upload(
        UploadedFile $image,
        string $carpeta = 'protectora'
    ): array {
        $resultado = $this->cloudinary
            ->uploadApi()
            ->upload($image->getRealPath(), [
                'folder' => $carpeta,
                'resource_type' => 'image',
            ]);

        return [
            'secure_url' => $resultado['secure_url'],
            'public_id' => $resultado['public_id'],
        ];
    }

    // ELIMINAR UNA IMAGEN DE CLOUDINARY
    public function eliminar(string $publicId): void
    {
        $this->cloudinary
            ->uploadApi()
            ->destroy($publicId, [
                'resource_type' => 'image',
                'invalidate' => true,
            ]);
    }
}