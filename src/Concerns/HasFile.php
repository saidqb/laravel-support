<?php

namespace Saidqb\LaravelSupport\Concerns;

trait HasFile
{
    protected function is_file()
    {
        return is_file($this->path);
    }

    protected function fileExtensionType($type)
    {
        $exstension = [
            'image' => [
                'jpg',
                'jpeg',
                'png',
                'gif',
                'bmp',
                'webp',
                'svg',
                'ico'
            ],
            'document' => [
                'pdf',
                'doc',
                'docx',
                'xls',
                'xlsx',
                'ppt',
                'pptx',
                'txt',
                'csv',
                'rtf'
            ],
            'video' => [
                'mp4',
                'avi',
                'flv',
                'wmv',
                'mov',
                'webm',
                'mkv',
                '3gp',
                'mpg',
                'mpeg'
            ],
        ];

        if (isset($exstension[$type])) {
            return $exstension[$type];
        }
        return [];
    }

}
