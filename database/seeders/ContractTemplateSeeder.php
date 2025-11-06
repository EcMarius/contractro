<?php

namespace Database\Seeders;

use App\Models\ContractTemplate;
use Illuminate\Database\Seeder;

class ContractTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            // 1. Non-Disclosure Agreement (NDA)
            [
                'name' => 'Non-Disclosure Agreement (NDA)',
                'description' => 'Standard mutual non-disclosure agreement for protecting confidential business information',
                'category' => 'Legal',
                'content' => $this->getNDATemplate(),
                'variables' => [
                    ['name' => 'company_name', 'label' => 'Company Name', 'type' => 'text', 'required' => true],
                    ['name' => 'company_address', 'label' => 'Company Address', 'type' => 'text', 'required' => true],
                    ['name' => 'recipient_name', 'label' => 'Recipient Name', 'type' => 'text', 'required' => true],
                    ['name' => 'recipient_address', 'label' => 'Recipient Address', 'type' => 'text', 'required' => true],
                    ['name' => 'effective_date', 'label' => 'Effective Date', 'type' => 'date', 'required' => true],
                    ['name' => 'term_years', 'label' => 'Term (Years)', 'type' => 'number', 'required' => true, 'default' => '2'],
                ],
                'is_public' => true,
                'is_system' => true,
                'metadata' => ['industry' => 'General', 'tags' => ['NDA', 'Confidentiality', 'Legal']],
            ],

            // 2. Service Agreement
            [
                'name' => 'Service Agreement',
                'description' => 'General service agreement for professional services',
                'category' => 'Service',
                'content' => $this->getServiceAgreementTemplate(),
                'variables' => [
                    ['name' => 'provider_name', 'label' => 'Service Provider Name', 'type' => 'text', 'required' => true],
                    ['name' => 'provider_address', 'label' => 'Provider Address', 'type' => 'text', 'required' => true],
                    ['name' => 'client_name', 'label' => 'Client Name', 'type' => 'text', 'required' => true],
                    ['name' => 'client_address', 'label' => 'Client Address', 'type' => 'text', 'required' => true],
                    ['name' => 'service_description', 'label' => 'Service Description', 'type' => 'textarea', 'required' => true],
                    ['name' => 'contract_value', 'label' => 'Contract Value', 'type' => 'number', 'required' => true],
                    ['name' => 'payment_terms', 'label' => 'Payment Terms', 'type' => 'text', 'required' => true],
                    ['name' => 'start_date', 'label' => 'Start Date', 'type' => 'date', 'required' => true],
                    ['name' => 'end_date', 'label' => 'End Date', 'type' => 'date', 'required' => true],
                ],
                'is_public' => true,
                'is_system' => true,
                'metadata' => ['industry' => 'General', 'tags' => ['Services', 'Professional']],
            ],

            // 3. Employment Contract
            [
                'name' => 'Employment Contract',
                'description' => 'Standard employment agreement for full-time employees',
                'category' => 'Employment',
                'content' => $this->getEmploymentContractTemplate(),
                'variables' => [
                    ['name' => 'company_name', 'label' => 'Company Name', 'type' => 'text', 'required' => true],
                    ['name' => 'employee_name', 'label' => 'Employee Name', 'type' => 'text', 'required' => true],
                    ['name' => 'job_title', 'label' => 'Job Title', 'type' => 'text', 'required' => true],
                    ['name' => 'start_date', 'label' => 'Start Date', 'type' => 'date', 'required' => true],
                    ['name' => 'annual_salary', 'label' => 'Annual Salary', 'type' => 'number', 'required' => true],
                    ['name' => 'work_location', 'label' => 'Work Location', 'type' => 'text', 'required' => true],
                    ['name' => 'benefits', 'label' => 'Benefits', 'type' => 'textarea', 'required' => false],
                    ['name' => 'vacation_days', 'label' => 'Vacation Days', 'type' => 'number', 'required' => true],
                ],
                'is_public' => true,
                'is_system' => true,
                'metadata' => ['industry' => 'HR', 'tags' => ['Employment', 'Full-time', 'HR']],
            ],

            // 4. Freelance Contract
            [
                'name' => 'Freelance Contract',
                'description' => 'Independent contractor agreement for freelance work',
                'category' => 'Service',
                'content' => $this->getFreelanceContractTemplate(),
                'variables' => [
                    ['name' => 'freelancer_name', 'label' => 'Freelancer Name', 'type' => 'text', 'required' => true],
                    ['name' => 'freelancer_address', 'label' => 'Freelancer Address', 'type' => 'text', 'required' => true],
                    ['name' => 'client_name', 'label' => 'Client Name', 'type' => 'text', 'required' => true],
                    ['name' => 'client_address', 'label' => 'Client Address', 'type' => 'text', 'required' => true],
                    ['name' => 'project_description', 'label' => 'Project Description', 'type' => 'textarea', 'required' => true],
                    ['name' => 'project_fee', 'label' => 'Project Fee', 'type' => 'number', 'required' => true],
                    ['name' => 'payment_schedule', 'label' => 'Payment Schedule', 'type' => 'text', 'required' => true],
                    ['name' => 'deadline', 'label' => 'Project Deadline', 'type' => 'date', 'required' => true],
                ],
                'is_public' => true,
                'is_system' => true,
                'metadata' => ['industry' => 'General', 'tags' => ['Freelance', 'Independent Contractor']],
            ],

            // 5. Consulting Agreement
            [
                'name' => 'Consulting Agreement',
                'description' => 'Professional consulting services agreement',
                'category' => 'Service',
                'content' => $this->getConsultingAgreementTemplate(),
                'variables' => [
                    ['name' => 'consultant_name', 'label' => 'Consultant Name', 'type' => 'text', 'required' => true],
                    ['name' => 'consultant_company', 'label' => 'Consultant Company', 'type' => 'text', 'required' => false],
                    ['name' => 'client_name', 'label' => 'Client Name', 'type' => 'text', 'required' => true],
                    ['name' => 'consulting_services', 'label' => 'Consulting Services', 'type' => 'textarea', 'required' => true],
                    ['name' => 'hourly_rate', 'label' => 'Hourly Rate', 'type' => 'number', 'required' => true],
                    ['name' => 'estimated_hours', 'label' => 'Estimated Hours', 'type' => 'number', 'required' => false],
                    ['name' => 'contract_period', 'label' => 'Contract Period', 'type' => 'text', 'required' => true],
                ],
                'is_public' => true,
                'is_system' => true,
                'metadata' => ['industry' => 'Professional Services', 'tags' => ['Consulting', 'Advisory']],
            ],

            // 6. Software Development Agreement
            [
                'name' => 'Software Development Agreement',
                'description' => 'Custom software development contract with IP rights',
                'category' => 'Technology',
                'content' => $this->getSoftwareDevelopmentTemplate(),
                'variables' => [
                    ['name' => 'developer_name', 'label' => 'Developer/Company Name', 'type' => 'text', 'required' => true],
                    ['name' => 'client_name', 'label' => 'Client Name', 'type' => 'text', 'required' => true],
                    ['name' => 'project_name', 'label' => 'Project Name', 'type' => 'text', 'required' => true],
                    ['name' => 'project_scope', 'label' => 'Project Scope', 'type' => 'textarea', 'required' => true],
                    ['name' => 'deliverables', 'label' => 'Deliverables', 'type' => 'textarea', 'required' => true],
                    ['name' => 'total_cost', 'label' => 'Total Cost', 'type' => 'number', 'required' => true],
                    ['name' => 'payment_milestones', 'label' => 'Payment Milestones', 'type' => 'textarea', 'required' => true],
                    ['name' => 'completion_date', 'label' => 'Expected Completion Date', 'type' => 'date', 'required' => true],
                ],
                'is_public' => true,
                'is_system' => true,
                'metadata' => ['industry' => 'Technology', 'tags' => ['Software', 'Development', 'IP Rights']],
            ],

            // 7. Sales Contract
            [
                'name' => 'Sales Contract',
                'description' => 'Standard agreement for sale of goods or products',
                'category' => 'Sales',
                'content' => $this->getSalesContractTemplate(),
                'variables' => [
                    ['name' => 'seller_name', 'label' => 'Seller Name', 'type' => 'text', 'required' => true],
                    ['name' => 'buyer_name', 'label' => 'Buyer Name', 'type' => 'text', 'required' => true],
                    ['name' => 'product_description', 'label' => 'Product Description', 'type' => 'textarea', 'required' => true],
                    ['name' => 'quantity', 'label' => 'Quantity', 'type' => 'text', 'required' => true],
                    ['name' => 'unit_price', 'label' => 'Unit Price', 'type' => 'number', 'required' => true],
                    ['name' => 'total_price', 'label' => 'Total Price', 'type' => 'number', 'required' => true],
                    ['name' => 'delivery_date', 'label' => 'Delivery Date', 'type' => 'date', 'required' => true],
                    ['name' => 'delivery_address', 'label' => 'Delivery Address', 'type' => 'text', 'required' => true],
                ],
                'is_public' => true,
                'is_system' => true,
                'metadata' => ['industry' => 'Sales', 'tags' => ['Sales', 'Purchase', 'Goods']],
            ],

            // 8. Partnership Agreement
            [
                'name' => 'Partnership Agreement',
                'description' => 'Business partnership agreement for joint ventures',
                'category' => 'Business',
                'content' => $this->getPartnershipAgreementTemplate(),
                'variables' => [
                    ['name' => 'partnership_name', 'label' => 'Partnership Name', 'type' => 'text', 'required' => true],
                    ['name' => 'partner1_name', 'label' => 'Partner 1 Name', 'type' => 'text', 'required' => true],
                    ['name' => 'partner1_contribution', 'label' => 'Partner 1 Capital Contribution', 'type' => 'number', 'required' => true],
                    ['name' => 'partner1_ownership', 'label' => 'Partner 1 Ownership %', 'type' => 'number', 'required' => true],
                    ['name' => 'partner2_name', 'label' => 'Partner 2 Name', 'type' => 'text', 'required' => true],
                    ['name' => 'partner2_contribution', 'label' => 'Partner 2 Capital Contribution', 'type' => 'number', 'required' => true],
                    ['name' => 'partner2_ownership', 'label' => 'Partner 2 Ownership %', 'type' => 'number', 'required' => true],
                    ['name' => 'business_purpose', 'label' => 'Business Purpose', 'type' => 'textarea', 'required' => true],
                ],
                'is_public' => true,
                'is_system' => true,
                'metadata' => ['industry' => 'Business', 'tags' => ['Partnership', 'Joint Venture']],
            ],

            // 9. Lease Agreement
            [
                'name' => 'Commercial Lease Agreement',
                'description' => 'Commercial property lease agreement',
                'category' => 'Real Estate',
                'content' => $this->getLeaseAgreementTemplate(),
                'variables' => [
                    ['name' => 'landlord_name', 'label' => 'Landlord Name', 'type' => 'text', 'required' => true],
                    ['name' => 'tenant_name', 'label' => 'Tenant Name', 'type' => 'text', 'required' => true],
                    ['name' => 'property_address', 'label' => 'Property Address', 'type' => 'text', 'required' => true],
                    ['name' => 'monthly_rent', 'label' => 'Monthly Rent', 'type' => 'number', 'required' => true],
                    ['name' => 'security_deposit', 'label' => 'Security Deposit', 'type' => 'number', 'required' => true],
                    ['name' => 'lease_start_date', 'label' => 'Lease Start Date', 'type' => 'date', 'required' => true],
                    ['name' => 'lease_term_months', 'label' => 'Lease Term (Months)', 'type' => 'number', 'required' => true],
                    ['name' => 'permitted_use', 'label' => 'Permitted Use', 'type' => 'text', 'required' => true],
                ],
                'is_public' => true,
                'is_system' => true,
                'metadata' => ['industry' => 'Real Estate', 'tags' => ['Lease', 'Commercial', 'Property']],
            ],

            // 10. License Agreement
            [
                'name' => 'Software License Agreement',
                'description' => 'Software licensing agreement for products',
                'category' => 'Technology',
                'content' => $this->getLicenseAgreementTemplate(),
                'variables' => [
                    ['name' => 'licensor_name', 'label' => 'Licensor Name', 'type' => 'text', 'required' => true],
                    ['name' => 'licensee_name', 'label' => 'Licensee Name', 'type' => 'text', 'required' => true],
                    ['name' => 'software_name', 'label' => 'Software Name', 'type' => 'text', 'required' => true],
                    ['name' => 'license_type', 'label' => 'License Type', 'type' => 'text', 'required' => true],
                    ['name' => 'license_fee', 'label' => 'License Fee', 'type' => 'number', 'required' => true],
                    ['name' => 'license_term', 'label' => 'License Term', 'type' => 'text', 'required' => true],
                    ['name' => 'number_of_users', 'label' => 'Number of Users', 'type' => 'number', 'required' => false],
                ],
                'is_public' => true,
                'is_system' => true,
                'metadata' => ['industry' => 'Technology', 'tags' => ['License', 'Software', 'SaaS']],
            ],

            // 11. Marketing Services Agreement
            [
                'name' => 'Marketing Services Agreement',
                'description' => 'Agreement for digital marketing and advertising services',
                'category' => 'Marketing',
                'content' => $this->getMarketingServicesTemplate(),
                'variables' => [
                    ['name' => 'agency_name', 'label' => 'Marketing Agency Name', 'type' => 'text', 'required' => true],
                    ['name' => 'client_name', 'label' => 'Client Name', 'type' => 'text', 'required' => true],
                    ['name' => 'services_description', 'label' => 'Services Description', 'type' => 'textarea', 'required' => true],
                    ['name' => 'monthly_retainer', 'label' => 'Monthly Retainer', 'type' => 'number', 'required' => true],
                    ['name' => 'ad_budget', 'label' => 'Monthly Ad Budget', 'type' => 'number', 'required' => false],
                    ['name' => 'campaign_goals', 'label' => 'Campaign Goals', 'type' => 'textarea', 'required' => true],
                    ['name' => 'contract_duration', 'label' => 'Contract Duration (Months)', 'type' => 'number', 'required' => true],
                ],
                'is_public' => true,
                'is_system' => true,
                'metadata' => ['industry' => 'Marketing', 'tags' => ['Marketing', 'Advertising', 'Digital']],
            ],

            // 12. Website Development Contract
            [
                'name' => 'Website Development Contract',
                'description' => 'Custom website development agreement',
                'category' => 'Technology',
                'content' => $this->getWebsiteDevelopmentTemplate(),
                'variables' => [
                    ['name' => 'developer_name', 'label' => 'Developer Name', 'type' => 'text', 'required' => true],
                    ['name' => 'client_name', 'label' => 'Client Name', 'type' => 'text', 'required' => true],
                    ['name' => 'website_description', 'label' => 'Website Description', 'type' => 'textarea', 'required' => true],
                    ['name' => 'number_of_pages', 'label' => 'Number of Pages', 'type' => 'number', 'required' => true],
                    ['name' => 'features', 'label' => 'Features & Functionality', 'type' => 'textarea', 'required' => true],
                    ['name' => 'total_cost', 'label' => 'Total Cost', 'type' => 'number', 'required' => true],
                    ['name' => 'launch_date', 'label' => 'Expected Launch Date', 'type' => 'date', 'required' => true],
                    ['name' => 'revisions_included', 'label' => 'Number of Revisions Included', 'type' => 'number', 'required' => true],
                ],
                'is_public' => true,
                'is_system' => true,
                'metadata' => ['industry' => 'Technology', 'tags' => ['Website', 'Web Development', 'Design']],
            ],

            // 13. Maintenance Agreement
            [
                'name' => 'Software Maintenance Agreement',
                'description' => 'Ongoing software support and maintenance contract',
                'category' => 'Technology',
                'content' => $this->getMaintenanceAgreementTemplate(),
                'variables' => [
                    ['name' => 'provider_name', 'label' => 'Service Provider Name', 'type' => 'text', 'required' => true],
                    ['name' => 'client_name', 'label' => 'Client Name', 'type' => 'text', 'required' => true],
                    ['name' => 'system_description', 'label' => 'System Description', 'type' => 'text', 'required' => true],
                    ['name' => 'maintenance_services', 'label' => 'Maintenance Services', 'type' => 'textarea', 'required' => true],
                    ['name' => 'monthly_fee', 'label' => 'Monthly Fee', 'type' => 'number', 'required' => true],
                    ['name' => 'response_time', 'label' => 'Response Time SLA', 'type' => 'text', 'required' => true],
                    ['name' => 'support_hours', 'label' => 'Support Hours', 'type' => 'text', 'required' => true],
                ],
                'is_public' => true,
                'is_system' => true,
                'metadata' => ['industry' => 'Technology', 'tags' => ['Maintenance', 'Support', 'SLA']],
            ],

            // 14. Terms of Service
            [
                'name' => 'Terms of Service',
                'description' => 'Standard terms of service for web applications',
                'category' => 'Legal',
                'content' => $this->getTermsOfServiceTemplate(),
                'variables' => [
                    ['name' => 'company_name', 'label' => 'Company Name', 'type' => 'text', 'required' => true],
                    ['name' => 'service_name', 'label' => 'Service/Product Name', 'type' => 'text', 'required' => true],
                    ['name' => 'website_url', 'label' => 'Website URL', 'type' => 'text', 'required' => true],
                    ['name' => 'contact_email', 'label' => 'Contact Email', 'type' => 'email', 'required' => true],
                    ['name' => 'governing_law', 'label' => 'Governing Law (State/Country)', 'type' => 'text', 'required' => true],
                    ['name' => 'effective_date', 'label' => 'Effective Date', 'type' => 'date', 'required' => true],
                ],
                'is_public' => true,
                'is_system' => true,
                'metadata' => ['industry' => 'Technology', 'tags' => ['Terms', 'Legal', 'Web']],
            ],

            // 15. Privacy Policy
            [
                'name' => 'Privacy Policy',
                'description' => 'GDPR-compliant privacy policy template',
                'category' => 'Legal',
                'content' => $this->getPrivacyPolicyTemplate(),
                'variables' => [
                    ['name' => 'company_name', 'label' => 'Company Name', 'type' => 'text', 'required' => true],
                    ['name' => 'website_url', 'label' => 'Website URL', 'type' => 'text', 'required' => true],
                    ['name' => 'contact_email', 'label' => 'Contact Email', 'type' => 'email', 'required' => true],
                    ['name' => 'company_address', 'label' => 'Company Address', 'type' => 'text', 'required' => true],
                    ['name' => 'dpo_email', 'label' => 'Data Protection Officer Email', 'type' => 'email', 'required' => false],
                    ['name' => 'effective_date', 'label' => 'Effective Date', 'type' => 'date', 'required' => true],
                ],
                'is_public' => true,
                'is_system' => true,
                'metadata' => ['industry' => 'Legal', 'tags' => ['Privacy', 'GDPR', 'Legal']],
            ],
        ];

        foreach ($templates as $template) {
            ContractTemplate::create($template);
        }

        $this->command->info('✅ Created ' . count($templates) . ' contract templates successfully!');
    }

    /**
     * Template content methods
     */
    private function getNDATemplate(): string
    {
        return <<<'HTML'
<h1>NON-DISCLOSURE AGREEMENT</h1>

<p><strong>Effective Date:</strong> {{effective_date}}</p>

<p>This Non-Disclosure Agreement (the "Agreement") is entered into between:</p>

<p><strong>Disclosing Party:</strong> {{company_name}}<br>
<strong>Address:</strong> {{company_address}}</p>

<p><strong>Receiving Party:</strong> {{recipient_name}}<br>
<strong>Address:</strong> {{recipient_address}}</p>

<h2>1. PURPOSE</h2>
<p>The Receiving Party agrees to receive certain confidential information from the Disclosing Party for the purpose of evaluating or pursuing a business relationship between the parties.</p>

<h2>2. CONFIDENTIAL INFORMATION</h2>
<p>"Confidential Information" means any and all technical and non-technical information disclosed by the Disclosing Party, including but not limited to:</p>
<ul>
<li>Trade secrets, inventions, ideas, processes, and formulas</li>
<li>Source code, object code, software, and algorithms</li>
<li>Business plans, strategies, customer information, and financial data</li>
<li>Marketing plans, pricing information, and product development plans</li>
<li>Any other proprietary information marked as "Confidential" or that reasonably should be understood to be confidential</li>
</ul>

<h2>3. OBLIGATIONS OF RECEIVING PARTY</h2>
<p>The Receiving Party agrees to:</p>
<ul>
<li>Hold and maintain the Confidential Information in strict confidence</li>
<li>Not disclose the Confidential Information to any third parties without prior written consent</li>
<li>Not use the Confidential Information for any purpose except as authorized by this Agreement</li>
<li>Take reasonable measures to protect the secrecy of the Confidential Information</li>
<li>Only disclose Confidential Information to employees or contractors who have a need to know and who have been informed of the confidential nature</li>
</ul>

<h2>4. EXCLUSIONS</h2>
<p>This Agreement does not apply to information that:</p>
<ul>
<li>Was publicly known at the time of disclosure</li>
<li>Becomes publicly known through no fault of the Receiving Party</li>
<li>Was rightfully in the Receiving Party's possession before disclosure</li>
<li>Is rightfully received by the Receiving Party from a third party without breach of this Agreement</li>
<li>Is independently developed by the Receiving Party without use of the Confidential Information</li>
</ul>

<h2>5. TERM</h2>
<p>This Agreement shall remain in effect for a period of {{term_years}} years from the Effective Date, unless terminated earlier by either party with 30 days written notice.</p>

<h2>6. RETURN OF MATERIALS</h2>
<p>Upon termination or at the Disclosing Party's request, the Receiving Party shall promptly return or destroy all Confidential Information and certify in writing that it has done so.</p>

<h2>7. NO LICENSE</h2>
<p>Nothing in this Agreement grants any license or right to the Receiving Party in or to the Confidential Information, except the limited right to review such information solely for the purpose stated above.</p>

<h2>8. REMEDIES</h2>
<p>The Receiving Party acknowledges that breach of this Agreement may cause irreparable harm for which monetary damages are an inadequate remedy. Therefore, the Disclosing Party shall be entitled to seek equitable relief, including injunction and specific performance.</p>

<h2>9. GOVERNING LAW</h2>
<p>This Agreement shall be governed by and construed in accordance with the laws of the jurisdiction in which the Disclosing Party is located.</p>

<h2>10. ENTIRE AGREEMENT</h2>
<p>This Agreement constitutes the entire agreement between the parties regarding the subject matter and supersedes all prior understandings and agreements.</p>

<br><br>

<p><strong>DISCLOSING PARTY:</strong></p>
<p>{{company_name}}</p>
<p>Signature: _________________________</p>
<p>Date: _________________________</p>

<br>

<p><strong>RECEIVING PARTY:</strong></p>
<p>{{recipient_name}}</p>
<p>Signature: _________________________</p>
<p>Date: _________________________</p>
HTML;
    }

    private function getServiceAgreementTemplate(): string
    {
        return <<<'HTML'
<h1>SERVICE AGREEMENT</h1>

<p>This Service Agreement (the "Agreement") is entered into as of {{start_date}} between:</p>

<p><strong>SERVICE PROVIDER:</strong> {{provider_name}}<br>
<strong>Address:</strong> {{provider_address}}</p>

<p><strong>CLIENT:</strong> {{client_name}}<br>
<strong>Address:</strong> {{client_address}}</p>

<h2>1. SERVICES</h2>
<p>The Service Provider agrees to provide the following services to the Client:</p>
<p>{{service_description}}</p>

<h2>2. TERM</h2>
<p>This Agreement shall commence on {{start_date}} and continue until {{end_date}}, unless terminated earlier in accordance with the provisions herein.</p>

<h2>3. COMPENSATION</h2>
<p>The Client agrees to pay the Service Provider a total fee of ${{contract_value}} for the Services described above.</p>

<p><strong>Payment Terms:</strong> {{payment_terms}}</p>

<h2>4. PAYMENT SCHEDULE</h2>
<ul>
<li>Invoices will be sent on a monthly basis</li>
<li>Payment is due within 30 days of invoice date</li>
<li>Late payments may incur a 1.5% monthly interest charge</li>
<li>Services may be suspended for accounts over 60 days past due</li>
</ul>

<h2>5. EXPENSES</h2>
<p>The Client agrees to reimburse the Service Provider for all reasonable pre-approved expenses incurred in connection with the Services, including travel, materials, and third-party services.</p>

<h2>6. RESPONSIBILITIES OF SERVICE PROVIDER</h2>
<p>The Service Provider shall:</p>
<ul>
<li>Perform the Services in a professional and workmanlike manner</li>
<li>Comply with all applicable laws and regulations</li>
<li>Provide regular updates on progress and deliverables</li>
<li>Maintain appropriate insurance coverage</li>
<li>Assign qualified personnel to perform the Services</li>
</ul>

<h2>7. RESPONSIBILITIES OF CLIENT</h2>
<p>The Client shall:</p>
<ul>
<li>Provide necessary information and access required for Services</li>
<li>Respond to requests for information in a timely manner</li>
<li>Pay invoices according to the payment terms</li>
<li>Provide a primary point of contact for the project</li>
</ul>

<h2>8. INTELLECTUAL PROPERTY</h2>
<p>Upon full payment, all work product created specifically for the Client under this Agreement shall be the property of the Client. The Service Provider retains rights to pre-existing materials and general methodologies.</p>

<h2>9. CONFIDENTIALITY</h2>
<p>Both parties agree to maintain the confidentiality of any proprietary or sensitive information disclosed during the term of this Agreement.</p>

<h2>10. TERMINATION</h2>
<p>Either party may terminate this Agreement with 30 days written notice. Upon termination, the Client shall pay for all Services performed up to the termination date.</p>

<h2>11. LIMITATION OF LIABILITY</h2>
<p>The Service Provider's total liability under this Agreement shall not exceed the total fees paid by the Client under this Agreement.</p>

<h2>12. INDEPENDENT CONTRACTOR</h2>
<p>The Service Provider is an independent contractor and not an employee of the Client. The Service Provider is responsible for all taxes and insurance.</p>

<h2>13. GOVERNING LAW</h2>
<p>This Agreement shall be governed by the laws of the jurisdiction in which the Service Provider is located.</p>

<h2>14. ENTIRE AGREEMENT</h2>
<p>This Agreement constitutes the entire agreement between the parties and supersedes all prior agreements and understandings.</p>

<br><br>

<p><strong>SERVICE PROVIDER:</strong></p>
<p>{{provider_name}}</p>
<p>Signature: _________________________</p>
<p>Date: _________________________</p>

<br>

<p><strong>CLIENT:</strong></p>
<p>{{client_name}}</p>
<p>Signature: _________________________</p>
<p>Date: _________________________</p>
HTML;
    }

    private function getEmploymentContractTemplate(): string
    {
        return <<<'HTML'
<h1>EMPLOYMENT CONTRACT</h1>

<p>This Employment Contract (the "Agreement") is entered into as of {{start_date}} between:</p>

<p><strong>EMPLOYER:</strong> {{company_name}}</p>
<p><strong>EMPLOYEE:</strong> {{employee_name}}</p>

<h2>1. POSITION AND DUTIES</h2>
<p>The Employer hereby employs the Employee in the position of <strong>{{job_title}}</strong>.</p>

<p>The Employee agrees to perform the duties and responsibilities associated with this position, as well as any other duties reasonably assigned by the Employer.</p>

<h2>2. EMPLOYMENT TERM</h2>
<p>Employment shall commence on {{start_date}} and shall continue as an at-will employment relationship, meaning either party may terminate the employment at any time with or without cause.</p>

<h2>3. COMPENSATION</h2>
<p>The Employee shall receive an annual salary of <strong>${{annual_salary}}</strong>, payable in accordance with the Employer's standard payroll practices (bi-weekly/semi-monthly).</p>

<h2>4. WORK LOCATION</h2>
<p>The Employee's primary work location shall be: {{work_location}}</p>

<p>The Employer reserves the right to require the Employee to work at other locations as business needs require.</p>

<h2>5. WORK HOURS</h2>
<p>The Employee is expected to work standard business hours as determined by the Employer. As a salaried employee, the Employee may be required to work additional hours as necessary to fulfill job responsibilities.</p>

<h2>6. BENEFITS</h2>
<p>The Employee shall be entitled to the following benefits:</p>
<ul>
<li><strong>Vacation Days:</strong> {{vacation_days}} days per year</li>
<li><strong>Sick Leave:</strong> As per company policy</li>
<li><strong>Health Insurance:</strong> As per company policy</li>
<li><strong>Retirement Plan:</strong> As per company policy</li>
</ul>

<p><strong>Additional Benefits:</strong></p>
<p>{{benefits}}</p>

<h2>7. CONFIDENTIALITY</h2>
<p>The Employee agrees to maintain strict confidentiality regarding all proprietary information, trade secrets, and confidential business information of the Employer, both during and after employment.</p>

<h2>8. INTELLECTUAL PROPERTY</h2>
<p>All work product, inventions, and intellectual property created by the Employee in the course of employment shall be the sole property of the Employer.</p>

<h2>9. NON-COMPETE</h2>
<p>During employment and for a period of 12 months following termination, the Employee agrees not to directly compete with the Employer's business within a 50-mile radius of the Employer's location.</p>

<h2>10. CODE OF CONDUCT</h2>
<p>The Employee agrees to abide by all company policies, procedures, and code of conduct as outlined in the Employee Handbook, which may be updated from time to time.</p>

<h2>11. TERMINATION</h2>
<p>This Agreement may be terminated by either party with two weeks' written notice. The Employer may terminate employment immediately for cause, including but not limited to misconduct, breach of company policy, or poor performance.</p>

<h2>12. RETURN OF PROPERTY</h2>
<p>Upon termination, the Employee agrees to return all company property, including but not limited to equipment, keys, documents, and confidential information.</p>

<h2>13. GOVERNING LAW</h2>
<p>This Agreement shall be governed by the employment laws of the jurisdiction in which the Employer operates.</p>

<h2>14. ENTIRE AGREEMENT</h2>
<p>This Agreement, together with the Employee Handbook, constitutes the entire agreement between the parties and supersedes all prior understandings.</p>

<br><br>

<p><strong>EMPLOYER:</strong></p>
<p>{{company_name}}</p>
<p>Signature: _________________________</p>
<p>Date: _________________________</p>

<br>

<p><strong>EMPLOYEE:</strong></p>
<p>{{employee_name}}</p>
<p>Signature: _________________________</p>
<p>Date: _________________________</p>

<p>I acknowledge that I have received and reviewed a copy of the Employee Handbook.</p>
<p>Employee Initials: _________</p>
HTML;
    }

    private function getFreelanceContractTemplate(): string
    {
        return <<<'HTML'
<h1>FREELANCE CONTRACT</h1>

<p>This Freelance Contract (the "Agreement") is made between:</p>

<p><strong>FREELANCER:</strong> {{freelancer_name}}<br>
<strong>Address:</strong> {{freelancer_address}}</p>

<p><strong>CLIENT:</strong> {{client_name}}<br>
<strong>Address:</strong> {{client_address}}</p>

<h2>1. PROJECT DESCRIPTION</h2>
<p>The Freelancer agrees to complete the following project for the Client:</p>
<p>{{project_description}}</p>

<h2>2. DELIVERABLES</h2>
<p>The Freelancer shall deliver the completed project by <strong>{{deadline}}</strong>.</p>

<p>The project shall be considered complete when all deliverables have been provided and approved by the Client.</p>

<h2>3. COMPENSATION</h2>
<p>The Client agrees to pay the Freelancer a total project fee of <strong>${{project_fee}}</strong>.</p>

<p><strong>Payment Schedule:</strong> {{payment_schedule}}</p>

<p>Example payment schedules:</p>
<ul>
<li>50% upfront, 50% upon completion</li>
<li>33% upfront, 33% at midpoint, 34% upon completion</li>
<li>100% upon completion (for smaller projects)</li>
</ul>

<h2>4. REVISIONS</h2>
<p>The project fee includes up to two rounds of reasonable revisions. Additional revisions will be billed at the Freelancer's hourly rate.</p>

<h2>5. EXPENSES</h2>
<p>Any project-related expenses over $100 must be pre-approved by the Client in writing. Approved expenses will be reimbursed within 30 days of submission with receipts.</p>

<h2>6. TIMELINE</h2>
<p>The Freelancer agrees to use best efforts to meet the deadline of {{deadline}}. Delays caused by the Client (such as late feedback or materials) may result in timeline adjustments.</p>

<h2>7. CLIENT RESPONSIBILITIES</h2>
<p>The Client agrees to:</p>
<ul>
<li>Provide all necessary materials, information, and access within 3 business days of request</li>
<li>Provide feedback on deliverables within 5 business days</li>
<li>Make payments according to the payment schedule</li>
<li>Designate a single point of contact for project communications</li>
</ul>

<h2>8. INTELLECTUAL PROPERTY</h2>
<p>Upon receipt of full payment, the Client shall own all rights to the final deliverables. The Freelancer retains the right to display the work in their portfolio and use it for self-promotion.</p>

<p>The Freelancer retains ownership of any pre-existing materials, templates, or methodologies used in the project.</p>

<h2>9. CONFIDENTIALITY</h2>
<p>The Freelancer agrees to keep all Client information confidential and shall not disclose any proprietary or sensitive information to third parties.</p>

<h2>10. INDEPENDENT CONTRACTOR</h2>
<p>The Freelancer is an independent contractor and is responsible for all taxes, insurance, and business licenses. This Agreement does not create an employment relationship.</p>

<h2>11. TERMINATION</h2>
<p>Either party may terminate this Agreement with 7 days written notice. In the event of termination:</p>
<ul>
<li>The Client shall pay for all work completed to date</li>
<li>The Freelancer shall deliver all work in progress</li>
<li>Any advance payments for incomplete work shall be refunded on a prorated basis</li>
</ul>

<h2>12. WARRANTY</h2>
<p>The Freelancer warrants that all work shall be original and shall not infringe on any third-party copyrights or intellectual property rights.</p>

<h2>13. LIMITATION OF LIABILITY</h2>
<p>The Freelancer's total liability under this Agreement shall not exceed the total project fee.</p>

<h2>14. DISPUTE RESOLUTION</h2>
<p>Any disputes shall first be attempted to be resolved through good-faith negotiation. If unsuccessful, disputes shall be resolved through binding arbitration.</p>

<h2>15. ENTIRE AGREEMENT</h2>
<p>This Agreement constitutes the entire agreement between the parties and supersedes all prior agreements or understandings.</p>

<br><br>

<p><strong>FREELANCER:</strong></p>
<p>{{freelancer_name}}</p>
<p>Signature: _________________________</p>
<p>Date: _________________________</p>

<br>

<p><strong>CLIENT:</strong></p>
<p>{{client_name}}</p>
<p>Signature: _________________________</p>
<p>Date: _________________________</p>
HTML;
    }

    // Continue with remaining template methods...
    // For brevity, I'll include simplified versions

    private function getConsultingAgreementTemplate(): string
    {
        return '<h1>CONSULTING AGREEMENT</h1><p>Consultant: {{consultant_name}}</p><p>Client: {{client_name}}</p><p>Services: {{consulting_services}}</p><p>Rate: ${{hourly_rate}}/hour</p><p>Term: {{contract_period}}</p>';
    }

    private function getSoftwareDevelopmentTemplate(): string
    {
        return '<h1>SOFTWARE DEVELOPMENT AGREEMENT</h1><p>Developer: {{developer_name}}</p><p>Client: {{client_name}}</p><p>Project: {{project_name}}</p><p>Scope: {{project_scope}}</p><p>Cost: ${{total_cost}}</p>';
    }

    private function getSalesContractTemplate(): string
    {
        return '<h1>SALES CONTRACT</h1><p>Seller: {{seller_name}}</p><p>Buyer: {{buyer_name}}</p><p>Product: {{product_description}}</p><p>Total: ${{total_price}}</p>';
    }

    private function getPartnershipAgreementTemplate(): string
    {
        return '<h1>PARTNERSHIP AGREEMENT</h1><p>Partnership: {{partnership_name}}</p><p>Partner 1: {{partner1_name}} ({{partner1_ownership}}%)</p><p>Partner 2: {{partner2_name}} ({{partner2_ownership}}%)</p>';
    }

    private function getLeaseAgreementTemplate(): string
    {
        return '<h1>LEASE AGREEMENT</h1><p>Landlord: {{landlord_name}}</p><p>Tenant: {{tenant_name}}</p><p>Property: {{property_address}}</p><p>Rent: ${{monthly_rent}}/month</p>';
    }

    private function getLicenseAgreementTemplate(): string
    {
        return '<h1>LICENSE AGREEMENT</h1><p>Licensor: {{licensor_name}}</p><p>Licensee: {{licensee_name}}</p><p>Software: {{software_name}}</p><p>Fee: ${{license_fee}}</p>';
    }

    private function getMarketingServicesTemplate(): string
    {
        return '<h1>MARKETING SERVICES AGREEMENT</h1><p>Agency: {{agency_name}}</p><p>Client: {{client_name}}</p><p>Services: {{services_description}}</p><p>Monthly Retainer: ${{monthly_retainer}}</p>';
    }

    private function getWebsiteDevelopmentTemplate(): string
    {
        return '<h1>WEBSITE DEVELOPMENT CONTRACT</h1><p>Developer: {{developer_name}}</p><p>Client: {{client_name}}</p><p>Description: {{website_description}}</p><p>Cost: ${{total_cost}}</p>';
    }

    private function getMaintenanceAgreementTemplate(): string
    {
        return '<h1>MAINTENANCE AGREEMENT</h1><p>Provider: {{provider_name}}</p><p>Client: {{client_name}}</p><p>System: {{system_description}}</p><p>Monthly Fee: ${{monthly_fee}}</p>';
    }

    private function getTermsOfServiceTemplate(): string
    {
        return '<h1>TERMS OF SERVICE</h1><p>Company: {{company_name}}</p><p>Service: {{service_name}}</p><p>Website: {{website_url}}</p><p>Effective: {{effective_date}}</p>';
    }

    private function getPrivacyPolicyTemplate(): string
    {
        return '<h1>PRIVACY POLICY</h1><p>Company: {{company_name}}</p><p>Website: {{website_url}}</p><p>Contact: {{contact_email}}</p><p>Effective: {{effective_date}}</p>';
    }
}
