<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $documentTypes = [
            [
                'code' => 'barangay_clearance',
                'name' => 'Barangay Clearance',
                'description' => 'Official clearance from barangay for various purposes',
                'fee_amount' => 50.00,
                'required_documents' => ['valid_id', 'proof_of_residency'],
                'processing_steps' => [
                    'Submit required documents',
                    'Verification of residency',
                    'Background check',
                    'Approval and issuance'
                ],
                'processing_days' => 3,
                'is_active' => true,
                'requires_payment' => true,
                'requires_approval' => false,
                'category' => 'certificates',
                'sort_order' => 1,
                'notes' => 'Most commonly requested document',
            ],
            [
                'code' => 'residency_certificate',
                'name' => 'Residency Certificate',
                'description' => 'Certificate proving residency in the barangay',
                'fee_amount' => 100.00,
                'required_documents' => ['valid_id', 'proof_of_residency', 'utility_bill'],
                'processing_steps' => [
                    'Submit required documents',
                    'Residency verification',
                    'Document review',
                    'Certificate issuance'
                ],
                'processing_days' => 5,
                'is_active' => true,
                'requires_payment' => true,
                'requires_approval' => false,
                'category' => 'certificates',
                'sort_order' => 2,
                'notes' => 'Required for various government transactions',
            ],
            [
                'code' => 'business_permit',
                'name' => 'Business Permit',
                'description' => 'Permit to operate business within barangay jurisdiction',
                'fee_amount' => 500.00,
                'required_documents' => ['valid_id', 'business_plan', 'location_sketch'],
                'processing_steps' => [
                    'Submit business application',
                    'Location inspection',
                    'Business plan review',
                    'Permit approval',
                    'Permit issuance'
                ],
                'processing_days' => 7,
                'is_active' => true,
                'requires_payment' => true,
                'requires_approval' => true,
                'category' => 'permits',
                'sort_order' => 3,
                'notes' => 'Requires barangay captain approval',
            ],
            [
                'code' => 'indigency_certificate',
                'name' => 'Indigency Certificate',
                'description' => 'Certificate proving indigent status for government assistance',
                'fee_amount' => 0.00,
                'required_documents' => ['valid_id', 'income_certificate'],
                'processing_steps' => [
                    'Submit required documents',
                    'Income verification',
                    'Social worker assessment',
                    'Certificate issuance'
                ],
                'processing_days' => 2,
                'is_active' => true,
                'requires_payment' => false,
                'requires_approval' => true,
                'category' => 'certificates',
                'sort_order' => 4,
                'notes' => 'Free service for indigent residents',
            ],
            [
                'code' => 'cedula',
                'name' => 'Cedula',
                'description' => 'Community tax certificate for various transactions',
                'fee_amount' => 30.00,
                'required_documents' => ['valid_id'],
                'processing_steps' => [
                    'Submit valid ID',
                    'Tax computation',
                    'Payment processing',
                    'Cedula issuance'
                ],
                'processing_days' => 1,
                'is_active' => true,
                'requires_payment' => true,
                'requires_approval' => false,
                'category' => 'taxes',
                'sort_order' => 5,
                'notes' => 'Annual community tax certificate',
            ],
            [
                'code' => 'death_certificate',
                'name' => 'Death Certificate',
                'description' => 'Official certificate of death for legal purposes',
                'fee_amount' => 150.00,
                'required_documents' => ['valid_id', 'medical_certificate', 'witness_affidavit'],
                'processing_steps' => [
                    'Submit required documents',
                    'Medical verification',
                    'Witness verification',
                    'Certificate preparation',
                    'Official issuance'
                ],
                'processing_days' => 5,
                'is_active' => true,
                'requires_payment' => true,
                'requires_approval' => true,
                'category' => 'certificates',
                'sort_order' => 6,
                'notes' => 'Requires medical and witness verification',
            ],
            [
                'code' => 'birth_certificate',
                'name' => 'Birth Certificate',
                'description' => 'Official certificate of birth for legal purposes',
                'fee_amount' => 200.00,
                'required_documents' => ['valid_id', 'hospital_record', 'parent_ids'],
                'processing_steps' => [
                    'Submit required documents',
                    'Hospital record verification',
                    'Parent verification',
                    'Certificate preparation',
                    'Official issuance'
                ],
                'processing_days' => 7,
                'is_active' => true,
                'requires_payment' => true,
                'requires_approval' => true,
                'category' => 'certificates',
                'sort_order' => 7,
                'notes' => 'Requires hospital and parent verification',
            ],
            [
                'code' => 'marriage_certificate',
                'name' => 'Marriage Certificate',
                'description' => 'Official certificate of marriage for legal purposes',
                'fee_amount' => 300.00,
                'required_documents' => ['valid_id', 'marriage_contract', 'witness_ids'],
                'processing_steps' => [
                    'Submit required documents',
                    'Marriage contract verification',
                    'Witness verification',
                    'Certificate preparation',
                    'Official issuance'
                ],
                'processing_days' => 10,
                'is_active' => true,
                'requires_payment' => true,
                'requires_approval' => true,
                'category' => 'certificates',
                'sort_order' => 8,
                'notes' => 'Requires marriage contract and witness verification',
            ],
        ];

        foreach ($documentTypes as $documentType) {
            DocumentType::updateOrCreate(
                ['code' => $documentType['code']],
                $documentType
            );
        }
    }
}
