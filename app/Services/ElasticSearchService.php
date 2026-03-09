<?php

namespace App\Services;

use App\Models\CallLog;
use App\Models\CallLogStat;
use App\Models\Contact;

class ElasticSearchService
{
    const MAX_DEPTH = 4;
    const MAX_NODES = 2000;

    // public function searchLocal(string $targetNumber): array
    // {
    //     $me     = auth()->id();
    //     $target = preg_replace('/\D/', '', $targetNumber);

    //     $visitedNumbers = [];
    //     $visitedUsers   = [];
    //     $queue          = [];
    //     $relations      = [];
    //     $processedNodes = 0;

    //     $startNumbers = Contact::where('user_id', $me)
    //         ->pluck('normalized_mobile')
    //         ->unique()
    //         ->take(300)             
    //         ->toArray();

    //     foreach ($startNumbers as $num) {
    //         $queue[] = ['number' => $num, 'depth' => 1];
    //     }

    //     while (!empty($queue)) {

    //         if ($processedNodes >= self::MAX_NODES) {
    //             break; 
    //         }

    //         $node = array_shift($queue);
    //         $processedNodes++;

    //         $num   = $node['number'];
    //         $depth = $node['depth'];

    //         if ($depth > self::MAX_DEPTH) continue;
    //         if (isset($visitedNumbers[$num])) continue;

    //         $visitedNumbers[$num] = true;


    //         if ($num === $target) {
    //             $relations[] = [
    //                 'mobile' => $target,
    //                 'depth'  => $depth,
    //                 'type'   => $this->relationType($depth)
    //             ];
    //             break; 
    //         }


    //         $userIds = Contact::where('normalized_mobile', $num)
    //             ->pluck('user_id')
    //             ->unique()
    //             ->take(50) 
    //             ->toArray();

    //         foreach ($userIds as $userId) {

    //             if (isset($visitedUsers[$userId])) continue;
    //             $visitedUsers[$userId] = true;


    //             $nextNumbers = Contact::where('user_id', $userId)
    //                 ->pluck('normalized_mobile')
    //                 ->take(50) 
    //                 ->toArray();

    //             foreach ($nextNumbers as $next) {
    //                 if (!isset($visitedNumbers[$next])) {
    //                     $queue[] = [
    //                         'number' => $next,
    //                         'depth'  => $depth + 1
    //                     ];
    //                 }
    //             }
    //         }
    //     }

    //     return $relations;
    // }

    public function searchLocal(string $targetNumber): array
    {
        // $target = preg_replace('/\D/', '', $targetNumber);
        $target = $this->normalizeNumber($targetNumber);

        $relation = $this->bfsSearch($target);

        if (!$relation) {
            return [];
        }

        $callCount = \DB::table('call_log_stats')
            ->whereRaw('RIGHT(normalized_mobile,10) = ?', [$target])
            ->sum('total_calls');

        $contactCount = Contact::whereRaw('RIGHT(normalized_mobile,10) = ?', [$target])->count();

        // $callCount = \DB::table('call_log_stats')
        //     ->where('normalized_mobile', $target)
        //     ->sum('total_calls');

        // $contactCount = Contact::where('normalized_mobile', $target)->count();

        $score = $this->calculateScore(
            $relation['depth'],
            $callCount,
            $contactCount
        );

        return [
            'mobile' => $target,
            'depth' => $relation['depth'],
            'relation_type' => $relation['type'],
            'call_count' => $callCount,
            'contact_count' => $contactCount,
            'score' => $score,
            'color' => $this->connectionColor($score),
            'is_favourite' => $score > 120
        ];
    }
    private function calculateScore($depth, $calls, $contacts)
    {
        $depthScore = match ($depth) {
            1 => 100,
            2 => 60,
            3 => 30,
            default => 10
        };

        return $depthScore + ($calls * 2) + ($contacts * 3);
    }
    private function connectionColor($score)
    {
        if ($score >= 120) return 'green';
        if ($score >= 60) return 'yellow';
        return 'red';
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

    private function bfsSearch(string $target): ?array
    {
        $me = auth()->id();

        $visitedNumbers = [];
        $visitedUsers   = [];
        $queue          = [];

        $startNumbers = Contact::where('user_id', $me)
            ->pluck('normalized_mobile')
            ->unique()
            ->take(300)
            ->toArray();

        foreach ($startNumbers as $num) {
            $queue[] = ['number' => $num, 'depth' => 1];
        }

        while (!empty($queue)) {

            $node = array_shift($queue);

            $num = $node['number'];
            $depth = $node['depth'];

            if ($depth > self::MAX_DEPTH) continue;

            if (isset($visitedNumbers[$num])) continue;

            $visitedNumbers[$num] = true;


            // if ($num === $target) 
            if (substr($num, -10) === $target) {
                return [
                    'mobile' => $target,
                    'depth' => $depth,
                    'type' => $this->relationType($depth)
                ];
            }
            // Contact::whereRaw('RIGHT(normalized_mobile,10) = ?', [substr($num, -10)])

            // $userIds = Contact::where('normalized_mobile', $num)
            // $userIds =
            //     Contact::whereRaw('RIGHT(normalized_mobile,10) = ?', [substr($num, -10)])
            //     ->pluck('user_id')
            //     ->unique()
            //     ->take(50)
            //     ->toArray();

            $userIds = Contact::whereRaw('RIGHT(normalized_mobile,10) = ?', [substr($num, -10)])
                ->pluck('user_id')
                ->unique()
                ->take(50)
                ->toArray();

            foreach ($userIds as $userId) {

                if (isset($visitedUsers[$userId])) continue;

                $visitedUsers[$userId] = true;

                $nextNumbers = Contact::where('user_id', $userId)
                    ->pluck('normalized_mobile')
                    ->take(50)
                    ->toArray();

                foreach ($nextNumbers as $next) {

                    if (!isset($visitedNumbers[$next])) {
                        $queue[] = [
                            'number' => $next,
                            'depth' => $depth + 1
                        ];
                    }
                }
            }
        }

        return null;
    }


    private function normalizeNumber(string $number): string
    {
        $number = preg_replace('/\D/', '', $number);
        if (strlen($number) > 10) {
            $number = substr($number, -10);
        }

        return $number;
    }

    // call logs
    public function storeCallLog($userId, $mobile, $type, $duration = null)
    {
        // $normalized = preg_replace('/\D/', '', $mobile);
        $normalized = substr(preg_replace('/\D/', '', $mobile), -10);

        CallLog::create([
            'user_id' => $userId,
            'mobile' => $mobile,
            'normalized_mobile' => $normalized,
            'type' => $type,
            'duration' => $duration,
            'called_at' => now()
        ]);

        $stat = CallLogStat::firstOrCreate([
            'user_id' => $userId,
            'normalized_mobile' => $normalized
        ]);

        $stat->total_calls += 1;

        if ($type == 'incoming') {
            $stat->incoming_calls += 1;
        }

        if ($type == 'outgoing') {
            $stat->outgoing_calls += 1;
        }

        $stat->save();
    }
}
