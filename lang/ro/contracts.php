<?php

return [
    // General
    'contracts' => 'Contracte',
    'contract' => 'Contract',
    'new_contract' => 'Contract Nou',
    'create_contract' => 'Creează Contract',
    'edit_contract' => 'Editează Contract',
    'view_contract' => 'Vezi Contract',
    'delete_contract' => 'Șterge Contract',
    'duplicate_contract' => 'Duplică Contract',

    // Status
    'status' => [
        'draft' => 'Ciornă',
        'pending' => 'În așteptare semnare',
        'signed' => 'Semnat',
        'active' => 'Activ',
        'expired' => 'Expirat',
        'terminated' => 'Reziliat',
    ],

    // Fields
    'contract_number' => 'Nr. Contract',
    'title' => 'Titlu',
    'description' => 'Descriere',
    'content' => 'Conținut',
    'contract_type' => 'Tip Contract',
    'company' => 'Companie',
    'client_name' => 'Nume Client',
    'start_date' => 'Data Început',
    'end_date' => 'Data Sfârșit',
    'value' => 'Valoare',
    'billing_cycle' => 'Ciclu Facturare',
    'auto_renewal' => 'Reînnoire Automată',

    // Billing Cycles
    'billing_cycles' => [
        'monthly' => 'Lunar',
        'quarterly' => 'Trimestrial',
        'yearly' => 'Anual',
        'one-time' => 'O singură dată',
    ],

    // Actions
    'send_for_signing' => 'Trimite spre semnare',
    'sign_contract' => 'Semnează Contract',
    'download_pdf' => 'Descarcă PDF',
    'add_party' => 'Adaugă Parte',
    'add_amendment' => 'Adaugă Act Adițional',
    'add_attachment' => 'Adaugă Atașament',
    'add_task' => 'Adaugă Task',
    'create_invoice' => 'Creează Factură',

    // Parties
    'parties' => 'Părți',
    'party' => 'Parte',
    'add_party_title' => 'Adaugă Parte Contractantă',
    'party_name' => 'Nume',
    'party_email' => 'Email',
    'party_phone' => 'Telefon',
    'signing_status' => 'Status Semnare',
    'signed_at' => 'Semnat la',
    'not_signed' => 'Nesemnat',

    // Attachments
    'attachments' => 'Atașamente',
    'attachment' => 'Atașament',
    'file_name' => 'Nume Fișier',
    'file_size' => 'Dimensiune',
    'uploaded_by' => 'Încărcat de',
    'uploaded_at' => 'Încărcat la',

    // Amendments
    'amendments' => 'Acte Adiționale',
    'amendment' => 'Act Adițional',
    'amendment_number' => 'Nr. Act Adițional',
    'amendment_title' => 'Titlu Act Adițional',
    'effective_date' => 'Data Intrare în Vigoare',

    // Tasks
    'tasks' => 'Taskuri',
    'task' => 'Task',
    'task_title' => 'Titlu Task',
    'task_description' => 'Descriere Task',
    'due_date' => 'Scadență',
    'assigned_to' => 'Asignat',
    'priority' => 'Prioritate',
    'task_status' => [
        'pending' => 'În Așteptare',
        'in_progress' => 'În Progres',
        'completed' => 'Completat',
    ],
    'priority_levels' => [
        'low' => 'Scăzută',
        'medium' => 'Medie',
        'high' => 'Ridicată',
    ],

    // Messages
    'messages' => [
        'created_successfully' => 'Contractul a fost creat cu succes.',
        'updated_successfully' => 'Contractul a fost actualizat cu succes.',
        'deleted_successfully' => 'Contractul a fost șters cu succes.',
        'sent_for_signing' => 'Contractul a fost trimis spre semnare către :count părți.',
        'signed_successfully' => 'Contractul a fost semnat cu succes.',
        'no_contracts' => 'Nu există contracte.',
        'no_parties' => 'Nu există părți adăugate.',
        'no_attachments' => 'Nu există atașamente.',
        'no_amendments' => 'Nu există acte adiționale.',
        'no_tasks' => 'Nu există taskuri.',
        'confirm_delete' => 'Sigur dorești să ștergi acest contract?',
        'requires_company' => 'Trebuie să ai cel puțin o companie creată.',
    ],

    // Filters
    'filters' => [
        'all_statuses' => 'Toate statusurile',
        'all_types' => 'Toate tipurile',
        'all_companies' => 'Toate companiile',
        'search_placeholder' => 'Nr. contract, titlu, client...',
        'reset' => 'Resetează',
        'apply' => 'Filtrează',
    ],

    // Statistics
    'stats' => [
        'total_contracts' => 'Total Contracte',
        'active_contracts' => 'Contracte Active',
        'pending_contracts' => 'În Așteptare',
        'signed_this_month' => 'Semnate Luna Aceasta',
        'expiring_soon' => 'Expiră În Curând',
        'total_value' => 'Valoare Totală',
    ],
];
