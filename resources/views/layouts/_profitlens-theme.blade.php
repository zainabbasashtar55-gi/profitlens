<style>
    :root {
        --pl-primary: #2563EB;
        --pl-primary-hover: #1D4ED8;
        --pl-success: #10B981;
        --pl-warning: #F59E0B;
        --pl-error: #EF4444;
        --pl-bg: #FFFFFF;
        --pl-surface: #F8FAFC;
        --pl-card: #FFFFFF;
        --pl-border: #E2E8F0;
        --pl-text: #0F172A;
        --pl-muted: #64748B;
        --pl-matte: #111315;
        --pl-matte-soft: #1A1D20;
    }

    body {
        background: var(--pl-surface);
        color: var(--pl-text);
        -webkit-font-smoothing: antialiased;
    }

    /* Exact product palette mappings for existing Tailwind markup. */
    .bg-indigo-600, .bg-indigo-500 { background-color: var(--pl-primary) !important; }
    .hover\:bg-indigo-700:hover, .hover\:bg-indigo-600:hover { background-color: var(--pl-primary-hover) !important; }
    .text-indigo-600, .text-indigo-700, .text-indigo-800 { color: var(--pl-primary) !important; }
    .bg-indigo-50 { background-color: #EFF6FF !important; }
    .bg-indigo-100 { background-color: #DBEAFE !important; }
    .border-indigo-200, .border-indigo-300 { border-color: #BFDBFE !important; }
    .border-indigo-500, .ring-indigo-500 { border-color: var(--pl-primary) !important; --tw-ring-color: var(--pl-primary) !important; }
    .focus\:border-indigo-500:focus { border-color: var(--pl-primary) !important; }
    .focus\:ring-indigo-500:focus { --tw-ring-color: var(--pl-primary) !important; }

    .bg-emerald-500, .bg-emerald-600 { background-color: var(--pl-success) !important; }
    .text-emerald-600, .text-emerald-700, .text-emerald-800 { color: #047857 !important; }
    .bg-emerald-50 { background-color: #ECFDF5 !important; }
    .bg-emerald-100 { background-color: #D1FAE5 !important; }
    .border-emerald-200 { border-color: #A7F3D0 !important; }
    .bg-amber-500 { background-color: var(--pl-warning) !important; }
    .text-amber-700, .text-amber-800 { color: #B45309 !important; }
    .bg-amber-50 { background-color: #FFFBEB !important; }
    .border-amber-200 { border-color: #FDE68A !important; }
    .bg-red-500, .bg-rose-500 { background-color: var(--pl-error) !important; }
    .text-red-600, .text-red-700, .text-rose-600, .text-rose-700, .text-rose-800 { color: #B91C1C !important; }

    .bg-slate-50 { background-color: var(--pl-surface) !important; }
    .bg-white { background-color: var(--pl-card) !important; }
    .border-slate-200, .border-gray-200 { border-color: var(--pl-border) !important; }
    .text-slate-900 { color: var(--pl-text) !important; }
    .text-slate-500, .text-slate-600 { color: var(--pl-muted) !important; }

    input:not([type="checkbox"]):not([type="radio"]),
    select,
    textarea {
        background: #FFFFFF;
        border-color: #CBD5E1 !important;
        color: var(--pl-text);
        border-radius: .625rem !important;
        transition: border-color .18s ease, box-shadow .18s ease;
    }

    input:focus, select:focus, textarea:focus {
        border-color: var(--pl-primary) !important;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .12) !important;
        outline: none;
    }

    button, a { transition-property: color, background-color, border-color, box-shadow, transform; transition-duration: .18s; }
    table { color: var(--pl-text); }
    thead { background: var(--pl-surface); }

    /* Shared cards receive a quieter professional edge and depth. */
    .rounded-lg.border.bg-white,
    .rounded-xl.border.bg-white {
        border-color: var(--pl-border) !important;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .03), 0 8px 24px rgba(15, 23, 42, .035);
    }

    ::selection { background: #BFDBFE; color: #1E3A8A; }
</style>
