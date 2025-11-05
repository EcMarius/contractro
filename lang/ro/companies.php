<?php

return [
    // General
    'companies' => 'Companii',
    'company' => 'Companie',
    'my_companies' => 'Companiile Mele',
    'new_company' => 'Companie Nouă',
    'create_company' => 'Creează Companie',
    'edit_company' => 'Editează Companie',
    'view_company' => 'Vezi Companie',
    'delete_company' => 'Șterge Companie',
    'switch_company' => 'Schimbă Compania',

    // Fields
    'name' => 'Denumire',
    'cui' => 'CUI (Cod Unic de Identificare)',
    'reg_com' => 'Nr. Reg. Com.',
    'address' => 'Adresă',
    'city' => 'Oraș',
    'county' => 'Județ',
    'postal_code' => 'Cod Poștal',
    'phone' => 'Telefon',
    'email' => 'Email',
    'website' => 'Website',
    'logo' => 'Logo',

    // Banking
    'bank_name' => 'Nume Bancă',
    'iban' => 'IBAN',
    'bank_details' => 'Detalii Bancare',
    'banking_information' => 'Informații Bancare',

    // Sections
    'company_information' => 'Informații Companie',
    'contact_information' => 'Informații de Contact',
    'fiscal_information' => 'Informații Fiscale',

    // Actions
    'add_logo' => 'Adaugă Logo',
    'change_logo' => 'Schimbă Logo',
    'remove_logo' => 'Șterge Logo',
    'upload_logo' => 'Încarcă Logo',
    'validate_cui' => 'Validează CUI',

    // Messages
    'messages' => [
        'created_successfully' => 'Compania a fost creată cu succes.',
        'updated_successfully' => 'Compania a fost actualizată cu succes.',
        'deleted_successfully' => 'Compania a fost ștearsă cu succes.',
        'switched_successfully' => 'Ai schimbat compania activă.',
        'no_companies' => 'Nu ai nicio companie creată.',
        'confirm_delete' => 'Sigur dorești să ștergi această companie? Toate contractele și facturile asociate vor fi șterse!',
        'cui_invalid' => 'CUI-ul introdus nu este valid.',
        'cui_valid' => 'CUI-ul este valid.',
        'first_company_prompt' => 'Creează prima ta companie pentru a începe.',
        'manage_companies' => 'Gestionează companiile tale și datele fiscale',
        'fill_company_info' => 'Completează informațiile despre compania ta',
        'update_company_info' => 'Actualizează informațiile companiei tale',
        'save_company' => 'Salvează Compania',
        'save_changes' => 'Salvează Modificările',
        'current_logo' => 'Logo curent',
        'remove_current_logo' => 'Șterge logo-ul curent',
        'replace_logo' => 'Înlocuiește logo-ul',
    ],

    // Validation
    'validation' => [
        'name_required' => 'Numele companiei este obligatoriu.',
        'name_min' => 'Numele companiei trebuie să aibă cel puțin :min caractere.',
        'name_max' => 'Numele companiei poate avea maximum :max caractere.',
        'cui_max' => 'CUI-ul poate avea maximum :max caractere.',
        'cui_format' => 'Format: RO12345678 sau doar cifrele',
        'email_invalid' => 'Adresa de email nu este validă.',
        'logo_format' => 'Logo-ul trebuie să fie în format PNG, JPG, JPEG sau SVG.',
        'logo_size' => 'Logo-ul nu poate depăși :max MB.',
    ],

    // Statistics
    'stats' => [
        'total_contracts' => 'Total Contracte',
        'active_contracts' => 'Contracte Active',
        'total_invoices' => 'Total Facturi',
        'total_revenue' => 'Venituri Totale',
        'pending_revenue' => 'În Așteptare',
    ],

    // Helpers
    'helpers' => [
        'cui_format' => 'Format: RO12345678 sau doar cifrele',
        'reg_com_format' => 'Ex: J40/1234/2023',
        'iban_format' => 'Format IBAN românesc: RO49AAAA1B31007593840000',
        'logo_requirements' => 'PNG, JPG, JPEG sau SVG. Maximum :max MB.',
    ],

    // Quick Actions
    'quick_actions' => 'Acțiuni Rapide',
    'new_contract' => 'Contract Nou',
    'new_invoice' => 'Factură Nouă',
    'view_reports' => 'Vezi Rapoarte',
];
