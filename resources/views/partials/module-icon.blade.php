@switch($icon)
    @case('users')
        <svg viewBox="0 0 24 24" role="img" aria-label="Users icon">
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
            <circle cx="9" cy="7" r="4" />
            <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
        </svg>
        @break

    @case('shop')
        <svg viewBox="0 0 24 24" role="img" aria-label="Shop icon">
            <path d="M4 10h16" />
            <path d="M5 10l1-6h12l1 6" />
            <path d="M6 10v10h12V10" />
            <path d="M9 20v-6h6v6" />
        </svg>
        @break

    @case('award')
        <svg viewBox="0 0 24 24" role="img" aria-label="Award icon">
            <circle cx="12" cy="8" r="5" />
            <path d="M8.5 12.5 7 22l5-3 5 3-1.5-9.5" />
        </svg>
        @break

    @case('map')
        <svg viewBox="0 0 24 24" role="img" aria-label="Map icon">
            <path d="m3 6 6-3 6 3 6-3v15l-6 3-6-3-6 3V6z" />
            <path d="M9 3v15" />
            <path d="M15 6v15" />
        </svg>
        @break

    @case('box')
        <svg viewBox="0 0 24 24" role="img" aria-label="Box icon">
            <path d="M21 8 12 3 3 8l9 5 9-5z" />
            <path d="M3 8v8l9 5 9-5V8" />
            <path d="M12 13v8" />
        </svg>
        @break

    @case('community')
        <svg viewBox="0 0 24 24" role="img" aria-label="Community contribution icon">
            <path d="M12 21s7-4.35 7-11a7 7 0 0 0-14 0c0 6.65 7 11 7 11z" />
            <path d="M9.5 10.5h5" />
            <path d="M12 8v5" />
        </svg>
        @break
@endswitch
