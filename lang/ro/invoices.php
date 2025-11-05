<?php

return [
    // General
    'invoices' => 'Facturi',
    'invoice' => 'Factură',
    'new_invoice' => 'Factură Nouă',
    'create_invoice' => 'Creează Factură',
    'edit_invoice' => 'Editează Factură',
    'view_invoice' => 'Vezi Factură',
    'delete_invoice' => 'Șterge Factură',

    // Status
    'status' => [
        'draft' => 'Ciornă',
        'issued' => 'Emisă',
        'paid' => 'Plătită',
        'overdue' => 'Restantă',
        'cancelled' => 'Anulată',
    ],

    // Fields
    'invoice_number' => 'Nr. Factură',
    'series' => 'Serie',
    'company' => 'Companie',
    'contract' => 'Contract',
    'client_name' => 'Nume Client',
    'client_cui' => 'CUI Client',
    'client_reg_com' => 'Nr. Reg. Com. Client',
    'client_address' => 'Adresă Client',
    'issue_date' => 'Data Emitere',
    'due_date' => 'Data Scadență',
    'payment_date' => 'Data Plată',
    'notes' => 'Observații',

    // Line Items
    'items' => 'Produse/Servicii',
    'item' => 'Produs/Serviciu',
    'description' => 'Descriere',
    'quantity' => 'Cantitate',
    'unit_price' => 'Preț Unitar',
    'total_price' => 'Total',
    'add_item' => 'Adaugă Produs/Serviciu',

    // Amounts
    'subtotal' => 'Subtotal',
    'vat' => 'TVA',
    'vat_rate' => 'Cota TVA',
    'total' => 'Total',
    'total_amount' => 'Total de Plată',
    'currency' => 'Monedă',

    // Actions
    'issue' => 'Emite Factura',
    'mark_as_paid' => 'Marchează ca Plătită',
    'cancel' => 'Anulează',
    'download_pdf' => 'Descarcă PDF',
    'send_email' => 'Trimite pe Email',
    'create_from_contract' => 'Creează din Contract',

    // Messages
    'messages' => [
        'created_successfully' => 'Factura a fost creată cu succes.',
        'updated_successfully' => 'Factura a fost actualizată cu succes.',
        'deleted_successfully' => 'Factura a fost ștearsă cu succes.',
        'issued_successfully' => 'Factura a fost emisă cu succes.',
        'marked_as_paid' => 'Factura a fost marcată ca plătită.',
        'cancelled_successfully' => 'Factura a fost anulată cu succes.',
        'no_invoices' => 'Nu există facturi.',
        'confirm_delete' => 'Sigur dorești să ștergi această factură?',
        'confirm_cancel' => 'Sigur dorești să anulezi această factură?',
        'requires_company' => 'Trebuie să ai cel puțin o companie creată.',
        'invoice_overdue' => 'Această factură este restantă.',
        'invoice_paid' => 'Această factură a fost plătită.',
    ],

    // Filters
    'filters' => [
        'all_statuses' => 'Toate statusurile',
        'all_companies' => 'Toate companiile',
        'search_placeholder' => 'Nr. factură, client, CUI...',
        'reset' => 'Resetează',
        'apply' => 'Filtrează',
    ],

    // Statistics
    'stats' => [
        'total_invoices' => 'Total Facturi',
        'total_amount' => 'Valoare Totală',
        'paid_amount' => 'Plătite',
        'pending_amount' => 'În Așteptare',
        'overdue_amount' => 'Restante',
        'overdue_count' => 'Facturi Restante',
    ],

    // Payment Info
    'payment_instructions' => 'Instrucțiuni de Plată',
    'bank_details' => 'Detalii Bancare',
    'pay_within' => 'Termen de plată',
    'days' => 'zile',
];
