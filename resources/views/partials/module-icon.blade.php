@switch($icon)
    @case('dashboard')
        <svg viewBox="0 0 24 24" role="img" aria-label="Dashboard icon">
            <rect x="3" y="3" width="7" height="8" rx="1.5" />
            <rect x="14" y="3" width="7" height="5" rx="1.5" />
            <rect x="14" y="12" width="7" height="9" rx="1.5" />
            <rect x="3" y="15" width="7" height="6" rx="1.5" />
        </svg>
        @break

    @case('user')
        <svg viewBox="0 0 24 24" role="img" aria-label="User icon">
            <circle cx="12" cy="8" r="4" />
            <path d="M4 21a8 8 0 0 1 16 0" />
        </svg>
        @break

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

    @case('passport')
        <svg viewBox="0 0 24 24" role="img" aria-label="Passport icon">
            <path d="M6 3h10a2 2 0 0 1 2 2v16H8a2 2 0 0 1-2-2V3z" />
            <path d="M9 7h6" />
            <circle cx="12" cy="13" r="3" />
            <path d="M9 17h6" />
        </svg>
        @break

    @case('map')
        <svg viewBox="0 0 24 24" role="img" aria-label="Map icon">
            <path d="m3 6 6-3 6 3 6-3v15l-6 3-6-3-6 3V6z" />
            <path d="M9 3v15" />
            <path d="M15 6v15" />
        </svg>
        @break

    @case('shop-plus')
        <svg viewBox="0 0 24 24" role="img" aria-label="Submit shop icon">
            <path d="M4 10h16" />
            <path d="M5 10l1-6h12l1 6" />
            <path d="M6 10v10h8" />
            <path d="M9 20v-6h4" />
            <path d="M18 15v6" />
            <path d="M15 18h6" />
        </svg>
        @break

    @case('file-edit')
        <svg viewBox="0 0 24 24" role="img" aria-label="Drafts icon">
            <path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8" />
            <path d="M14 3v5h5" />
            <path d="m13 16 5-5 3 3-5 5-4 1 1-4z" />
        </svg>
        @break

    @case('history-list')
        <svg viewBox="0 0 24 24" role="img" aria-label="My contributions icon">
            <path d="M3 12a9 9 0 1 0 3-6.7" />
            <path d="M3 4v5h5" />
            <path d="M10 10h7" />
            <path d="M10 14h5" />
            <path d="M10 18h3" />
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

    @case('route')
        <svg viewBox="0 0 24 24" role="img" aria-label="Route icon">
            <circle cx="6" cy="19" r="3" />
            <circle cx="18" cy="5" r="3" />
            <path d="M9 19h3a4 4 0 0 0 0-8h-1a4 4 0 0 1 0-8h4" />
        </svg>
        @break

    @case('bookmark')
        <svg viewBox="0 0 24 24" role="img" aria-label="Saved icon">
            <path d="M6 4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18l-6-4-6 4V4z" />
        </svg>
        @break

    @case('alert')
        <svg viewBox="0 0 24 24" role="img" aria-label="Correction request icon">
            <path d="M12 3 2 21h20L12 3z" />
            <path d="M12 9v5" />
            <path d="M12 17h.01" />
        </svg>
        @break

    @case('ranking')
        <svg viewBox="0 0 24 24" role="img" aria-label="Leaderboard icon">
            <path d="M4 20V10" />
            <path d="M12 20V4" />
            <path d="M20 20v-7" />
            <path d="M2 20h20" />
        </svg>
        @break
@endswitch
