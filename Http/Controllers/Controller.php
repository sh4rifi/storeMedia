<?php

namespace App\Http\Controllers;

use App\Helpers\Sh4Helper;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Auth;
use Intervention\Image\Facades\Image;

use Illuminate\Http\Request;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;


    public $pathThumbnail = '/uploads/thumbnail/';
    public $pathMedia = '/uploads/media/';
    public $prefix = null; #todo add to web
    public $divider = '-';
    public $randomCharacter = 5; #todo add to web
    public $maxAllowedVideos = 6;
    public $cropWidthThumbnail = 300;
    public $cropHeightThumbnail = 300;
    public $maxAllowedWidth = 780;
    public $defaultPicture = '/uploads/media/default-user.jpg';

    const PROGRAM_STATUSES = [
        'incomplete' => -1,
        'init' => 0,
        'send_without_call' => 1,
        'send_with_call' => 2,
    ];


    public $reagentReward;


    public function __construct()
    {
        $this->reagentReward = 111; #todo sh4: please add in key value table
        Auth::onceUsingId(1); #todo sh4: for testing


        if ($this->prefix)
            $this->prefix = $this->prefix . $this->divider;

    }

    private function storePicture($originalMedia, $makeThumbnail)
    {
        if (!$originalMedia)
            return null;

        $originalPath = public_path() . $this->pathMedia;
        $thumbnailPath = public_path() . $this->pathThumbnail;
        $extension = $originalMedia->getClientOriginalExtension();
        $name = $this->prefix . $this->quickRandom() . time() . '.' . $extension;

        $img = Image::make(($originalMedia->getRealPath()));

        $img->resize($this->maxAllowedWidth, null, function ($constraint) {
            $constraint->aspectRatio();
        });

        $img->save($originalPath . $name, 90);

        if ($makeThumbnail) {
            if ($img->height() > $img->width())
                $img->resize($this->cropWidthThumbnail, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
            else
                $img->resize(null, $this->cropWidthThumbnail, function ($constraint) {
                    $constraint->aspectRatio();
                });


            $img->crop($this->cropWidthThumbnail, $this->cropHeightThumbnail);

            $img->save($thumbnailPath . $name, 90);
        }

        return $this->pathMedia . $name;
    }


    private function storeVideo($originalMedia)
    {
        if (!$originalMedia)
            return null;

        $extension = $originalMedia->getClientOriginalExtension();
        $name = $this->prefix . $this->quickRandom() . time() . '.' . $extension;
        $path = public_path() . $this->pathMedia;
        $originalMedia->move($path, $name);

        return $this->pathMedia . $name;
    }

    public function storeMedia($media, $type, $thumbnail = true)
    {
        if ($type == 'video')
            return $this->storeVideo($media);
        elseif ($type == 'picture')
            return $this->storePicture($media, $thumbnail);

    }

    private function quickRandom()
    {
        $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        $str = substr(str_shuffle(str_repeat($pool, $this->randomCharacter)), 0, $this->randomCharacter);

        if ($this->randomCharacter)
            $str = $str . $this->divider;

        return $str;
    }

    public function unlinkMedia($media_path)
    {
        if (!$media_path)
            return 0;


        $paths[] = public_path() . $media_path;
        $paths[] = public_path() . Sh4Helper::createThumbnailPathForUnlink($media_path);


        foreach ($paths as $path)
            if (is_file($path))
                unlink($path);

        return 1;
    }

}
