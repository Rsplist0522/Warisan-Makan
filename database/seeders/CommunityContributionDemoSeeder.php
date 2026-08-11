<?php

namespace Database\Seeders;

use App\Models\HeritageShopContribution;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class CommunityContributionDemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('username', 'admin')->first();
        $googleContributor = User::query()
            ->where('role', 'user')
            ->whereNotNull('google_id')
            ->oldest('id')
            ->first();

        $records = [
            [
                'contributor' => ['name' => 'Aisha Rahman', 'email' => 'aisha.rahman@example.test'],
                'submitted_at' => '2026-07-21 10:14:00',
                'status' => HeritageShopContribution::STATUS_PENDING_REVIEW,
                'contribution_title' => 'Hameediyah Restaurant nasi kandar heritage record',
                'shop_name' => 'Hameediyah Restaurant',
                'primary_food_category' => 'Nasi kandar / Indian Muslim',
                'establishment_year' => 1907,
                'founder_name' => 'Mohamed Thamby Rather and family',
                'founder_background' => 'Hameediyah began from Indian Muslim spice-trading roots in George Town and grew into one of Malaysia oldest nasi kandar institutions.',
                'current_owner_name' => 'Hameediyah family successors',
                'current_owner_details' => 'The restaurant continues as a multi-generation family business on Lebuh Campbell.',
                'heritage_story' => 'Operating on Lebuh Campbell since 1907, Hameediyah is closely tied to Penang nasi kandar culture and is known for nasi kandar, murtabak, briyani, and rich curry gravies.',
                'address' => '164A Lebuh Campbell',
                'city' => 'George Town',
                'state' => 'Penang',
                'postal_code' => '10100',
                'contact_number' => '+60 4-261 1095',
                'food_items' => [
                    ['name' => 'Nasi kandar', 'desc' => 'Rice served with layered curries and assorted lauk.'],
                    ['name' => 'Murtabak', 'desc' => 'Stuffed pan-fried bread associated with the shop heritage menu.'],
                ],
            ],
            [
                'contributor' => ['name' => 'Jason Lim', 'email' => 'jason.lim@example.test'],
                'submitted_at' => '2026-07-23 14:30:00',
                'status' => HeritageShopContribution::STATUS_UNDER_REVIEW,
                'review_started_at' => '2026-07-24 09:20:00',
                'contribution_title' => 'Yut Kee Hainanese kopitiam record',
                'shop_name' => 'Yut Kee Restaurant',
                'primary_food_category' => 'Hainanese kopitiam',
                'establishment_year' => 1928,
                'founder_name' => 'Lee Tai Yik',
                'founder_background' => 'Founded by Hainanese immigrant Lee Tai Yik, Yut Kee became one of Kuala Lumpur classic kopitiam names.',
                'current_owner_name' => 'Mervyn Lee and the Lee family',
                'current_owner_details' => 'The business is associated with the Lee family and later moved from Jalan Dang Wangi to Jalan Kamunting.',
                'heritage_story' => 'Yut Kee has served generations of KL diners since 1928 with Hainanese chicken chop, roti babi, kaya toast, marble cake, and other old-school kopitiam staples.',
                'address' => '1 Jalan Kamunting, Chow Kit',
                'city' => 'Kuala Lumpur',
                'state' => 'Wilayah Persekutuan Kuala Lumpur',
                'postal_code' => '50300',
                'contact_number' => '+60 3-2698 8108',
                'food_items' => [
                    ['name' => 'Hainanese chicken chop', 'desc' => 'A signature old-school chicken chop with gravy.'],
                    ['name' => 'Roti babi', 'desc' => 'A long-running house specialty.'],
                ],
            ],
            [
                'contributor' => ['name' => 'Nurul Izzati', 'email' => 'nurul.izzati@example.test'],
                'submitted_at' => '2026-07-26 11:05:00',
                'status' => HeritageShopContribution::STATUS_PENDING_REVIEW,
                'contribution_title' => 'Capital Cafe multi-racial kopitiam submission',
                'shop_name' => 'Capital Cafe',
                'primary_food_category' => 'Pork-free kopitiam / nasi padang / mee rebus',
                'establishment_year' => 1956,
                'founder_name' => 'Lin family',
                'founder_background' => 'Capital Cafe was opened by a Foochow family before Merdeka and became known as an old-school meeting place in central Kuala Lumpur.',
                'current_owner_name' => 'Lin family second generation',
                'current_owner_details' => 'The cafe is known for keeping a simple traditional kopitiam atmosphere on Jalan Tuanku Abdul Rahman.',
                'heritage_story' => 'Capital Cafe has operated in the heart of Kuala Lumpur since 1956, bringing together kopitiam drinks, nasi padang, mee rebus, rojak, fried noodles, and satay under one roof.',
                'address' => '213 Jalan Tuanku Abdul Rahman',
                'city' => 'Kuala Lumpur',
                'state' => 'Wilayah Persekutuan Kuala Lumpur',
                'postal_code' => '50100',
                'contact_number' => '+60 12-854 5046',
                'food_items' => [
                    ['name' => 'Nasi padang', 'desc' => 'Rice with assorted Indonesian-Malay dishes.'],
                    ['name' => 'Mee rebus', 'desc' => 'Yellow noodles in a thick sweet-spicy gravy.'],
                ],
            ],
            [
                'contributor' => ['name' => 'Mei Ling Tan', 'email' => 'mei.ling@example.test'],
                'submitted_at' => '2026-07-14 16:45:00',
                'status' => HeritageShopContribution::STATUS_APPROVED,
                'review_started_at' => '2026-07-15 09:35:00',
                'approved_at' => '2026-07-18 12:10:00',
                'contribution_title' => 'Sek Yuen Cantonese banquet restaurant archive',
                'shop_name' => 'Sek Yuen Restaurant',
                'primary_food_category' => 'Traditional Cantonese',
                'establishment_year' => 1948,
                'founder_name' => 'Original Sek Yuen founding family',
                'founder_background' => 'Sek Yuen was opened in post-war Kuala Lumpur and became known for classic Cantonese banquet cooking in Pudu.',
                'current_owner_name' => 'Sek Yuen family operators',
                'current_owner_details' => 'The restaurant continues to preserve vintage dining rooms and long-standing Cantonese house signatures.',
                'heritage_story' => 'Since 1948, Sek Yuen has remained a Pudu institution for Cantonese dishes such as pipa duck, eight-treasure duck, and celebratory banquet cooking.',
                'address' => '313 Jalan Pudu, Pudu',
                'city' => 'Kuala Lumpur',
                'state' => 'Wilayah Persekutuan Kuala Lumpur',
                'postal_code' => '55100',
                'contact_number' => '+60 3-9226 3383',
                'admin_feedback' => 'Core details verified for demo publication.',
                'food_items' => [
                    ['name' => 'Pipa duck', 'desc' => 'A signature Cantonese roast duck preparation.'],
                    ['name' => 'Eight-treasure duck', 'desc' => 'A banquet dish usually ordered in advance.'],
                ],
            ],
            [
                'contributor' => ['name' => 'Farid Zain', 'email' => 'farid.zain@example.test'],
                'submitted_at' => '2026-07-12 09:55:00',
                'status' => HeritageShopContribution::STATUS_REVISION_REQUIRED,
                'review_started_at' => '2026-07-13 10:15:00',
                'contribution_title' => 'Sin Yoon Loong Ipoh white coffee origin story',
                'shop_name' => 'Sin Yoon Loong',
                'primary_food_category' => 'Ipoh white coffee / Hainanese kopitiam',
                'establishment_year' => 1937,
                'founder_name' => 'Wong Poh Chew and Wong Poh Ting',
                'founder_background' => 'The Wong brothers opened a Hainanese coffee shop in Ipoh Old Town and helped popularise Ipoh white coffee.',
                'current_owner_name' => 'Wong family successors',
                'current_owner_details' => 'The kopitiam remains associated with the Wong family white coffee legacy.',
                'heritage_story' => 'Founded in 1937, Sin Yoon Loong is strongly linked with the birth and spread of Ipoh white coffee, served with kaya toast, sponge cakes, and local kopitiam fare.',
                'address' => '15A Jalan Bandar Timah',
                'city' => 'Ipoh',
                'state' => 'Perak',
                'postal_code' => '30000',
                'contact_number' => '+60 5-241 3991',
                'admin_feedback' => 'Please add clearer current-operator details and at least one supporting photo of the shopfront.',
                'food_items' => [
                    ['name' => 'Ipoh white coffee', 'desc' => 'The signature local coffee associated with the shop.'],
                    ['name' => 'Kaya toast', 'desc' => 'A classic kopitiam pairing with coffee.'],
                ],
            ],
            [
                'contributor' => ['name' => 'Priya Nair', 'email' => 'priya.nair@example.test'],
                'submitted_at' => '2026-07-10 13:25:00',
                'status' => HeritageShopContribution::STATUS_REJECTED,
                'review_started_at' => '2026-07-11 08:50:00',
                'contribution_title' => 'Durbar at FMS colonial-era restaurant note',
                'shop_name' => 'Durbar at FMS',
                'primary_food_category' => 'Hainanese heritage restaurant / bar',
                'establishment_year' => 1906,
                'founder_name' => 'Two Hainanese immigrants',
                'founder_background' => 'The FMS began as a colonial-era bar and restaurant serving European miners and planters in Ipoh.',
                'current_owner_name' => 'Seow Wee Liam',
                'current_owner_details' => 'The historic FMS premises were revived and reopened as Durbar at FMS in 2019.',
                'heritage_story' => 'Founded in 1906 and later revived as Durbar at FMS, the Ipoh landmark preserves a colonial-era dining setting with Hainanese and heritage restaurant dishes.',
                'address' => '2 Jalan Sultan Idris Shah',
                'city' => 'Ipoh',
                'state' => 'Perak',
                'postal_code' => '30000',
                'contact_number' => '+60 17-797 7115',
                'admin_feedback' => 'Rejected in demo data as a duplicate of an existing editorial draft.',
                'rejection_reason' => 'Duplicate of an existing editorial draft.',
                'food_items' => [
                    ['name' => 'Hainanese chicken chop', 'desc' => 'A heritage restaurant classic.'],
                    ['name' => 'Baked stuffed crab', 'desc' => 'One of the revived house-style dishes.'],
                ],
            ],
        ];

        foreach ($records as $record) {
            $user = $googleContributor ?? User::updateOrCreate([
                'email' => $record['contributor']['email'],
            ], [
                'name' => $record['contributor']['name'],
                'password' => Hash::make('password123'),
                'role' => 'user',
            ]);

            $status = $record['status'];
            $submittedAt = Carbon::parse($record['submitted_at']);
            $reviewStartedAt = isset($record['review_started_at']) ? Carbon::parse($record['review_started_at']) : null;
            $approvedAt = isset($record['approved_at']) ? Carbon::parse($record['approved_at']) : null;
            $updatedAt = $approvedAt ?? $reviewStartedAt ?? $submittedAt;

            $contribution = HeritageShopContribution::updateOrCreate([
                'shop_name' => $record['shop_name'],
            ], [
                'user_id' => $user->id,
                'contribution_title' => $record['contribution_title'],
                'primary_food_category' => $record['primary_food_category'],
                'establishment_year' => $record['establishment_year'],
                'founder_name' => $record['founder_name'],
                'founder_background' => $record['founder_background'],
                'current_owner_name' => $record['current_owner_name'],
                'current_owner_details' => $record['current_owner_details'],
                'heritage_story' => $record['heritage_story'],
                'food_items' => $record['food_items'],
                'contact_number' => $record['contact_number'],
                'address' => $record['address'],
                'city' => $record['city'],
                'state' => $record['state'],
                'postal_code' => $record['postal_code'],
                'status' => $status,
                'submitted_at' => $submittedAt,
                'reviewed_by_user_id' => $reviewStartedAt ? $admin?->id : null,
                'review_started_at' => $reviewStartedAt,
                'approved_by_user_id' => $approvedAt ? $admin?->id : null,
                'approved_at' => $approvedAt,
                'admin_feedback' => $record['admin_feedback'] ?? null,
                'rejection_reason' => $record['rejection_reason'] ?? null,
                'created_at' => $submittedAt->copy()->subHours(2),
                'updated_at' => $updatedAt,
            ]);
            $contribution->forceFill([
                'created_at' => $submittedAt->copy()->subHours(2),
                'updated_at' => $updatedAt,
            ])->saveQuietly();

            $contribution->versions()->delete();
            $contribution->moderationActivities()->delete();
            $contribution->recordVersion($user, 'demo_submission');

            if ($reviewStartedAt) {
                $contribution->moderationActivities()->create([
                    'actor_user_id' => $admin?->id,
                    'action' => 'start_review',
                    'from_status' => HeritageShopContribution::STATUS_PENDING_REVIEW,
                    'to_status' => HeritageShopContribution::STATUS_UNDER_REVIEW,
                    'created_at' => $reviewStartedAt,
                    'updated_at' => $reviewStartedAt,
                ]);
            }

            if (in_array($status, [
                HeritageShopContribution::STATUS_APPROVED,
                HeritageShopContribution::STATUS_REVISION_REQUIRED,
                HeritageShopContribution::STATUS_REJECTED,
            ], true)) {
                $actionAt = $approvedAt ?? $updatedAt;
                $contribution->moderationActivities()->create([
                    'actor_user_id' => $admin?->id,
                    'action' => match ($status) {
                        HeritageShopContribution::STATUS_APPROVED => 'approved',
                        HeritageShopContribution::STATUS_REVISION_REQUIRED => 'revision_requested',
                        HeritageShopContribution::STATUS_REJECTED => 'rejected',
                    },
                    'from_status' => HeritageShopContribution::STATUS_UNDER_REVIEW,
                    'to_status' => $status,
                    'comment' => $record['admin_feedback'] ?? $record['rejection_reason'] ?? null,
                    'metadata' => ['source' => 'demo_seed'],
                    'created_at' => $actionAt,
                    'updated_at' => $actionAt,
                ]);
            }
        }
    }
}
