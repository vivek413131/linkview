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
