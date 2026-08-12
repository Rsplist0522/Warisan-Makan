<?php
return [
    'sources' => [
        // Example source placeholder. Replace with real site configs.
        // You can now use either a config key or a seed URL to run the crawler.
        // Example by source key:
        //   php artisan crawl:heritage-shops example_site --limit=5
        // Example by seed URL:
        //   php artisan crawl:heritage-shops https://example.com/malaysia/heritage-shops --limit=5
        'example_site' => [
            'seed_urls' => [
                // 'https://example.com/malaysia/heritage-shops'
            ],
            'list_selector' => '.shop-card',
            'detail_link_selector' => 'a.detail-link',
            'fields' => [
                'name' => 'h1.title',
                'location' => '.meta .location',
                'country' => '.meta .country',
                'description' => '.description',
                'founder' => '.founder',
                'establishment_year' => '.established',
                'heritage_story' => '.story',
                'operating_hours' => '.hours',
            ],
            'next_page_selector' => '.pagination .next a',
            'base_uri' => null,
            'rate_limit_seconds' => 1,
        ],
        'malaya_heritage' => [
            'seed_urls' => [
                'https://www.malayaheritage.com/',
            ],
            'detail_page_mode' => true,
            'fields' => [
                'name' => 'h1',
                'location' => 'jsonld:address.streetAddress',
                'country' => 'static:Malaysia',
                'category' => 'static:Heritage Food',
                'description' => 'css:p:nth-of-type(2)',
                'founder' => 'static:',
                'establishment_year' => 'static:',
                'heritage_story' => 'css:h5',
                'operating_hours' => 'static:',
            ],
            'base_uri' => 'https://www.malayaheritage.com',
            'rate_limit_seconds' => 1,
        ],
    ],

    'malaysia_states' => [
        'kuala lumpur','johor','penang','pahang','selangor','perak','kedah','kelantan',
        'terengganu','negeri sembilan','melaka','perlis','sabah','sarawak','putrajaya'
    ],

    'minimum_years' => 30,
];
