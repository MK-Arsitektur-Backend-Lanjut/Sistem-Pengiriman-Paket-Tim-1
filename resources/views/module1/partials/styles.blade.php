@push('styles')
<style>
    /* ── Module-1 Hero Banner ── */
    .m1-hero {
        border-radius: 20px;
        background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #60a5fa 100%);
        color: #fff;
        position: relative;
        overflow: hidden;
    }

    .m1-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at 20% 20%, rgba(255, 255, 255, 0.22), transparent 45%);
    }

    .m1-hero-content {
        position: relative;
        z-index: 2;
    }

    /* ── KPI Cards (shared style with Module-4) ── */
    .kpi-card {
        border: 1px solid var(--border);
        border-radius: 16px;
        background: #fff;
        height: 100%;
        transition: box-shadow 0.2s, transform 0.2s;
    }

    .kpi-card:hover {
        box-shadow: 0 8px 24px rgba(37, 99, 235, 0.10);
        transform: translateY(-2px);
    }

    .kpi-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }

    .kpi-icon-warehouse { background: #eff6ff; color: #2563eb; }
    .kpi-icon-package   { background: #ecfdf5; color: #16a34a; }
    .kpi-icon-capacity  { background: #f0f9ff; color: #0284c7; }
    .kpi-icon-usage     { background: #fef3c7; color: #b45309; }
    .kpi-icon-small     { background: #eff6ff; color: #2563eb; }
    .kpi-icon-medium    { background: #ecfdf5; color: #16a34a; }
    .kpi-icon-large     { background: #fef2f2; color: #dc2626; }

    /* ── Pill Labels ── */
    .pill-info {
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.3rem 0.7rem;
        border: 1px solid #bfdbfe;
        background: #eff6ff;
        color: #1d4ed8;
    }

    .pill-info.pill-green {
        border-color: #bbf7d0;
        background: #ecfdf5;
        color: #166534;
    }

    .pill-info.pill-sky {
        border-color: #bae6fd;
        background: #f0f9ff;
        color: #0369a1;
    }

    .pill-info.pill-amber {
        border-color: #fde68a;
        background: #fef3c7;
        color: #92400e;
    }

    .pill-info.pill-red {
        border-color: #fecaca;
        background: #fef2f2;
        color: #991b1b;
    }

    /* ── Soft Card (tables & panels) ── */
    .soft-card {
        border: 1px solid var(--border);
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.06);
        overflow: hidden;
    }

    .soft-card-header {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid var(--border);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #fff;
    }

    /* ── Table Styles ── */
    .table thead th {
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
        font-weight: 600;
        border-bottom: 1px solid var(--border);
    }

    .table tbody tr {
        transition: background 0.15s;
    }

    .table-hover tbody tr:hover {
        background-color: #f8fafc;
    }

    /* ── Progress Bar ── */
    .hub-progress {
        display: flex;
        height: 8px;
        border-radius: 999px;
        background: #eef2f7;
        overflow: hidden;
    }

    .hub-progress .progress-bar {
        height: 100%;
        border-radius: 999px;
        transition: width 0.4s ease;
    }

    /* ── Effective Weight Badge ── */
    .badge-volumetric {
        background: #ede9fe;
        color: #6d28d9;
        border: 1px solid #ddd6fe;
        border-radius: 999px;
        font-size: 0.72rem;
        padding: 0.2rem 0.55rem;
        font-weight: 600;
    }

    .badge-actual {
        background: #ecfdf5;
        color: #166534;
        border: 1px solid #bbf7d0;
        border-radius: 999px;
        font-size: 0.72rem;
        padding: 0.2rem 0.55rem;
        font-weight: 600;
    }

    /* ── Fleet Track Modal Info ── */
    .fleet-info-row {
        display: flex;
        gap: 0.75rem;
        align-items: flex-start;
        padding: 0.75rem 0;
        border-bottom: 1px solid #f1f5f9;
    }

    .fleet-info-row:last-child { border-bottom: none; }

    .fleet-info-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: #eff6ff;
        color: #2563eb;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        border: 1px solid #dbeafe;
    }

    /* ── Volumetric Live Preview Box ── */
    .vol-preview {
        background: linear-gradient(135deg, #f0f9ff, #eff6ff);
        border: 1px solid #bfdbfe;
        border-radius: 14px;
        padding: 1rem 1.25rem;
    }

    .vol-preview-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.35rem 0;
    }

    .vol-preview-label {
        font-size: 0.82rem;
        color: #64748b;
    }

    .vol-preview-value {
        font-size: 0.9rem;
        font-weight: 700;
        color: #1e3a8a;
    }

    .vol-preview-divider {
        border: none;
        border-top: 1px dashed #bfdbfe;
        margin: 0.4rem 0;
    }

    .fit-badge {
        font-size: 0.8rem;
        font-weight: 700;
        border-radius: 999px;
        padding: 0.35rem 0.9rem;
    }

    .fit-badge.fit-ok {
        background: #dcfce7;
        color: #15803d;
        border: 1px solid #86efac;
    }

    .fit-badge.fit-no {
        background: #fee2e2;
        color: #b91c1c;
        border: 1px solid #fca5a5;
    }

    /* ── Modal Header custom ── */
    .modal-header-blue {
        background: linear-gradient(135deg, #1e3a8a, #2563eb);
        color: white;
        border-radius: 0;
    }

    .modal-header-blue .btn-close {
        filter: invert(1) brightness(2);
    }

    /* ── Lokasi Hub chip ── */
    .hub-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        background: #f0f9ff;
        border: 1px solid #bae6fd;
        border-radius: 999px;
        padding: 0.2rem 0.6rem;
        font-size: 0.78rem;
        color: #0369a1;
        font-weight: 600;
        white-space: nowrap;
    }

    .status-badge-idle        { background: #ecfdf5; color: #166534; border: 1px solid #bbf7d0; border-radius: 999px; padding: 0.15rem 0.55rem; font-size: 0.75rem; font-weight: 600; }
    .status-badge-in_transit  { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; border-radius: 999px; padding: 0.15rem 0.55rem; font-size: 0.75rem; font-weight: 600; }
    .status-badge-maintenance { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; border-radius: 999px; padding: 0.15rem 0.55rem; font-size: 0.75rem; font-weight: 600; }

    /* Custom Sleek Scrollbar for Table Responsive */
    .table-responsive {
        scrollbar-width: auto;
        scrollbar-color: #cbd5e1 #f8fafc;
        -webkit-overflow-scrolling: touch;
        overflow-x: auto;
    }
    .table-responsive::-webkit-scrollbar {
        height: 10px;
    }
    .table-responsive::-webkit-scrollbar-track {
        background: #f8fafc;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
    }
    .table-responsive::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 8px;
        border: 2px solid #f8fafc;
    }
    .table-responsive::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
</style>
@endpush
