<style>
    .custom-doc-btn {
        display: inline-flex; align-items: center; gap: 0.375rem; 
        padding: 0.25rem 0.75rem; background-color: #3b82f6; 
        color: white; border-radius: 9999px; font-size: 0.875rem; 
        font-weight: 600; text-decoration: none; 
        transition: all 0.2s ease-in-out;
    }
    .custom-doc-btn:hover {
        background-color: #2563eb !important;
        transform: scale(1.05);
    }
</style>

@php
    $state = $getRecord()->link_dokumen;
    $isValid = $state && $state !== '-' && filter_var($state, FILTER_VALIDATE_URL);
@endphp

@if($isValid)
    <a href="{{ $state }}" target="_blank" class="custom-doc-btn">
        <svg style="width: 1rem; height: 1rem;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
        </svg>
        Lihat File
    </a>
@else
    <span style="color: #9ca3af;">-</span>
@endif
