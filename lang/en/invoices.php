<?php

return [
    // General
    'invoices' => 'Invoices',
    'invoice' => 'Invoice',
    'new_invoice' => 'New Invoice',
    'create_invoice' => 'Create Invoice',
    'edit_invoice' => 'Edit Invoice',
    'view_invoice' => 'View Invoice',
    'delete_invoice' => 'Delete Invoice',

    // Status
    'status' => [
        'draft' => 'Draft',
        'issued' => 'Issued',
        'paid' => 'Paid',
        'overdue' => 'Overdue',
        'cancelled' => 'Cancelled',
    ],

    // Fields
    'invoice_number' => 'Invoice No.',
    'series' => 'Series',
    'company' => 'Company',
    'contract' => 'Contract',
    'client_name' => 'Client Name',
    'client_cui' => 'Client CUI',
    'client_reg_com' => 'Client Trade Register No.',
    'client_address' => 'Client Address',
    'issue_date' => 'Issue Date',
    'due_date' => 'Due Date',
    'payment_date' => 'Payment Date',
    'notes' => 'Notes',

    // Line Items
    'items' => 'Products/Services',
    'item' => 'Product/Service',
    'description' => 'Description',
    'quantity' => 'Quantity',
    'unit_price' => 'Unit Price',
    'total_price' => 'Total',
    'add_item' => 'Add Product/Service',

    // Amounts
    'subtotal' => 'Subtotal',
    'vat' => 'VAT',
    'vat_rate' => 'VAT Rate',
    'total' => 'Total',
    'total_amount' => 'Total Amount',
    'currency' => 'Currency',

    // Actions
    'issue' => 'Issue Invoice',
    'mark_as_paid' => 'Mark as Paid',
    'cancel' => 'Cancel',
    'download_pdf' => 'Download PDF',
    'send_email' => 'Send via Email',
    'create_from_contract' => 'Create from Contract',

    // Messages
    'messages' => [
        'created_successfully' => 'Invoice has been created successfully.',
        'updated_successfully' => 'Invoice has been updated successfully.',
        'deleted_successfully' => 'Invoice has been deleted successfully.',
        'issued_successfully' => 'Invoice has been issued successfully.',
        'marked_as_paid' => 'Invoice has been marked as paid.',
        'cancelled_successfully' => 'Invoice has been cancelled successfully.',
        'no_invoices' => 'No invoices exist.',
        'confirm_delete' => 'Are you sure you want to delete this invoice?',
        'confirm_cancel' => 'Are you sure you want to cancel this invoice?',
        'requires_company' => 'You must have at least one company created.',
        'invoice_overdue' => 'This invoice is overdue.',
        'invoice_paid' => 'This invoice has been paid.',
    ],

    // Filters
    'filters' => [
        'all_statuses' => 'All statuses',
        'all_companies' => 'All companies',
        'search_placeholder' => 'Invoice no., client, CUI...',
        'reset' => 'Reset',
        'apply' => 'Filter',
    ],

    // Statistics
    'stats' => [
        'total_invoices' => 'Total Invoices',
        'total_amount' => 'Total Amount',
        'paid_amount' => 'Paid',
        'pending_amount' => 'Pending',
        'overdue_amount' => 'Overdue',
        'overdue_count' => 'Overdue Invoices',
    ],

    // Payment Info
    'payment_instructions' => 'Payment Instructions',
    'bank_details' => 'Bank Details',
    'pay_within' => 'Payment term',
    'days' => 'days',
];
