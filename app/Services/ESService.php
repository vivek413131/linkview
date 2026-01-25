<?php

namespace App\Services;

use Elastic\Elasticsearch\ClientBuilder;
// use Elasticsearch\Elastic\Elasticsearch\ClientBuilder;


class ESService
{
    protected $client;

    public function __construct()
    {
        $this->client = ClientBuilder::create()->setHosts(['localhost:9200'])->build();
    }

    public function indexContact($contact)
    {
        $params = [
            'index' => 'contacts',
            'id' => $contact->id,
            'body' => $contact->toArray()
        ];

        return $this->client->index($params);
    }

    public function searchContact($query)
    {
        $params = [
            'index' => 'contacts',
            'body' => [
                'query' => [
                    'multi_match' => [
                        'query' => $query,
                        'fields' => ['contact_name', 'contact_mobile', 'normalized_mobile']
                    ]
                ]
            ]
        ];

        return $this->client->search($params);
    }
}
