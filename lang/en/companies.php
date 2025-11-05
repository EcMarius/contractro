<?php

return [
    // General
    'companies' => 'Companies',
    'company' => 'Company',
    'my_companies' => 'My Companies',
    'new_company' => 'New Company',
    'create_company' => 'Create Company',
    'edit_company' => 'Edit Company',
    'view_company' => 'View Company',
    'delete_company' => 'Delete Company',
    'switch_company' => 'Switch Company',

    // Fields
    'name' => 'Name',
    'cui' => 'CUI (Unique Identification Code)',
    'reg_com' => 'Trade Register No.',
    'address' => 'Address',
    'city' => 'City',
    'county' => 'County',
    'postal_code' => 'Postal Code',
    'phone' => 'Phone',
    'email' => 'Email',
    'website' => 'Website',
    'logo' => 'Logo',

    // Banking
    'bank_name' => 'Bank Name',
    'iban' => 'IBAN',
    'bank_details' => 'Bank Details',
    'banking_information' => 'Banking Information',

    // Sections
    'company_information' => 'Company Information',
    'contact_information' => 'Contact Information',
    'fiscal_information' => 'Fiscal Information',

    // Actions
    'add_logo' => 'Add Logo',
    'change_logo' => 'Change Logo',
    'remove_logo' => 'Remove Logo',
    'upload_logo' => 'Upload Logo',
    'validate_cui' => 'Validate CUI',

    // Messages
    'messages' => [
        'created_successfully' => 'Company has been created successfully.',
        'updated_successfully' => 'Company has been updated successfully.',
        'deleted_successfully' => 'Company has been deleted successfully.',
        'switched_successfully' => 'You have switched the active company.',
        'no_companies' => 'You have no companies created.',
        'confirm_delete' => 'Are you sure you want to delete this company? All associated contracts and invoices will be deleted!',
        'cui_invalid' => 'The entered CUI is not valid.',
        'cui_valid' => 'The CUI is valid.',
        'first_company_prompt' => 'Create your first company to get started.',
        'manage_companies' => 'Manage your companies and tax information',
        'fill_company_info' => 'Fill in the information about your company',
        'update_company_info' => 'Update your company information',
        'save_company' => 'Save Company',
        'save_changes' => 'Save Changes',
        'current_logo' => 'Current logo',
        'remove_current_logo' => 'Remove current logo',
        'replace_logo' => 'Replace logo',
    ],

    // Validation
    'validation' => [
        'name_required' => 'Company name is required.',
        'name_min' => 'Company name must be at least :min characters.',
        'name_max' => 'Company name can be maximum :max characters.',
        'cui_max' => 'CUI can be maximum :max characters.',
        'cui_format' => 'Format: RO12345678 or just the numbers',
        'email_invalid' => 'The email address is not valid.',
        'logo_format' => 'Logo must be in PNG, JPG, JPEG or SVG format.',
        'logo_size' => 'Logo cannot exceed :max MB.',
    ],

    // Statistics
    'stats' => [
        'total_contracts' => 'Total Contracts',
        'active_contracts' => 'Active Contracts',
        'total_invoices' => 'Total Invoices',
        'total_revenue' => 'Total Revenue',
        'pending_revenue' => 'Pending',
    ],

    // Helpers
    'helpers' => [
        'cui_format' => 'Format: RO12345678 or just the numbers',
        'reg_com_format' => 'Ex: J40/1234/2023',
        'iban_format' => 'Romanian IBAN format: RO49AAAA1B31007593840000',
        'logo_requirements' => 'PNG, JPG, JPEG or SVG. Maximum :max MB.',
    ],

    // Quick Actions
    'quick_actions' => 'Quick Actions',
    'new_contract' => 'New Contract',
    'new_invoice' => 'New Invoice',
    'view_reports' => 'View Reports',
];
