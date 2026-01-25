<?php

namespace App\Services;

use App\Models\Contact;
use Elastic\Elasticsearch\ClientBuilder as ElasticsearchClientBuilder;
use Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ElasticSearchService
{
    protected $client;
    protected int $MAX_DEPTH = 4;

    public function __construct()
    {
        $this->client = ElasticsearchClientBuilder::create()
            ->setHosts([env('ELASTIC_HOST', 'localhost:9200')])
            ->build();
    }

    public function indexContact($contact)
    {
        return $this->client->index([
            'index' => 'contacts',
            'id' => $contact['id'],
            'body' => $contact
        ]);
    }
    public function searchNumber(string $number, int $limit = 50)
    {
        $params = [
            'index' => 'contacts',
            'size' => $limit,
            'body' => [
                'query' => [
                    'term' => [
                        'normalized_mobile' => $number
                    ]
                ]
            ]
        ];

        return $this->client->search($params)['hits']['hits'] ?? [];
    }


    // public function searchNumberBFS(string $startNumber, int $maxDepth = 2): array
    // {
    //     $cacheKey = "search:$startNumber:$maxDepth";

    //     // Check Redis cache first
    //     if (Cache::has($cacheKey)) {
    //         return Cache::get($cacheKey);
    //     }

    //     $visited = [];
    //     $queue = [['number' => $startNumber, 'depth' => 0]];
    //     $chain = [];

    //     while (!empty($queue)) {
    //         $current = array_shift($queue);
    //         $number = $current['number'];
    //         $depth = $current['depth'];

    //         if (isset($visited[$number]) || $depth >= $maxDepth) {
    //             continue;
    //         }

    //         $visited[$number] = true;

    //         // Get direct contacts from DB
    //         $contacts = DB::table('contacts')
    //             ->where('normalized_mobile', $number)
    //             ->pluck('normalized_mobile')
    //             ->toArray();

    //         foreach ($contacts as $contact) {
    //             if (!isset($visited[$contact])) {
    //                 $queue[] = ['number' => $contact, 'depth' => $depth + 1];
    //                 $chain[] = $contact;
    //             }
    //         }
    //     }

    //     // Cache results in Redis for 1 hour
    //     Cache::put($cacheKey, $chain, 3600);

    //     return $chain;
    // }

    // public function searchNumberBFS(string $startNumber, int $maxDepth = 2): array
    // {
    //     $cacheKey = "search:$startNumber:$maxDepth";

    //     // Check Redis cache first
    //     if (Cache::has($cacheKey)) {
    //         return Cache::get($cacheKey);
    //     }

    //     $visitedNumbers = [];
    //     $visitedUsers   = [];
    //     $queue          = [['number' => preg_replace('/\D/', '', $startNumber), 'depth' => 0]];
    //     $chain          = [];

    //     while (!empty($queue)) {
    //         $current = array_shift($queue);
    //         $number  = $current['number'];
    //         $depth   = $current['depth'];

    //         if (isset($visitedNumbers[$number]) || $depth >= $maxDepth) {
    //             continue;
    //         }

    //         $visitedNumbers[$number] = true;

    //         // 1️⃣ Find all contacts having this number
    //         $contacts = DB::table('contacts')
    //             ->where('normalized_mobile', $number)
    //             ->select('user_id', 'contact_name', 'contact_mobile', 'normalized_mobile')
    //             ->get();

    //         foreach ($contacts as $contact) {

    //             // Skip users we've already processed
    //             if (in_array($contact->user_id, $visitedUsers)) {
    //                 continue;
    //             }
    //             $visitedUsers[] = $contact->user_id;

    //             // 2️⃣ Privacy rule: only if current user has this number saved
    //             $isAllowed = DB::table('contacts')
    //                 ->where('user_id', auth()->id())
    //                 ->where('normalized_mobile', $contact->normalized_mobile)
    //                 ->exists();

    //             if (!$isAllowed) {
    //                 continue;
    //             }

    //             $chain[] = [
    //                 'name'   => $contact->contact_name,
    //                 'mobile' => $contact->contact_mobile,
    //                 'user'   => $contact->user_id,
    //                 'depth'  => $depth + 1
    //             ];


    //             $userContacts = Contact::
    //                 where('user_id', $contact->user_id)
    //                 ->pluck('normalized_mobile')
    //                 ->toArray();

    //             foreach ($userContacts as $num) {
    //                 if (!isset($visitedNumbers[$num])) {
    //                     $queue[] = ['number' => $num, 'depth' => $depth + 1];
    //                 }
    //             }
    //         }
    //     }

    //     // Cache results in Redis for 1 hour
    //     Cache::put($cacheKey, $chain, 3600);

    //     return $chain;
    // }
    // public function searchNumberBFS(string $targetNumber, int $maxDepth = 5): array
    // {
    //     $me = auth()->id();
    //     $target = preg_replace('/\D/', '', $targetNumber);

    //     $cacheKey = "search:$me:$target:$maxDepth";
    //     if (Cache::has($cacheKey)) {
    //         return Cache::get($cacheKey);
    //     }

    //     // 1️⃣ preload all contacts (SINGLE QUERY)
    //     $allContacts = Contact::select('user_id', 'normalized_mobile')
    //         ->get()
    //         ->groupBy('user_id');

    //     // 2️⃣ map: number => users
    //     $numberToUsers = Contact::select('user_id', 'normalized_mobile')
    //         ->get()
    //         ->groupBy('normalized_mobile');

    //     // 3️⃣ BFS on USERS
    //     $queue = [];
    //     $visitedUsers = [];
    //     $relations = [];

    //     // seed = my contacts' users
    //     $myNumbers = $allContacts[$me]?->pluck('normalized_mobile')->toArray() ?? [];

    //     foreach ($myNumbers as $num) {
    //         if (isset($numberToUsers[$num])) {
    //             foreach ($numberToUsers[$num] as $c) {
    //                 $queue[] = [
    //                     'user'  => $c->user_id,
    //                     'depth' => 1
    //                 ];
    //             }
    //         }
    //     }

    //     while (!empty($queue)) {
    //         $node = array_shift($queue);

    //         $userId = $node['user'];
    //         $depth  = $node['depth'];

    //         if ($depth > $maxDepth || isset($visitedUsers[$userId])) {
    //             continue;
    //         }

    //         $visitedUsers[$userId] = true;

    //         $numbers = $allContacts[$userId]?->pluck('normalized_mobile')->toArray() ?? [];

    //         // 🎯 target found
    //         if (in_array($target, $numbers)) {
    //             $relations[] = [
    //                 'mobile' => $target,
    //                 'depth'  => $depth,
    //                 'type'   => $this->relationType($depth)
    //             ];
    //         }

    //         // expand graph
    //         foreach ($numbers as $num) {
    //             if (isset($numberToUsers[$num])) {
    //                 foreach ($numberToUsers[$num] as $u) {
    //                     if (!isset($visitedUsers[$u->user_id])) {
    //                         $queue[] = [
    //                             'user'  => $u->user_id,
    //                             'depth' => $depth + 1
    //                         ];
    //                     }
    //                 }
    //             }
    //         }
    //     }

    //     Cache::put($cacheKey, $relations, 3600);
    //     return $relations;
    // }

    // protected int $MAX_DEPTH = 4;

    public function searchNumberBFS(string $targetNumber): array
    {
        $me = auth()->id();
        $target = preg_replace('/\D/', '', $targetNumber);

        $cacheKey = "search:$me:$target";

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        /** preload all contacts (FAST) */
        $allContacts = Contact::select('user_id', 'normalized_mobile')->get();

        $byUser   = $allContacts->groupBy('user_id');
        $byNumber = $allContacts->groupBy('normalized_mobile');

        /** BFS START = MY CONTACTS */
        $queue = [];
        $visitedNumbers = [];
        $visitedUsers   = [];

        $myNumbers = $byUser->get($me, collect())
            ->pluck('normalized_mobile')
            ->unique()
            ->toArray();

        foreach ($myNumbers as $num) {
            $queue[] = [
                'number' => $num,
                'depth'  => 1
            ];
        }

        $relations = [];

        while (!empty($queue)) {

            $node  = array_shift($queue);
            $num   = $node['number'];
            $depth = $node['depth'];

            if ($depth > $this->MAX_DEPTH) {
                continue;
            }

            if (isset($visitedNumbers[$num])) {
                continue;
            }

            $visitedNumbers[$num] = true;

            /** 🎯 TARGET FOUND */
            if ($num === $target) {
                $relations[] = [
                    'mobile' => $target,
                    'depth'  => $depth,
                    'type'   => $this->relationType($depth)
                ];
            }

            /** expand graph */
            if (!$byNumber->has($num)) {
                continue;
            }

            foreach ($byNumber->get($num) as $contact) {

                if (isset($visitedUsers[$contact->user_id])) {
                    continue;
                }

                $visitedUsers[$contact->user_id] = true;

                $nextNumbers = $byUser->get($contact->user_id, collect())
                    ->pluck('normalized_mobile')
                    ->unique()
                    ->toArray();

                foreach ($nextNumbers as $next) {
                    if (!isset($visitedNumbers[$next])) {
                        $queue[] = [
                            'number' => $next,
                            'depth'  => $depth + 1
                        ];
                    }
                }
            }
        }

        Cache::put($cacheKey, $relations, 3600);

        return $relations;
    }


    private function relationType(int $depth): string
    {
        return match (true) {
            $depth === 1 => 'very_strong',
            $depth === 2 => 'strong',
            $depth === 3 => 'medium',
            default      => 'weak',
        };
    }
}
