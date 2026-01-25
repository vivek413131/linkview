<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RealNetworkSeeder extends Seeder
{
    public function run(): void
    {
        $TOTAL_USERS = 30000;   // start with 5000
        $CONTACTS_PER_USER = 100;
        $CLUSTER_SIZE = 100;

        echo "Creating users...\n";

        // 1️⃣ USERS
        $users = [];
        for ($i = 1; $i <= $TOTAL_USERS; $i++) {
            $users[] = [
                'mobile' => '9' . str_pad($i, 9, '0', STR_PAD_LEFT),
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

        echo "Building clustered contact graph...\n";

        // 2️⃣ CLUSTERS
        $clusters = array_chunk($users, $CLUSTER_SIZE);

        foreach ($clusters as $clusterIndex => $clusterUsers) {

            $neighborCluster = $clusters[$clusterIndex + 1] ?? null;

            foreach ($clusterUsers as $user) {

                $contacts = [];

                // 🟢 STRONG – same cluster (40)
                $sameCluster = collect($clusterUsers)
                    ->where('id', '!=', $user['id'])
                    ->random(min(40, count($clusterUsers)-1));

                foreach ($sameCluster as $c) {
                    $contacts[] = $this->contactRow($user['id'], $c['mobile']);
                }

                // 🟡 MEDIUM – neighbour cluster (40)
                if ($neighborCluster) {
                    $medium = collect($neighborCluster)->random(40);
                    foreach ($medium as $c) {
                        $contacts[] = $this->contactRow($user['id'], $c['mobile']);
                    }
                }

                // 🔴 WEAK – random global (20)
                for ($i = count($contacts); $i < $CONTACTS_PER_USER; $i++) {
                    $rand = $users[rand(0, $total - 1)];
                    if ($rand['id'] === $user['id']) continue;

                    $contacts[] = $this->contactRow($user['id'], $rand['mobile']);
                }

                // Insert
                DB::table('contacts')->insertOrIgnore($contacts);
            }

            echo "Cluster {$clusterIndex} done\n";
        }

        echo "✅ Realistic graph seeding completed\n";
    }

    private function contactRow($userId, $mobile): array
    {
        return [
            'user_id' => $userId,
            'contact_mobile' => $mobile,
            'normalized_mobile' => $mobile,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
