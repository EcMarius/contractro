<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $contract->title }}</title>
    <style>
        @page {
            margin: 2cm;
            @bottom-right {
                content: "Page " counter(page) " of " counter(pages);
            }
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.6;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #2563eb;
        }

        .header h1 {
            margin: 0;
            font-size: 24pt;
            color: #1e40af;
        }

        .contract-meta {
            margin-bottom: 25px;
            padding: 15px;
            background-color: #f3f4f6;
            border-left: 4px solid #2563eb;
        }

        .contract-meta table {
            width: 100%;
            border-collapse: collapse;
        }

        .contract-meta td {
            padding: 5px 10px;
            font-size: 10pt;
        }

        .contract-meta td:first-child {
            font-weight: bold;
            width: 150px;
            color: #1e40af;
        }

        .content {
            margin: 20px 0;
            text-align: justify;
        }

        .content h2 {
            font-size: 14pt;
            color: #1e40af;
            margin-top: 25px;
            margin-bottom: 10px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 5px;
        }

        .content h3 {
            font-size: 12pt;
            color: #374151;
            margin-top: 15px;
            margin-bottom: 8px;
        }

        .content p {
            margin: 10px 0;
        }

        .content ul, .content ol {
            margin: 10px 0;
            padding-left: 25px;
        }

        .content li {
            margin: 5px 0;
        }

        .signatures {
            margin-top: 50px;
            page-break-inside: avoid;
        }

        .signatures h2 {
            font-size: 14pt;
            color: #1e40af;
            margin-bottom: 20px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 5px;
        }

        .signature-block {
            margin-bottom: 40px;
            page-break-inside: avoid;
        }

        .signature-block table {
            width: 100%;
            border-collapse: collapse;
        }

        .signature-block td {
            padding: 8px;
            vertical-align: top;
        }

        .signature-block .label {
            font-weight: bold;
            color: #374151;
            width: 120px;
        }

        .signature-image {
            max-width: 250px;
            max-height: 80px;
            border: 1px solid #e5e7eb;
            padding: 5px;
            margin-top: 10px;
        }

        .signature-line {
            border-top: 2px solid #333;
            width: 250px;
            margin-top: 30px;
        }

        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80pt;
            color: rgba(220, 38, 38, 0.1);
            z-index: -1;
            font-weight: bold;
        }

        .footer {
            margin-top: 50px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            font-size: 9pt;
            color: #6b7280;
            text-align: center;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 9pt;
            font-weight: bold;
        }

        .badge-signed {
            background-color: #dcfce7;
            color: #166534;
        }

        .badge-pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .badge-draft {
            background-color: #e5e7eb;
            color: #374151;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        table th {
            background-color: #f3f4f6;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #d1d5db;
        }

        table td {
            padding: 8px;
            border: 1px solid #d1d5db;
        }

        .certification {
            margin-top: 30px;
            padding: 15px;
            border: 2px solid #2563eb;
            background-color: #eff6ff;
            page-break-inside: avoid;
        }

        .certification h3 {
            margin-top: 0;
            color: #1e40af;
        }

        .certification-details {
            font-size: 9pt;
            color: #374151;
        }
    </style>
</head>
<body>
    @if($contract->status === 'draft' || $contract->status === 'cancelled')
        <div class="watermark">{{ strtoupper($contract->status) }}</div>
    @endif

    <div class="header">
        <h1>{{ $contract->title }}</h1>
        <p style="margin: 5px 0; font-size: 10pt; color: #6b7280;">
            Contract #{{ $contract->contract_number }}
        </p>
    </div>

    <div class="contract-meta">
        <table>
            <tr>
                <td>Status:</td>
                <td>
                    <span class="badge badge-{{ $contract->status === 'signed' ? 'signed' : ($contract->status === 'draft' ? 'draft' : 'pending') }}">
                        {{ strtoupper(str_replace('_', ' ', $contract->status)) }}
                    </span>
                </td>
            </tr>
            <tr>
                <td>Created:</td>
                <td>{{ $contract->created_at->format('F d, Y') }}</td>
            </tr>
            @if($contract->contract_value)
                <tr>
                    <td>Contract Value:</td>
                    <td>${{ number_format($contract->contract_value, 2) }}</td>
                </tr>
            @endif
            @if($contract->effective_date)
                <tr>
                    <td>Effective Date:</td>
                    <td>{{ \Carbon\Carbon::parse($contract->effective_date)->format('F d, Y') }}</td>
                </tr>
            @endif
            @if($contract->expiration_date)
                <tr>
                    <td>Expiration Date:</td>
                    <td>{{ \Carbon\Carbon::parse($contract->expiration_date)->format('F d, Y') }}</td>
                </tr>
            @endif
            @if($contract->signed_at)
                <tr>
                    <td>Signed Date:</td>
                    <td>{{ $contract->signed_at->format('F d, Y') }}</td>
                </tr>
            @endif
        </table>
    </div>

    <div class="content">
        {!! $contract->content !!}
    </div>

    @if($contract->signatures->count() > 0)
        <div class="signatures">
            <h2>Signatures</h2>

            @foreach($contract->signatures->sortBy('signing_order') as $signature)
                <div class="signature-block">
                    <table>
                        <tr>
                            <td class="label">Signer:</td>
                            <td><strong>{{ $signature->signer_name }}</strong></td>
                        </tr>
                        <tr>
                            <td class="label">Email:</td>
                            <td>{{ $signature->signer_email }}</td>
                        </tr>
                        @if($signature->signer_role)
                            <tr>
                                <td class="label">Role:</td>
                                <td>{{ $signature->signer_role }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="label">Status:</td>
                            <td>
                                <span class="badge badge-{{ $signature->status === 'signed' ? 'signed' : 'pending' }}">
                                    {{ strtoupper($signature->status) }}
                                </span>
                            </td>
                        </tr>
                        @if($signature->status === 'signed')
                            <tr>
                                <td class="label">Signed Date:</td>
                                <td>{{ $signature->signed_at->format('F d, Y \a\t g:i A') }}</td>
                            </tr>
                            @if($signature->signature_data)
                                <tr>
                                    <td class="label">Signature:</td>
                                    <td>
                                        @if($signature->signature_type === 'drawn')
                                            <img src="{{ $signature->signature_data }}" alt="Signature" class="signature-image">
                                        @elseif($signature->signature_type === 'typed')
                                            <div style="font-family: 'Brush Script MT', cursive; font-size: 24pt; margin-top: 10px;">
                                                {{ $signature->signature_data }}
                                            </div>
                                        @else
                                            <div style="margin-top: 10px;">Electronic Signature</div>
                                        @endif
                                    </td>
                                </tr>
                            @endif
                            @if($signature->ip_address)
                                <tr>
                                    <td class="label">IP Address:</td>
                                    <td style="font-family: monospace;">{{ $signature->ip_address }}</td>
                                </tr>
                            @endif
                        @elseif($signature->status === 'declined')
                            <tr>
                                <td class="label">Declined Date:</td>
                                <td>{{ $signature->declined_at->format('F d, Y \a\t g:i A') }}</td>
                            </tr>
                            @if($signature->decline_reason)
                                <tr>
                                    <td class="label">Reason:</td>
                                    <td>{{ $signature->decline_reason }}</td>
                                </tr>
                            @endif
                        @else
                            <tr>
                                <td colspan="2">
                                    <div class="signature-line"></div>
                                    <div style="font-size: 9pt; color: #6b7280; margin-top: 5px;">
                                        Pending signature
                                    </div>
                                </td>
                            </tr>
                        @endif
                    </table>
                </div>
            @endforeach
        </div>
    @endif

    @if($contract->status === 'signed' && isset($showCertification) && $showCertification)
        <div class="certification">
            <h3>Certificate of Completion</h3>
            <div class="certification-details">
                <p><strong>This document certifies that:</strong></p>
                <ul>
                    <li>Contract "{{ $contract->title }}" ({{ $contract->contract_number }}) has been fully executed</li>
                    <li>All required signatures have been collected and verified</li>
                    <li>The document was signed by {{ $contract->signatures->where('status', 'signed')->count() }} authorized signatories</li>
                    <li>Final signature completed on {{ $contract->signed_at->format('F d, Y \a\t g:i A') }}</li>
                    <li>This PDF was generated on {{ now()->format('F d, Y \a\t g:i A') }}</li>
                </ul>
                <p style="margin-top: 15px;">
                    <strong>Document Hash:</strong> {{ hash('sha256', $contract->id . $contract->updated_at) }}
                </p>
            </div>
        </div>
    @endif

    <div class="footer">
        <p>
            This document was generated electronically by {{ config('app.name') }}<br>
            Generated on {{ now()->format('F d, Y \a\t g:i A T') }}
        </p>
        @if(isset($includeDisclaimer) && $includeDisclaimer)
            <p style="margin-top: 10px; font-size: 8pt;">
                This is a legally binding document. Please read carefully before signing.
            </p>
        @endif
    </div>
</body>
</html>
