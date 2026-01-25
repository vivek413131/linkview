<?php

namespace App\Services;

use App\Models\Contact;

class ElasticSearchService
{
    const MAX_DEPTH = 4;
    const MAX_NODES = 2000;

    public function searchLocal(string $targetNumber): array
    {
        $me     = auth()->id();
        $target = preg_replace('/\D/', '', $targetNumber);

        $visitedNumbers = [];
        $visitedUsers   = [];
        $queue          = [];
        $relations      = [];
        $processedNodes = 0;

        /** 🔹 START = my contacts only */
        $startNumbers = Contact::where('user_id', $me)
            ->pluck('normalized_mobile')
            ->unique()
            ->take(300)              // 🔥 LIMIT
            ->toArray();

        foreach ($startNumbers as $num) {
            $queue[] = ['number' => $num, 'depth' => 1];
        }

        while (!empty($queue)) {

            if ($processedNodes >= self::MAX_NODES) {
                break; // 🛑 safety stop
            }

            $node = array_shift($queue);
            $processedNodes++;

            $num   = $node['number'];
            $depth = $node['depth'];

            if ($depth > self::MAX_DEPTH) continue;
            if (isset($visitedNumbers[$num])) continue;

            $visitedNumbers[$num] = true;

            /** 🎯 FOUND TARGET */
            if ($num === $target) {
                $relations[] = [
                    'mobile' => $target,
                    'depth'  => $depth,
                    'type'   => $this->relationType($depth)
                ];
                break; // ✅ STOP BFS
            }

            /** 🔹 batch: users having this number */
            $userIds = Contact::where('normalized_mobile', $num)
                ->pluck('user_id')
                ->unique()
                ->take(50) // 🔥 LIMIT
                ->toArray();

            foreach ($userIds as $userId) {

                if (isset($visitedUsers[$userId])) continue;
                $visitedUsers[$userId] = true;

                /** 🔹 get THEIR contacts (limited) */
                $nextNumbers = Contact::where('user_id', $userId)
                    ->pluck('normalized_mobile')
                    ->take(50) // 🔥 LIMIT
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

        return $relations;
    }

    private function relationType(int $depth): string
    {
        return match ($depth) {
            1 => 'direct',
            2 => 'friend_of_friend',
            3 => 'third_degree',
            default => 'weak',
        };
    }
}
