<?php

namespace modules\twig\twigextension;

use Craft;
use craft\elements\Asset;

use Twig_Extension;
use Twig_SimpleFilter;

class TwigExtension extends Twig_Extension
{
    public function getFilters()
    {
        return [
            new Twig_SimpleFilter('img_tag', [$this, 'imgTag']),
            new Twig_SimpleFilter('img_url', [$this, 'imgUrl']),
            new Twig_SimpleFilter('youtube', [$this, 'youtube']),
        ];
    }

    public function imgUrl($asset, $options = [])
    {
        if (empty($asset) || !$asset instanceof Asset) {
            return '';
        }

        $size = !!isset($options['size']) ? $options['size'] : [
            'width' => 1920,
            'height' => 0,
        ];

        $config = Craft::$app->config->getConfigFromFile('general');
        $url = '';

        if (isset($config['ficelle']) && !empty($config['ficelle'])) {
            $transform = '';

            if (!empty($size['width'])) {
                $transform .= '&width=' . $size['width'];
            }

            if (!empty($size['height'])) {
                $transform .= '&height=' . $size['height'];
            }

            $url = 'https://' . $config['ficelle'] . '.ficelle.app/v1?src=' . urlencode($asset->getUrl()) . $transform;
        } else {
            $url = $asset->getUrl([
                'width' => $size['width'] ?? 0,
                'height' => $size['height'] ?? 0
            ]);
        }

        return $url;
    }

    public function youtube($src, $thumbnail = null)
    {
        if (empty($src) || strlen($src) > 2083) {
            return '';
        }
        $reg = '/(?:https?:\/{2}(?:(?:www.youtube(?:-nocookie)?\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=))|(?:youtu\.be\/)))([a-zA-Z0-9_-]{11})/';
        preg_match($reg, $src, $match);
        $thumbnailAttr = $thumbnail ? 'style="background-image: url(' . $thumbnail . ');"' : '';
        if (isset($match[1])) {
            return '<lite-youtube ' . $thumbnailAttr . 'videoid="' . $match[1] . '" params="modestbranding=1&rel=0&autoplay=1"></lite-youtube>';
        }
    }

    public function imgTag($asset, $options = [])
    {
        if (empty($asset) || !$asset instanceof Asset) {
            return '';
        }

        $images = !!isset($options['sizes']) ? $options['sizes'] : [
            [
                'width' => '430',
                'height' => '0',
                'mq' => '(max-width: 430px)',
            ],
            [
                'width' => '890',
                'height' => '0',
                'mq' => '(max-width: 890px)',
            ],
            [
                'width' => '1260',
                'height' => '0',
                'mq' => '(max-width: 1260px)',
            ],
            [
                'width' => '1920',
                'height' => '0',
                'mq' => '',
            ],
        ];

        $urls = [];

        foreach ($images as $index => $image) {
            $images[$index]['url'] = $this->imgUrl($asset, [
                'size' => $image,
            ]);
        }

        $srcset = implode(', ', array_map(function ($image) {
            return trim($image['url'] . ' ' . $image['width'] . 'w');
        }, $images));

        $sizes = implode(', ', array_map(function ($image) {
            return trim($image['mq'] . ' ' . $image['width'] . 'w');
        }, $images));

        $attributes = [
            'srcset' => $srcset,
            'sizes' => $sizes,
            'src' => $this->imgUrl($asset),
            'alt' => $asset->alt,
        ];

        $attributes = array_merge($attributes, isset($options) ? $options : []);
        $computedAttributes = [];

        foreach ($attributes as $key => $value) {
            $computedAttributes[] = $key . '="' . $value . '"';
        }

        $computedAttributes = implode(' ', $computedAttributes);

        return '<img ' . $computedAttributes . ' />';
    }
}
