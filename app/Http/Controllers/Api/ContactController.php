<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Contact;
use App\Services\ElasticSearchService;
use Illuminate\Support\Facades\Auth;
use App\Helpers\ApiResponse;

class ContactController extends Controller
{
    protected $es;

    public function __construct(ElasticSearchService $es)
    {
        // $this->middleware('jwt.auth');
        $this->es = $es;
    }

    // public function upload(Request $request)
    // {
    //     $request->validate([
    //         'contacts' => 'required|array'
    //     ]);

    //     try {
    //         $user = Auth::user();
    //         $uploaded = [];

    //         foreach ($request->contacts as $c) {
    //             $normalized = preg_replace('/\D/', '', $c['mobile']);

    //             $contact = Contact::updateOrCreate(
    //                 [
    //                     'user_id' => $user->id,
    //                     'contact_mobile' => $c['mobile']
    //                 ],
    //                 [
    //                     'contact_name' => $c['name'] ?? null,
    //                     'normalized_mobile' => $normalized
    //                 ]
    //             );

    //             // ElasticSearch indexing
    //             $this->es->indexContact([
    //                 'id' => $contact->id,
    //                 'user_id' => $user->id,
    //                 'contact_mobile' => $contact->contact_mobile,
    //                 'contact_name' => $contact->contact_name,
    //                 'normalized_mobile' => $contact->normalized_mobile
    //             ]);

    //             $uploaded[] = $contact->id;
    //         }

    //         return ApiResponse::success(['uploaded' => $uploaded], 'Contacts uploaded successfully');
    //     } catch (\Exception $e) {
    //         // Global error response
    //         return ApiResponse::error($e->getMessage(), 500);
    //     }
    // }


    public function upload(Request $request)
    {


        $request->validate([
            'device_id' => 'required|string',
            'contacts' => 'required|array',
            'contacts.*.phone' => 'required|string'
        ]);

        try {

            $user = Auth::user(); // user_id request se mat lo, auth se hi safe hai
            $uploaded = [];

            foreach ($request->contacts as $c) {

                // normalize phone
                $normalized = preg_replace('/\D/', '', $c['phone']);

                $contact = Contact::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'contact_mobile' => $c['phone']
                    ],
                    [
                        'contact_name' => $c['name'] ?? null,
                        'normalized_mobile' => $normalized,
                        // 'device_id' => $request->device_id
                    ]
                );



                // ElasticSearch indexing
                // $this->es->indexContact([
                //     'id' => $contact->id,
                //     'user_id' => $user->id,
                //     'contact_mobile' => $contact->contact_mobile,
                //     'contact_name' => $contact->contact_name,
                //     'normalized_mobile' => $contact->normalized_mobile,
                //     // 'device_id' => $request->device_id
                // ]);

                $uploaded[] = $contact->id;
            }
            //  dd($uploaded);
            return ApiResponse::success(
                ['uploaded' => $uploaded],
                'Contacts uploaded successfully'
            );
        } catch (\Exception $e) {

            // dd($e->getMessage());
            return ApiResponse::error($e->getMessage(), 500);
        }
    }


    public function getContacts()
    {
        try {

            $user = Auth::user(); // user_id request se mat lo, auth se hi safe hai
            // $uploaded = [];

            $contacts = Contact::select(
                'contact_name as name',
                'contact_mobile as phone'
            )
                ->where('user_id', $user->id)
                ->get();

            // dd($contacts);
            return ApiResponse::success(
                ['uploaded' => $contacts],
                'Contacts list fetch successfully'
            );
        } catch (\Exception $e) {

            // dd($e->getMessage());
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}
