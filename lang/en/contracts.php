<?php

return [
    // General
    'contracts' => 'Contracts',
    'contract' => 'Contract',
    'new_contract' => 'New Contract',
    'create_contract' => 'Create Contract',
    'edit_contract' => 'Edit Contract',
    'view_contract' => 'View Contract',
    'delete_contract' => 'Delete Contract',
    'duplicate_contract' => 'Duplicate Contract',

    // Status
    'status' => [
        'draft' => 'Draft',
        'pending' => 'Pending signature',
        'signed' => 'Signed',
        'active' => 'Active',
        'expired' => 'Expired',
        'terminated' => 'Terminated',
    ],

    // Fields
    'contract_number' => 'Contract No.',
    'title' => 'Title',
    'description' => 'Description',
    'content' => 'Content',
    'contract_type' => 'Contract Type',
    'company' => 'Company',
    'client_name' => 'Client Name',
    'start_date' => 'Start Date',
    'end_date' => 'End Date',
    'value' => 'Value',
    'billing_cycle' => 'Billing Cycle',
    'auto_renewal' => 'Auto Renewal',

    // Billing Cycles
    'billing_cycles' => [
        'monthly' => 'Monthly',
        'quarterly' => 'Quarterly',
        'yearly' => 'Yearly',
        'one-time' => 'One-time',
    ],

    // Actions
    'send_for_signing' => 'Send for signing',
    'sign_contract' => 'Sign Contract',
    'download_pdf' => 'Download PDF',
    'add_party' => 'Add Party',
    'add_amendment' => 'Add Amendment',
    'add_attachment' => 'Add Attachment',
    'add_task' => 'Add Task',
    'create_invoice' => 'Create Invoice',

    // Parties
    'parties' => 'Parties',
    'party' => 'Party',
    'add_party_title' => 'Add Contracting Party',
    'party_name' => 'Name',
    'party_email' => 'Email',
    'party_phone' => 'Phone',
    'signing_status' => 'Signing Status',
    'signed_at' => 'Signed at',
    'not_signed' => 'Not signed',

    // Attachments
    'attachments' => 'Attachments',
    'attachment' => 'Attachment',
    'file_name' => 'File Name',
    'file_size' => 'Size',
    'uploaded_by' => 'Uploaded by',
    'uploaded_at' => 'Uploaded at',

    // Amendments
    'amendments' => 'Amendments',
    'amendment' => 'Amendment',
    'amendment_number' => 'Amendment No.',
    'amendment_title' => 'Amendment Title',
    'effective_date' => 'Effective Date',

    // Tasks
    'tasks' => 'Tasks',
    'task' => 'Task',
    'task_title' => 'Task Title',
    'task_description' => 'Task Description',
    'due_date' => 'Due Date',
    'assigned_to' => 'Assigned to',
    'priority' => 'Priority',
    'task_status' => [
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
    ],
    'priority_levels' => [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
    ],

    // Messages
    'messages' => [
        'created_successfully' => 'Contract has been created successfully.',
        'updated_successfully' => 'Contract has been updated successfully.',
        'deleted_successfully' => 'Contract has been deleted successfully.',
        'sent_for_signing' => 'Contract has been sent for signing to :count parties.',
        'signed_successfully' => 'Contract has been signed successfully.',
        'no_contracts' => 'No contracts exist.',
        'no_parties' => 'No parties added.',
        'no_attachments' => 'No attachments exist.',
        'no_amendments' => 'No amendments exist.',
        'no_tasks' => 'No tasks exist.',
        'confirm_delete' => 'Are you sure you want to delete this contract?',
        'requires_company' => 'You must have at least one company created.',
    ],

    // Filters
    'filters' => [
        'all_statuses' => 'All statuses',
        'all_types' => 'All types',
        'all_companies' => 'All companies',
        'search_placeholder' => 'Contract no., title, client...',
        'reset' => 'Reset',
        'apply' => 'Filter',
    ],

    // Statistics
    'stats' => [
        'total_contracts' => 'Total Contracts',
        'active_contracts' => 'Active Contracts',
        'pending_contracts' => 'Pending',
        'signed_this_month' => 'Signed This Month',
        'expiring_soon' => 'Expiring Soon',
        'total_value' => 'Total Value',
    ],
];
