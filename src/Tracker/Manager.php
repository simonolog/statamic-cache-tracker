<?php

namespace Thoughtco\StatamicCacheTracker\Tracker;

use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Statamic\StaticCaching\Cacher;

class Manager
{
    private $cacheKey = 'tracker::urls';

    public function add(string $url, array $tags = [])
    {
        $storeData = $this->cacheStore()->get($this->cacheKey) ?? [];
        $storeData[md5($url)] = [
            'url' => $url,
            'tags' => $tags
        ];

        $this->cacheStore()->forever($this->cacheKey, $storeData);

        return $this;
    }

    public function invalidate(array $tags = [])
    {
        $storeData = $this->cacheStore()->get($this->cacheKey) ?? [];

        $urls = [];
        foreach ($storeData as $key => $data) {
            $storeTags = $data['tags'];
            $url = $data['url'];

            if (count(array_intersect($tags, $storeTags)) > 0) {
                $urls[] = $url;

                unset($storeData[$url]);
            }
        }

        if (! empty($urls)) {
            $this->invalidateUrls($urls);

            $this->cacheStore()->forever($this->cacheKey, $storeData);
        }

        return $this;
    }

    public function cacheStore()
    {
        try {
            $store = Cache::store('static_cache');
        } catch (InvalidArgumentException $e) {
            $store = Cache::store();
        }

        return $store;
    }

    private function invalidateUrls($urls)
    {
        $cacher = app(Cacher::class);
        $cacher->invalidateUrls($urls);
    }
}
