<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RealNetworkSeeder extends Seeder
{
    public function run(): void
    {
        $TOTAL_USERS = 5000;          // manageable for testing
        $CONTACTS_PER_USER = 50;      // realistic
        $CLUSTER_SIZE = 100;
        $MAX_CALLS_PER_CONTACT = 20;  // per contact

        echo "Seeding users...\n";

        $users = [];
        for ($i = 1; $i <= $TOTAL_USERS; $i++) {
            $users[] = [
                'mobile' => '9' . str_pad($i, 9, '0', STR_PAD_LEFT),
                'name' => 'User ' . $i,
                'email' => "user{$i}@example.com",
                'experience_years' => rand(0,20),
                'location_lat' => rand(-9000000,9000000)/100000,
                'location_lng' => rand(-18000000,18000000)/100000,
                'is_active' => true,
                'is_buisness' => rand(0,1),
                'is_govt' => rand(0,1),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if ($i % 1000 === 0) {
                DB::table('users')->insert($users);
                $users = [];
                echo "Inserted users: {$i}\n";
            }
        }
        if ($users) DB::table('users')->insert($users);

        $users = User::orderBy('id')->get(['id','mobile'])->toArray();
        $total = count($users);

        echo "Seeding designations...\n";
        $designations = ['Manager','Engineer','Analyst','Director','Clerk','Consultant','Officer'];
        foreach ($designations as $name) {
            DB::table('designations')->updateOrInsert(['name'=>$name]);
        }

        echo "Building contact graph and call logs...\n";

        $clusters = array_chunk($users, $CLUSTER_SIZE);

        foreach ($clusters as $clusterIndex => $clusterUsers) {
            $neighborCluster = $clusters[$clusterIndex + 1] ?? null;

            foreach ($clusterUsers as $user) {
                $contacts = [];

                // Strong: same cluster (40%)
                $strongCount = min(ceil($CONTACTS_PER_USER*0.4), count($clusterUsers)-1);
                $strongContacts = collect($clusterUsers)
                    ->where('id','!=',$user['id'])
                    ->random($strongCount);

                foreach($strongContacts as $c){
                    $contacts[] = $this->contactRow($user['id'],$c['mobile']);
                }

                // Medium: neighbor cluster (40%)
                $mediumCount = min(ceil($CONTACTS_PER_USER*0.4), $neighborCluster ? count($neighborCluster) : 0);
                if($neighborCluster && $mediumCount > 0){
                    $mediumContacts = collect($neighborCluster)->random($mediumCount);
                    foreach($mediumContacts as $c){
                        $contacts[] = $this->contactRow($user['id'],$c['mobile']);
                    }
                }

                // Weak: random global remaining
                while(count($contacts) < $CONTACTS_PER_USER){
                    $rand = $users[rand(0,$total-1)];
                    if($rand['id']==$user['id']) continue;
                    $contacts[] = $this->contactRow($user['id'],$rand['mobile']);
                }

                // Insert contacts
                DB::table('contacts')->insertOrIgnore($contacts);

                // Generate call logs for each contact
                $callLogs = [];
                $callStats = [];
                foreach($contacts as $c){
                    $numCalls = rand(1,$MAX_CALLS_PER_CONTACT);
                    for($i=0;$i<$numCalls;$i++){
                        $type = ['incoming','outgoing','missed'][rand(0,2)];
                        $duration = $type==='missed' ? 0 : rand(10,300);
                        $callLogs[] = [
                            'user_id' => $user['id'],
                            'mobile' => $c['contact_mobile'],
                            'normalized_mobile' => $c['normalized_mobile'],
                            'type' => $type,
                            'duration' => $duration,
                            'called_at' => now()->subDays(rand(0,30)),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        // Call log stats
                        $key = $c['normalized_mobile'];
                        if(!isset($callStats[$key])){
                            $callStats[$key] = ['total_calls'=>0,'incoming_calls'=>0,'outgoing_calls'=>0];
                        }
                        $callStats[$key]['total_calls'] += 1;
                        if($type==='incoming') $callStats[$key]['incoming_calls'] += 1;
                        if($type==='outgoing') $callStats[$key]['outgoing_calls'] += 1;
                    }
                }

                // Insert call logs in batch
                if($callLogs) DB::table('call_logs')->insert($callLogs);

                // Insert/update call_log_stats
                foreach($callStats as $mobile => $stats){
                    DB::table('call_log_stats')->updateOrInsert(
                        ['user_id'=>$user['id'],'normalized_mobile'=>$mobile],
                        [
                            'total_calls'=>$stats['total_calls'],
                            'incoming_calls'=>$stats['incoming_calls'],
                            'outgoing_calls'=>$stats['outgoing_calls'],
                            'created_at'=>now(),
                            'updated_at'=>now()
                        ]
                    );
                }
            }

            echo "Cluster {$clusterIndex} done\n";
        }

        echo "✅ Realistic network with call logs seeded successfully!\n";
    }

    private function contactRow($userId, $mobile): array
    {
        return [
            'user_id' => $userId,
            'contact_mobile' => $mobile,
            'normalized_mobile' => $mobile,
            'contact_name' => 'Contact ' . substr($mobile,-4),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}