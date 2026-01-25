<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Services\ElasticSearchService;
use App\Models\Contact;
use Illuminate\Support\Facades\Auth;
use App\Events\UserProfileRequested;
use App\Helpers\ApiResponse;

class SerachController extends Controller
{
    //

    protected $es;

    public function __construct(ElasticSearchService $es)
    {
        // $this->middleware('jwt.auth');
        $this->es = $es;
    }

    // public function searchNumber(Request $request)
    // {
    //     $request->validate([
    //         'number' => 'required|string'
    //     ]);

    //     $user = Auth::user();
    //     $target = preg_replace('/\D/', '', $request->number);

    //     $visited = [];
    //     $queue = [];
    //     $result = [];
    //     $depth = 0;
    //     $maxDepth = 10;

    //     // 🔹 ROOT = current user's contacts
    //     $userContacts = Contact::where('user_id', $user->id)
    //         ->pluck('normalized_mobile')
    //         ->toArray();

    //     foreach ($userContacts as $mobile) {
    //         $queue[] = ['mobile' => $mobile, 'depth' => 1];
    //     }

    //     while (!empty($queue)) {
    //         $node = array_shift($queue);

    //         $current = $node['mobile'];
    //         $depth = $node['depth'];

    //         if ($depth > $maxDepth) break;
    //         if (isset($visited[$current])) continue;

    //         $visited[$current] = true;

    //         // 🔍 Search in ES
    //         $nodes = $this->es->searchNumber($current, 50);

    //         foreach ($nodes as $n) {
    //             $contactMobile = $n['_source']['normalized_mobile'];
    //             $contactUserId = $n['_source']['user_id'];

    //             // 🎯 TARGET FOUND
    //             if ($contactMobile === $target) {

    //                 // show only if user saved this number
    //                 $myContact = Contact::where('user_id', $user->id)
    //                     ->where('normalized_mobile', $contactMobile)
    //                     ->first();

    //                 if ($myContact) {
    //                     $result[] = [
    //                         'name'   => $myContact->contact_name,
    //                         'mobile' => $myContact->contact_mobile,
    //                         'via_depth' => $depth
    //                     ];
    //                 }

    //                 // 🔔 notify ONLY ONCE
    //                 event(new UserProfileRequested($contactUserId, $user->id));

    //                 return response()->json([
    //                     'found' => true,
    //                     'chain' => $result
    //                 ]);
    //             }

    //             // BFS EXPAND
    //             if (!isset($visited[$contactMobile])) {
    //                 $queue[] = [
    //                     'mobile' => $contactMobile,
    //                     'depth' => $depth + 1
    //                 ];
    //             }
    //         }
    //     }

    //     return response()->json([
    //         'found' => false,
    //         'message' => 'No connection found within 10 hops'
    //     ]);
    // }

    // public function searchNumber(Request $request)
    // {
    //     $request->validate(['number' => 'required|string']);

    //     $user = Auth::user();
    //     $start = preg_replace('/\D/', '', $request->number);

    //     $visited = [];
    //     $queue = [$start];
    //     $result = [];
    //     $depth = 0;
    //     $maxDepth = 10;

    //     while (!empty($queue) && $depth < $maxDepth) {
    //         $nextQueue = [];

    //         foreach ($queue as $current) {
    //             if (isset($visited[$current])) continue;
    //             $visited[$current] = true;

    //             // Step 1: Find users who have this number
    //             $contacts = Contact::where('normalized_mobile', $current)->get();

    //             foreach ($contacts as $contact) {

    //                 // Step 2: Privacy rule
    //                 $existsInMyContacts = Contact::where('user_id', $user->id)
    //                     ->where('normalized_mobile', $contact->normalized_mobile)
    //                     ->exists();

    //                 if ($existsInMyContacts) {

    //                     $result[] = [
    //                         'name'   => $contact->contact_name,
    //                         'mobile' => $contact->contact_mobile,
    //                         'via_user' => $contact->user_id,
    //                         'depth' => $depth + 1
    //                     ];

    //                     // Step 3: Expand graph
    //                     $userContacts = Contact::where('user_id', $contact->user_id)
    //                         ->pluck('normalized_mobile');

    //                     foreach ($userContacts as $m) {
    //                         if (!isset($visited[$m])) {
    //                             $nextQueue[] = $m;
    //                         }
    //                     }
    //                 }
    //             }
    //         }

    //         $queue = array_unique($nextQueue);
    //         $depth++;
    //     }

    //     return response()->json([
    //         'start_number' => $start,
    //         'depth_reached' => $depth,
    //         'chain' => array_slice($result, 0, 50)
    //     ]);
    // }

    // public function searchNumber(Request $request)
    // {
    //     $request->validate(['number' => 'required|string']);

    //     $me = Auth::id();
    //     $start = preg_replace('/\D/', '', $request->number);

    //     $visitedNumbers = [];
    //     $visitedUsers   = [];
    //     $queue          = [$start];
    //     $depth          = 0;
    //     $maxDepth       = 5;
    //     $result         = [];

    //     while (!empty($queue) && $depth < $maxDepth) {

    //         // 1️⃣ find all contacts having these numbers
    //         $contacts = Contact::whereIn('normalized_mobile', $queue)
    //             ->select('user_id', 'contact_name', 'contact_mobile', 'normalized_mobile')
    //             ->get();

    //         $queue = []; // reset for next depth

    //         foreach ($contacts as $contact) {

    //             if (in_array($contact->user_id, $visitedUsers)) {
    //                 continue;
    //             }

    //             $visitedUsers[] = $contact->user_id;

    //             // 2️⃣ privacy rule: ONLY if I have this user saved
    //             $isAllowed = Contact::where('user_id', $me)
    //                 ->where('normalized_mobile', $contact->normalized_mobile)
    //                 ->exists();

    //             if (!$isAllowed) {
    //                 continue;
    //             }

    //             $result[] = [
    //                 'name'   => $contact->contact_name,
    //                 'mobile' => $contact->contact_mobile,
    //                 'user'   => $contact->user_id,
    //                 'depth'  => $depth + 1,
    //             ];

    //             // 3️⃣ expand graph
    //             $nextNumbers = Contact::where('user_id', $contact->user_id)
    //                 ->pluck('normalized_mobile')
    //                 ->toArray();

    //             foreach ($nextNumbers as $num) {
    //                 if (!in_array($num, $visitedNumbers)) {
    //                     $visitedNumbers[] = $num;
    //                     $queue[] = $num;
    //                 }
    //             }
    //         }

    //         $depth++;
    //     }

    //     return response()->json([
    //         'start_number' => $start,
    //         'depth_reached' => $depth,
    //         'chain' => $result
    //     ]);
    // }

    // public function searchNumber(Request $request)
    // {
    //     // $request->validate([
    //     //     'number' => 'required|string',
    //     //     'depth' => 'sometimes|integer|min:1|max:5'
    //     // ]);

    //     // $number = $request->input('number');
    //     // $depth = $request->input('depth', 2);

    //     // $chain = $this->es->searchNumberBFS($number, $depth);

    //     // return response()->json([
    //     //     'start_number' => $number,
    //     //     'depth_reached' => $depth,
    //     //     'chain' => $chain
    //     // ]);


    //     try {
    //         $number = $request->input('number');
    //          $depth = $request->input('depth', 2);
    //         $result = $this->es->searchNumberBFS($number ,$depth);

    //         if (!$result) {
    //             return ApiResponse::error('Number not found', 404);
    //         }

    //         return ApiResponse::success($result, 'Number found');
    //     } catch (\Exception $e) {
    //         return ApiResponse::error($e->getMessage(), 500);
    //     }
    // }


    //old
    // public function searchNumber(Request $request)
    // {
    //     $request->validate([
    //         'number' => 'required|string|min:10'
    //     ]);

    //     try {
    //         $number = $request->number;

    //         // 🔒 depth = system controlled
    //         $result = $this->es->searchNumberBFS($number);

    //         if (empty($result)) {
    //             return ApiResponse::error('No relation found', 404);
    //         }

    //         return ApiResponse::success([
    //             'searched_number' => $number,
    //             'relations' => $result,
    //             'total' => count($result)
    //         ], 'Relations found');
    //     } catch (\Throwable $e) {
    //         return ApiResponse::error('Search failed', 500);
    //     }
    // }

    // public function searchNumber(Request $request)
    // {
    //     $request->validate([
    //         'number' => 'required|string'
    //     ]);

    //     try {
    //         $number = $request->number;

    //         if (app()->environment(['local', 'testing'])) {
    //             $result = $this->es->searchLocal($number);
    //         } 
    //         // else {
    //         //     $result = $this->es->searchEks($number);
    //         // }

    //         if (empty($result)) {
    //             return ApiResponse::error('Number not found', 404);
    //         }

    //         return ApiResponse::success($result, 'Number found');
    //     } catch (\Throwable $e) {
    //         return ApiResponse::error('Search failed', 500);
    //     }
    // }

    public function searchNumber(Request $request)
    {
        $request->validate([
            'number' => 'required|string|min:10'
        ]);

        try {
            $result = $this->es->searchLocal($request->number);

            if (empty($result)) {
                return ApiResponse::error('Number not found in your network', 404);
            }

            return ApiResponse::success($result, 'Relation found');
        } catch (\Throwable $e) {
            return ApiResponse::error('Search failed', 500);
        }
    }
}
