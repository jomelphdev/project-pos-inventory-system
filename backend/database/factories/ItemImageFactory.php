<?php

namespace Database\Factories;

use App\Models\ItemImage;
use Illuminate\Database\Eloquent\Factories\Factory;


class ItemImageFactory extends Factory
{
    protected $model = ItemImage::class;

    public function definition()
    {
        $images = [
            'https://www.cheetos.com/sites/cheetos.com/files/2019-03/Cheetos%20Crunchy_v2_0.png',
            'https://tse1.mm.bing.net/th?id=OIP.yRf-HQPL0o47AeTzpQ6WLgHaHa',
            'https://ctl.s6img.com/society6/img/2rVD8IRkKjHHgOqSgSsnpDvUkf4/w_700/cases/iphone12/slim/back/~artwork,fw_1300,fh_2000,iw_1300,ih_2000/s6-original-art-uploads/society6/uploads/misc/806da9cab895445c9a141af2170a4b50/~~/retro-70s-color-palette-iii143323-cases.jpg',
            'https://assets.adidas.com/images/w_600,f_auto,q_auto/4e894c2b76dd4c8e9013aafc016047af_9366/Superstar_Shoes_White_FV3284_01_standard.jpg',
            'https://secure.img1-cg.wfcdn.com/im/91378491/resize-h600-w600%5Ecompr-r85/8140/81409053/Sofas.jpg'
        ];

        return [
            'item_id' => \App\Models\Item::factory(),
            'image_url' => $images[rand(0, count($images) - 1)],
        ];
    }
}