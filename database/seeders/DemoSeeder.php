<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Users ────────────────────────────────────────────────────────────
        $userDefs = [
            ['name' => 'Admin User',    'email' => 'admin@cosmas-sentry.com',     'days' => 30],
            ['name' => 'QC Inspector',  'email' => 'inspector@cosmas-sentry.com', 'days' => 25],
            ['name' => 'Plant Manager', 'email' => 'manager@cosmas-sentry.com',   'days' => 20],
        ];

        foreach ($userDefs as $u) {
            DB::table('users')->updateOrInsert(
                ['email' => $u['email']],
                [
                    'name'              => $u['name'],
                    'email'             => $u['email'],
                    'password'          => Hash::make('password'),
                    'email_verified_at' => now(),
                    'created_at'        => now()->subDays($u['days']),
                    'updated_at'        => now()->subDays($u['days']),
                ]
            );
        }

        $userIds = DB::table('users')
            ->whereIn('email', array_column($userDefs, 'email'))
            ->pluck('id')
            ->toArray();

        // ── 2. Wipe previous demo rows ───────────────────────────────────────────
        DB::table('inspections')
            ->where('image_path', 'LIKE', 'demo/%')
            ->delete();

        // ── 3. Inspection dataset ────────────────────────────────────────────────
        $rows = [
            // pass (12)
            ['pf'=>'pass','defect_type'=>'None — Scalpel Blade #10',         'conf'=>0.97,'days'=>28,'action'=>'APPROVE','sev'=>'LOW'],
            ['pf'=>'pass','defect_type'=>'None — Curved Scissors 7in',        'conf'=>0.95,'days'=>27,'action'=>'APPROVE','sev'=>'LOW'],
            ['pf'=>'pass','defect_type'=>'None — Forceps Standard',           'conf'=>0.99,'days'=>26,'action'=>'APPROVE','sev'=>'LOW'],
            ['pf'=>'pass','defect_type'=>'None — Needle Holder',              'conf'=>0.96,'days'=>25,'action'=>'APPROVE','sev'=>'LOW'],
            ['pf'=>'pass','defect_type'=>'None — Straight Scissors 6in',      'conf'=>0.98,'days'=>24,'action'=>'APPROVE','sev'=>'LOW'],
            ['pf'=>'pass','defect_type'=>'None — Tissue Forceps',             'conf'=>0.94,'days'=>23,'action'=>'APPROVE','sev'=>'LOW'],
            ['pf'=>'pass','defect_type'=>'None — Retractor Small',            'conf'=>0.97,'days'=>22,'action'=>'APPROVE','sev'=>'LOW'],
            ['pf'=>'pass','defect_type'=>'None — Hemostatic Forceps',         'conf'=>0.93,'days'=>21,'action'=>'APPROVE','sev'=>'LOW'],
            ['pf'=>'pass','defect_type'=>'None — Scalpel Blade #10',          'conf'=>0.98,'days'=>20,'action'=>'APPROVE','sev'=>'LOW'],
            ['pf'=>'pass','defect_type'=>'None — Curved Scissors 7in',        'conf'=>0.96,'days'=>19,'action'=>'APPROVE','sev'=>'LOW'],
            ['pf'=>'pass','defect_type'=>'None — Forceps Standard',           'conf'=>0.95,'days'=>18,'action'=>'APPROVE','sev'=>'LOW'],
            ['pf'=>'pass','defect_type'=>'None — Needle Holder',              'conf'=>0.97,'days'=>17,'action'=>'APPROVE','sev'=>'LOW'],
            // flagged (7)
            ['pf'=>'flagged','defect_type'=>'Surface scratch — Straight Scissors 6in','conf'=>0.81,'days'=>16,'action'=>'REVIEW','sev'=>'MEDIUM'],
            ['pf'=>'flagged','defect_type'=>'Minor discoloration — Retractor Small',   'conf'=>0.76,'days'=>14,'action'=>'REVIEW','sev'=>'MEDIUM'],
            ['pf'=>'flagged','defect_type'=>'Dimensional variance — Tissue Forceps',   'conf'=>0.83,'days'=>11,'action'=>'REVIEW','sev'=>'MEDIUM'],
            ['pf'=>'flagged','defect_type'=>'Finish irregularity — Hemostatic Forceps','conf'=>0.79,'days'=>8, 'action'=>'REVIEW','sev'=>'MEDIUM'],
            ['pf'=>'flagged','defect_type'=>'Surface scratch — Scalpel Blade #10',     'conf'=>0.84,'days'=>5, 'action'=>'REVIEW','sev'=>'MEDIUM'],
            ['pf'=>'flagged','defect_type'=>'Oxidation — Curved Scissors 7in',         'conf'=>0.77,'days'=>3, 'action'=>'REVIEW','sev'=>'MEDIUM'],
            ['pf'=>'flagged','defect_type'=>'Tip variance — Forceps Standard',         'conf'=>0.82,'days'=>2, 'action'=>'REVIEW','sev'=>'MEDIUM'],
            // fail (6)
            ['pf'=>'fail','defect_type'=>'Fracture — Scalpel Blade #10',       'conf'=>0.91,'days'=>15,'action'=>'REJECT','sev'=>'HIGH'],
            ['pf'=>'fail','defect_type'=>'Corrosion — Curved Scissors 7in',    'conf'=>0.88,'days'=>13,'action'=>'REJECT','sev'=>'HIGH'],
            ['pf'=>'fail','defect_type'=>'Deformation — Needle Holder',        'conf'=>0.93,'days'=>10,'action'=>'REJECT','sev'=>'HIGH'],
            ['pf'=>'fail','defect_type'=>'Fracture — Straight Scissors 6in',   'conf'=>0.89,'days'=>7, 'action'=>'REJECT','sev'=>'HIGH'],
            ['pf'=>'fail','defect_type'=>'Contamination — Retractor Small',    'conf'=>0.92,'days'=>4, 'action'=>'REJECT','sev'=>'HIGH'],
            ['pf'=>'fail','defect_type'=>'Corrosion — Tissue Forceps',         'conf'=>0.87,'days'=>1, 'action'=>'REJECT','sev'=>'HIGH'],
        ];

        $claudeTexts = [
            'LOW'    => 'No defects detected. Instrument meets all visual QC criteria. Surface finish, geometry, and sterile contact surfaces are within specification. Approved for sterilization and packaging.',
            'MEDIUM' => [
                'Minor surface irregularity detected. Within tolerance but approaching limit. Secondary visual inspection recommended before release. Document per FDA 21 CFR 820.100 CAPA requirements.',
                'Slight dimensional variance on instrument tip. Functional integrity appears intact. QC hold recommended pending manual verification. Document in batch record.',
                'Surface finish irregularity at joint. No structural compromise detected. Flag for batch tracking and manual disposition. CAPA documentation required.',
            ],
            'HIGH' => [
                'Visible fracture line detected on cutting edge. Immediate rejection required. Do not ship — patient safety risk. Escalate to QC manager. FDA 21 CFR 820.90 nonconforming product procedure required.',
                'Severe corrosion pitting detected. Structural integrity compromised. REJECT and quarantine batch. Initiate supplier audit. This is a Class II defect — FDA reportable if shipped.',
                'Contamination detected on sterile contact surface. Full batch hold required. Sterility cannot be assured. FDA Medical Device Report (MDR) required under 21 CFR 803 if instrument reached patient.',
                'Blade deformation outside tolerance. Cannot be reworked. Reject and quarantine. Notify production supervisor and initiate root cause analysis per CAPA procedure.',
            ],
        ];

        $regulatoryMap = [
            'LOW'    => 'Meets FDA 21 CFR 820.80 acceptance criteria. Cleared for release.',
            'MEDIUM' => 'Document per FDA 21 CFR 820.100 CAPA requirements. Hold pending manual disposition.',
            'HIGH'   => 'FDA 21 CFR 820.90 nonconforming product procedure required. Do not release.',
        ];

        $yoloClasses = [
            ['class_id'=>0,'class_name'=>'bisturi',       'display_name'=>'Scalpel'],
            ['class_id'=>1,'class_name'=>'pinzas',        'display_name'=>'Forceps/Tweezers'],
            ['class_id'=>2,'class_name'=>'tijeras_curvas','display_name'=>'Curved Scissors'],
            ['class_id'=>3,'class_name'=>'tijeras_rectas','display_name'=>'Straight Scissors'],
        ];

        $userIndex = 0;
        foreach ($rows as $idx => $row) {
            $userId    = $userIds[$userIndex % count($userIds)];
            $userIndex++;
            $sev       = $row['sev'];
            $createdAt = Carbon::now()->subDays($row['days'])->subHours(rand(1, 8));
            $yc        = $yoloClasses[array_rand($yoloClasses)];
            $nDet      = ($sev === 'HIGH') ? rand(2, 4) : (($sev === 'MEDIUM') ? 1 : 1);

            $detections = [];
            for ($d = 0; $d < $nDet; $d++) {
                $detections[] = array_merge($yc, [
                    'confidence' => round($row['conf'] - ($d * 0.04), 4),
                    'bbox'       => [rand(40,180), rand(40,180), rand(300,500), rand(300,500)],
                    'severity'   => $sev,
                ]);
            }

            $claudeText = ($sev === 'LOW')
                ? $claudeTexts['LOW']
                : $claudeTexts[$sev][array_rand($claudeTexts[$sev])];

            // bounding_box: first detection bbox or null
            $bbox = $detections[0]['bbox'] ?? null;

            DB::table('inspections')->insert([
                'user_id'            => $userId,
                'image_path'         => 'demo/instrument_' . ($idx + 1) . '.jpg',
                'defect_type'        => $row['defect_type'],
                'confidence'         => $row['conf'],
                'pass_fail'          => $row['pf'],
                'claude_reasoning'   => $claudeText,
                'bounding_box'       => json_encode($bbox),
                'yolo_detections'    => json_encode($detections),
                'yolo_count'         => count($detections),
                'yolo_model'         => 'yolov8n_real_finetuned.pt',
                'inference_ms'       => rand(180, 420),
                'recommended_action' => $row['action'],
                'regulatory_note'    => $regulatoryMap[$sev],
                'created_at'         => $createdAt,
                'updated_at'         => $createdAt,
            ]);
        }

        $this->command->info('Demo seeded: ' . count($userIds) . ' users, ' . count($rows) . ' inspections');
        $this->command->info('Breakdown: 12 passed | 7 flagged | 6 failed');
        $this->command->info('Login: admin@cosmas-sentry.com / password');
    }
}
